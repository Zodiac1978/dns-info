<?php
/**
 * Plugin Name: DNS Info
 * Description: Add DNS information to Health Check Debug information table, like SPF, MX, NS and A records.
 * Plugin URI:  https://torstenlandsiedel.de
 * Version:     1.0.0
 * Author:      Torsten Landsiedel
 * Author URI:  https://torstenlandsiedel.de
 * Licence:     GPL 2
 * License URI: http://opensource.org/licenses/GPL-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load helper functions.
require dirname( __FILE__ ) . '/inc/functions.php';

/**
 * Add custom DNS section to Health Check debug information
 *
 * @param  array $debug_info Array of the Health Check debug information.
 * @return array             Modified array with newly added DNS section.
 */
function dns_info_custom_health_check_dns_section( $debug_info ) {
	// Get site URL.
	$site_url = wp_parse_url( get_site_url(), PHP_URL_HOST );

	// Remove subdomains.
	$site_url = get_domain( $site_url );

	// Initialize debug info for DNS section.
	$dns_debug_info = array(
		'label'  => __( 'DNS Settings', 'dns-info' ),
		'fields' => array(),
	);

	// Define localhost IPs.
	$whitelist = array(
		'127.0.0.1',
		'::1',
	);

	// Check for local IP and stop if detected.
	if ( isset( $_SERVER['REMOTE_ADDR'] ) && in_array( $_SERVER['REMOTE_ADDR'], $whitelist, true ) ) {
		$dns_debug_info['fields']['local'] = array(
			'label' => __( 'Localhost install detected', 'dns-info' ),
			'value' => __( 'This section only works with a valid domain.', 'dns-info' ),
		);
		// Add DNS debug info to overall debug info.
		$debug_info['custom_dns_settings'] = $dns_debug_info;

		return $debug_info;
	}

	// Fetch SPF record.
	$spf_records = dns_get_record( $site_url, DNS_TXT );

	// Fetch MX records.
	$mx_records = dns_get_record( $site_url, DNS_MX );

	// Fetch A record.
	$a_records = dns_get_record( $site_url, DNS_A );

	// Fetch AAAA record.
	$aaaa_records = dns_get_record( $site_url, DNS_AAAA );

	// Fetch NS records.
	$ns_records = dns_get_record( $site_url, DNS_NS );

	// Fetch DMARC record.
	$dmarc_records = dns_get_record( "_dmarc.$site_url", DNS_TXT );

	// Fetch PTR record.
	$ptr_record = gethostbyaddr( $a_records[0]['ip'] );

	// Fetch CNAME record.
	$cname_records = dns_get_record( $site_url, DNS_CNAME );

	// Fetch SOA record.
	$soa_records = dns_get_record( $site_url, DNS_SOA );

	// Check SPF record.
	$spf_field = array();
	foreach ( $spf_records as $record ) {
		if ( strpos( $record['txt'], 'v=spf1' ) === 0 ) {
			$spf_field['value'] = $record['txt'];
			break;
		}
	}

	// If no SPF record found, set default message.
	if ( empty( $spf_field ) ) {
		$spf_field['value'] = __( 'No SPF record found', 'dns-info' );
	}

	// Add SPF field.
	$dns_debug_info['fields']['spf'] = array(
		'label' => __( 'SPF Record', 'dns-info' ),
		'value' => $spf_field['value'],
	);

	// MX Records.
	$mx_field = array(
		'label' => __( 'MX Records', 'dns-info' ),
		'value' => ! empty( $mx_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return "Priority: {$record['pri']}, Host: {$record['target']}"; },
				$mx_records
			)
		) : __( 'No MX records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['mx'] = $mx_field;

	// A Records.
	$a_field = array(
		'label' => __( 'A Record', 'dns-info' ),
		'value' => ! empty( $a_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return "IPv4 Address: {$record['ip']} (TTL: {$record['ttl']} )";
				},
				$a_records
			)
		) : __( 'No A records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['a'] = $a_field;

	// AAAA Records.
	$aaaa_field = array(
		'label' => __( 'AAAA Record', 'dns-info' ),
		'value' => ! empty( $aaaa_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return "IPv6 Address: {$record['ipv6']} (TTL: {$record['ttl']} )";
				},
				$aaaa_records
			)
		) : __( 'No AAAA records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['aaaa'] = $aaaa_field;

	// NS Records.
	$ns_field = array(
		'label' => __( 'NS Records', 'dns-info' ),
		'value' => ! empty( $ns_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return "Name Server: {$record['target']}";
				},
				$ns_records
			)
		) : __( 'No NS records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['ns'] = $ns_field;

	// DMARC Record.
	$dmarc_field = array();
	foreach ( $dmarc_records as $record ) {
		if ( strpos( $record['txt'], 'v=DMARC1' ) === 0 ) {
			$dmarc_field['value'] = $record['txt'];
			break;
		}
	}

	// If no DMARC record found, set default message.
	if ( empty( $dmarc_field ) ) {
		$dmarc_field['value'] = __( 'No DMARC record found', 'dns-info' );
	}

	// Add DMARC field.
	$dns_debug_info['fields']['dmarc'] = array(
		'label' => __( 'DMARC Record', 'dns-info' ),
		'value' => $dmarc_field['value'],
	);

	// PTR Record.
	$ptr_field = array(
		'label' => __( 'PTR Record', 'dns-info' ),
		'value' => ! empty( $ptr_record ) ? $ptr_record : __( 'No PTR record found', 'dns-info' ),
	);
	$dns_debug_info['fields']['ptr'] = $ptr_field;

	// CNAME Records.
	$cname_field = array(
		'label' => __( 'CNAME Records', 'dns-info' ),
		'value' => ! empty( $cname_records ) ? implode( ' | ', array_column( $cname_records, 'target' ) ) : __( 'No CNAME records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['cname'] = $cname_field;

	// SOA Records.
	$soa_field = array(
		'label' => __( 'SOA Records', 'dns-info' ),
		'value' => ! empty( $soa_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return "Primary Name Server: {$record['mname']}, Responsible Email Address: {$record['rname']}";
				},
				$soa_records
			)
		) : __( 'No SOA records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['soa'] = $soa_field;

	// Add DNS debug info to overall debug info.
	$debug_info['custom_dns_settings'] = $dns_debug_info;

	return $debug_info;
}

// Add the custom DNS section to debug information.
add_filter( 'debug_information', 'dns_info_custom_health_check_dns_section' );


/**
 * Register site status check for SPF record.
 *
 * @param  array $tests Array of current checks.
 * @return array        Array with addition of spf check.
 */
function dns_info_register_spf_record_check( $tests ) {
	$tests['direct']['spf_record'] = array(
		'label' => __( 'SPF Record Check' ),
		'test'  => 'dns_info_spf_record_check',
	);
	return $tests;
}
add_filter( 'site_status_tests', 'dns_info_register_spf_record_check' );


/**
 * Add site status check for SPF record.
 *
 * @return array Array of results with addition for spf check.
 */
function dns_info_spf_record_check() {
	$result = array(
		'label'       => __( 'SPF Record is properly configured' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security' ),
			'color' => 'blue',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your SPF record is properly configured, helping to prevent email spoofing.' )
		),
		'actions'     => '',
		'test'        => 'spf_record',
	);

	$domain     = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$spf_record = get_spf_record( $domain );

	if ( empty( $spf_record ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'SPF Record is not found' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'Your SPF record is not found. It is recommended to create and configure an SPF record to prevent email spoofing.' )
		);
		$result['actions']    .= sprintf(
			'<p>%s</p>',
			__( 'Please consult your DNS provider or system administrator to create an SPF record for your domain.' )
		);
	}

	return $result;
}
