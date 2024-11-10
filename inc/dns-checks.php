<?php

/**
 * Register site status check for SPF record.
 *
 * @param  array $tests Array of current checks.
 * @return array        Array with addition of spf check.
 */
function dns_info_register_spf_record_check( $tests ) {
	$tests['direct']['spf_record'] = array(
		'label' => __( 'SPF Record Check', 'dns-info' ),
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
		'label'       => __( 'SPF Record is properly configured', 'dns-info' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security', 'dns-info' ),
			'color' => 'blue',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your SPF record is properly configured, helping to prevent email spoofing.', 'dns-info' )
		),
		'actions'     => '',
		'test'        => 'spf_record',
	);

	$domain     = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$spf_record = get_spf_record( $domain );

	if ( empty( $spf_record ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'SPF Record is not found', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'Your SPF record is not found. It is recommended to create and configure an SPF record to prevent email spoofing.', 'dns-info' )
		);
		$result['actions']    .= sprintf(
			'<p>%s</p>',
			__( 'Please consult your DNS provider or system administrator to create an SPF record for your domain.', 'dns-info' )
		);
	}

	return $result;
}

/**
 * Register site status check for DMARC record.
 *
 * @param  array $tests Array of current checks.
 * @return array        Array with addition of spf check.
 */
function dns_info_register_dmarc_record_check( $tests ) {
	$tests['direct']['dmarc_record'] = array(
		'label' => __( 'DMARC Record Check', 'dns-info' ),
		'test'  => 'dns_info_dmarc_record_check',
	);
	return $tests;
}
add_filter( 'site_status_tests', 'dns_info_register_dmarc_record_check' );


/**
 * Add site status check for DMARC record.
 *
 * @return array Array of results with addition for spf check.
 */
function dns_info_dmarc_record_check() {
	$result = array(
		'label'       => __( 'DMARC Record is properly configured', 'dns-info' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security', 'dns-info' ),
			'color' => 'blue',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your DMARC record is properly configured, helping to prevent email spoofing.', 'dns-info' )
		),
		'actions'     => '',
		'test'        => 'dmarc_record',
	);

	$domain       = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$dmarc_record = get_dmarc_record( $domain );

	if ( empty( $dmarc_record ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'DMARC Record is not found', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'Your DMARC record is not found. It is recommended to create and configure an DMARC record to prevent email spoofing.', 'dns-info' )
		);
		$result['actions']    .= sprintf(
			'<p>%s</p>',
			__( 'Please consult your DNS provider or system administrator to create an DMARC record for your domain.', 'dns-info' )
		);
	}

	return $result;
}