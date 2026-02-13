<?php

/**
 * Add custom DNS section to Health Check debug information
 *
 * @param  array $debug_info Array of the Health Check debug information.
 * @return array             Modified array with newly added DNS section.
 */
function dns_info_custom_health_check_dns_section( $debug_info ) {
	// Get site host and derived root domain.
	$site_host = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$site_url  = get_domain( $site_host );

	// Initialize debug info for DNS section.
	$dns_debug_info = array(
		'label'  => __( 'DNS Settings', 'dns-info' ),
		'fields' => array(),
	);

	// Stop for localhost-style hostnames.
	$is_local_host = in_array( $site_host, array( 'localhost' ), true ) || filter_var( $site_host, FILTER_VALIDATE_IP );
	if ( $is_local_host ) {
		$dns_debug_info['fields']['local'] = array(
			'label' => __( 'Localhost install detected', 'dns-info' ),
			'value' => __( 'This section only works with a valid domain.', 'dns-info' ),
		);
		// Add DNS debug info to overall debug info.
		$debug_info['custom_dns_settings'] = $dns_debug_info;

		return $debug_info;
	}

	// If domain resolution failed, show explicit error instead of localhost message.
	if ( empty( $site_url ) ) {
		$dns_debug_info['fields']['domain_error'] = array(
			'label' => __( 'Domain resolution failed', 'dns-info' ),
			'value' => __( 'Could not determine a valid domain for DNS checks.', 'dns-info' ),
		);
		$debug_info['custom_dns_settings'] = $dns_debug_info;
		return $debug_info;
	}

	// Fetch SPF record.
	$spf_records = dns_info_get_dns_records( $site_url, DNS_TXT );

	// Fetch MX records.
	$mx_records = dns_info_get_dns_records( $site_url, DNS_MX );

	// Fetch A record.
	$a_records = dns_info_get_dns_records( $site_url, DNS_A );

	// Fetch AAAA record.
	$aaaa_records = dns_info_get_dns_records( $site_url, DNS_AAAA );

	// Fetch NS records.
	$ns_records = dns_info_get_dns_records( $site_url, DNS_NS );

	// Fetch DMARC record.
	$dmarc_records = dns_info_get_dns_records( "_dmarc.$site_url", DNS_TXT );

	// Fetch PTR record only when at least one valid A record exists.
	$ptr_record = '';
	if ( ! empty( $a_records ) && isset( $a_records[0]['ip'] ) ) {
		$ptr_record = gethostbyaddr( $a_records[0]['ip'] );
	}

	// Fetch CNAME record.
	$cname_records = dns_info_get_dns_records( $site_url, DNS_CNAME );

	// Fetch SOA record.
	$soa_records = dns_info_get_dns_records( $site_url, DNS_SOA );

	// Check SPF record.
	$spf_field = array();
	foreach ( $spf_records as $record ) {
		if ( isset( $record['txt'] ) && strpos( $record['txt'], 'v=spf1' ) === 0 ) {
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
						$priority = isset( $record['pri'] ) ? $record['pri'] : __( 'n/a', 'dns-info' );
						$target   = isset( $record['target'] ) ? $record['target'] : __( 'n/a', 'dns-info' );
						return "Priority: {$priority}, Host: {$target}";
					},
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
						$ip  = isset( $record['ip'] ) ? $record['ip'] : __( 'n/a', 'dns-info' );
						$ttl = isset( $record['ttl'] ) ? $record['ttl'] : __( 'n/a', 'dns-info' );
						return "IPv4 Address: {$ip} (TTL: {$ttl})";
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
						$ipv6 = isset( $record['ipv6'] ) ? $record['ipv6'] : __( 'n/a', 'dns-info' );
						$ttl  = isset( $record['ttl'] ) ? $record['ttl'] : __( 'n/a', 'dns-info' );
						return "IPv6 Address: {$ipv6} (TTL: {$ttl})";
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
						$target = isset( $record['target'] ) ? $record['target'] : __( 'n/a', 'dns-info' );
						return "Name Server: {$target}";
					},
					$ns_records
				)
			) : __( 'No NS records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['ns'] = $ns_field;

	// DMARC Record.
	$dmarc_field = array();
	foreach ( $dmarc_records as $record ) {
		if ( isset( $record['txt'] ) && strpos( $record['txt'], 'v=DMARC1' ) === 0 ) {
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
		'value' => ! empty( $cname_records ) ? implode(
			' | ',
			array_map(
				function( $record ) {
					return isset( $record['target'] ) ? $record['target'] : __( 'n/a', 'dns-info' );
				},
				$cname_records
			)
		) : __( 'No CNAME records found', 'dns-info' ),
	);
	$dns_debug_info['fields']['cname'] = $cname_field;

	// SOA Records.
	$soa_field = array(
		'label' => __( 'SOA Records', 'dns-info' ),
		'value' => ! empty( $soa_records ) ? implode(
			' | ',
				array_map(
					function( $record ) {
						$mname = isset( $record['mname'] ) ? $record['mname'] : __( 'n/a', 'dns-info' );
						$rname = isset( $record['rname'] ) ? $record['rname'] : __( 'n/a', 'dns-info' );
						return "Primary Name Server: {$mname}, Responsible Email Address: {$rname}";
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
