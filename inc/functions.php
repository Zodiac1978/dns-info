<?php

/**
 * Get domain name without subdomain
 * Based on: https://stackoverflow.com/questions/2679618/get-domain-name-not-subdomain-in-php
 *
 * @param  string $cache_dir Cache directory on server.
 * @return string            Directory of the list with all TLDs.
 */
function tld_list( $cache_dir = null ) {
	// We use "/tmp" if $cache_dir is not set.
	$cache_dir = isset( $cache_dir ) ? $cache_dir : sys_get_temp_dir();
	$lock_dir  = $cache_dir . '/public_suffix_list_lock/';
	$list_dir  = $cache_dir . '/public_suffix_list/';

	// Refresh list all 30 days.
	if ( file_exists( $list_dir ) && @filemtime( $list_dir ) + 2592000 > time() ) {
		return $list_dir;
	}
	// Use exclusive lock to avoid race conditions.
	if ( ! file_exists( $lock_dir ) && @mkdir( $lock_dir ) ) {
		// Read from source.
		$list = @fopen( 'https://publicsuffix.org/list/public_suffix_list.dat', 'r' );
		if ( $list ) {
			// The list is older than 30 days so delete everything first.
			if ( file_exists( $list_dir ) ) {
				foreach ( glob( $list_dir . '*' ) as $filename ) {
					unlink( $filename );
				}
				rmdir( $list_dir );
			}
			// now set list directory with new timestamp.
			mkdir( $list_dir );
			// read line-by-line to avoid high memory usage.
			while ( $line = fgets( $list ) ) {
				// Skip comments and empty lines.
				if ( $line[0] === '/' || ! $line ) {
					continue;
				}
				// remove wildcard.
				if ( $line[0] . $line[1] === '*.' ) {
					$line = substr( $line, 2 );
				}
				// remove exclamation mark.
				if ( $line[0] === '!' ) {
					$line = substr( $line, 1 );
				}
				// reverse TLD and remove linebreak.
				$line = implode( '.', array_reverse( explode( '.', ( trim( $line ) ) ) ) );
				// we split the TLD list to reduce memory usage.
				touch( $list_dir . $line );
			}
			fclose( $list );
		}
		@rmdir( $lock_dir );
	}
	// Repair locks (should never happen).
	if ( file_exists( $lock_dir ) && wp_rand( 0, 100 ) === 0 && @filemtime( $lock_dir ) + 86400 < time() ) {
		@rmdir( $lock_dir );
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
	$url = isset( $url ) ? $url : $_SERVER['SERVER_NAME'];

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
