<?php

/**
 * Get domain name without subdomain
 * Based on: https://stackoverflow.com/questions/2679618/get-domain-name-not-subdomain-in-php
 *
 * @param  string $cache_dir Cache directory on server.
 * @return string            Directory of the list with all TLDs.
 */
function tld_list( $cache_dir = null ) {
	global $wp_filesystem;

	// Initialize WP_Filesystem if not already available.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		/** @phpstan-ignore-next-line */
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	// Use "/tmp" if $cache_dir is not set.
	$cache_dir = isset( $cache_dir ) ? $cache_dir : sys_get_temp_dir();
	$lock_dir  = trailingslashit( $cache_dir ) . 'public_suffix_list_lock/';
	$list_dir  = trailingslashit( $cache_dir ) . 'public_suffix_list/';

	// Refresh list every 30 days.
	if ( $wp_filesystem->exists( $list_dir ) && $wp_filesystem->mtime( $list_dir ) + 2592000 > time() ) {
		return $list_dir;
	}

	// Use exclusive lock to avoid race conditions.
	if ( ! $wp_filesystem->exists( $lock_dir ) && $wp_filesystem->mkdir( $lock_dir ) ) {
		// Read from source.
		$response = wp_remote_get( 'https://publicsuffix.org/list/public_suffix_list.dat' );
		if ( is_wp_error( $response ) ) {
			$wp_filesystem->rmdir( $lock_dir );
			return '';
		}
		$list = wp_remote_retrieve_body( $response );

		// The list is older than 30 days, so delete everything first.
		if ( $wp_filesystem->exists( $list_dir ) ) {
			foreach ( $wp_filesystem->dirlist( $list_dir ) as $filename ) {
				$wp_filesystem->delete( $list_dir . $filename['name'] );
			}
			$wp_filesystem->rmdir( $list_dir );
		}

		// Set list directory with new timestamp.
		$wp_filesystem->mkdir( $list_dir );

		// Read line-by-line to avoid high memory usage.
		$lines = explode( "\n", $list );
		foreach ( $lines as $line ) {
			$line = trim( $line );

			// Skip comments and empty lines.
			if ( strlen( $line ) === 0 || $line[0] === '/' ) {
				continue;
			}

			// Remove wildcard and exclamation mark.
			if ( substr( $line, 0, 2 ) === '*.' ) {
				$line = substr( $line, 2 );
			} elseif ( $line[0] === '!' ) {
				$line = substr( $line, 1 );
			}

			// Reverse TLD and remove line break.
			$line = implode( '.', array_reverse( explode( '.', $line ) ) );

			// Split the TLD list to reduce memory usage.
			$wp_filesystem->touch( $list_dir . $line );
		}
	}

	// Repair locks (should never happen).
	if ( $wp_filesystem->exists( $lock_dir ) && wp_rand( 0, 100 ) === 0 && $wp_filesystem->mtime( $lock_dir ) + 86400 < time() ) {
		$wp_filesystem->rmdir( $lock_dir );
	}

	return $list_dir;
}

/**
 * Get domain of a given URL
 *
 * @param  string $url The given URL.
 * @return string      The root domain name with TLD.
 */
function get_domain( $url = null ) {
	// Obtain location of public suffix list.
	$tld_dir = tld_list();

	// No url = our own host.
	$url = isset( $url ) ? $url : ( isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '' );

	// Add missing scheme (ftp:// or http:// or ftps:// or https://).
	$url = ! isset( $url[5] ) || ( $url[3] !== ':' && $url[4] !== ':' && $url[5] !== ':' ) ? 'http://' . $url : $url;

	// Remove "/path/file.html", "/:80", etc.
	$url = wp_parse_url( $url, PHP_URL_HOST );

	// Replace absolute domain name by relative (http://www.dns-sd.org/TrailingDotsInDomainNames.html).
	$url = trim( $url, '.' );

	// Check if TLD exists.
	$url   = explode( '.', $url );
	$parts = array_reverse( $url );

	foreach ( $parts as $key => $part ) {
		$tld = implode( '.', $parts );
		if ( file_exists( $tld_dir . $tld ) ) {
			return ! $key ? '' : implode( '.', array_slice( $url, $key - 1 ) );
		}
		// Remove last part.
		array_pop( $parts );
	}
	return '';
}

/**
 * Get SPF record
 *
 * @param  string $domain Host to get the SPF record from.
 * @return string         String with SPF record or empty if no SPF record found.
 */
function get_spf_record( $domain ) {
	$spf_record = dns_get_record( $domain, DNS_TXT );
	foreach ( $spf_record as $record ) {
		if ( strpos( $record['txt'], 'v=spf1' ) !== false ) {
			return $record['txt'];
		}
	}
	return '';
}

/**
 * Get DMARC record
 *
 * @param  string $domain Host to get the DMARC record from.
 * @return string         String with DMARC record or empty if no DMARC record found.
 */
function get_dmarc_record( $domain ) {
	$dmarc_record = dns_get_record( "_dmarc.$domain", DNS_TXT );
	foreach ( $dmarc_record as $record ) {
		if ( strpos( $record['txt'], 'v=DMARC1' ) !== false ) {
			return $record['txt'];
		}
	}
	return '';
}
