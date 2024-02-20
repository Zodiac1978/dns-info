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

/**
 * Add custom DNS section to Health Check debug information
 *
 * @param  array $debug_info Array of the Health Check debug information.
 * @return array             Modified array with newly added DNS section.
 */
function custom_health_check_dns_section( $debug_info ) {
	// Get site URL.
	$site_url = get_site_url();

	// Fetch SPF record.
	$spf_records = dns_get_record( $site_url, DNS_TXT );

	// Fetch MX records.
	$mx_records = dns_get_record( $site_url, DNS_MX );

	// Fetch A records.
	$a_records = dns_get_record( $site_url, DNS_A );

	// Fetch NS records.
	$ns_records = dns_get_record( $site_url, DNS_NS );

	// Fetch DMARC record.
	$dmarc_records = dns_get_record( "_dmarc.$site_url", DNS_TXT );

	// Initialize debug info for DNS section.
	$dns_debug_info = array(
		'label'  => __( 'DNS Settings', 'dns-info' ),
		'fields' => array(),
	);

	// SPF Records.
	$spf_field = array(
		'label' => __( 'SPF Record', 'dns-info' ),
		'value' => ! empty( $spf_records ) ? implode( '|', array_column( $spf_records, 'txt' ) ) : __( 'No SPF records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['spf'] = $spf_field;

	// MX Records.
	$mx_field = array(
		'label' => __( 'MX Records', 'dns-info' ),
		'value' => ! empty( $mx_records ) ? implode(
			'|',
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
		'label' => __( 'A Records', 'dns-info' ),
		'value' => ! empty( $a_records ) ? implode( '|', array_map( function( $record ) { return "Host: {$record['host']}, IP Address: {$record['ip']}"; }, $a_records ) ) : __( 'No A records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['a'] = $a_field;

	// NS Records.
	$ns_field = array(
		'label' => __( 'NS Records', 'dns-info' ),
		'value' => ! empty( $ns_records ) ? implode( '|', array_map( function( $record ) { return "Host: {$record['host']}, Name Server: {$record['target']}"; }, $ns_records ) ) : __( 'No NS records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['ns'] = $ns_field;

	// DMARC Records.
	$dmarc_field = array(
		'label' => __( 'DMARC Records', 'dns-info' ),
		'value' => ! empty( $dmarc_records ) ? implode( '|', array_column( $dmarc_records, 'txt' ) ) : __( 'No DMARC records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['dmarc'] = $dmarc_field;

	// Add DNS debug info to overall debug info.
	$debug_info['custom_dns_settings'] = $dns_debug_info;

	return $debug_info;
}

// Add the custom DNS section to debug information.
add_filter( 'debug_information', 'custom_health_check_dns_section' );
