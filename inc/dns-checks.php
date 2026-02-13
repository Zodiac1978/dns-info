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
		'label'       => __( 'SPF Record is properly configured for the site host', 'dns-info' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security', 'dns-info' ),
			'color' => 'blue',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your site host has an SPF record, helping to prevent email spoofing.', 'dns-info' )
		),
		'actions'     => '',
		'test'        => 'spf_record',
	);

	$site_host   = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$root_domain = get_domain( $site_host );

	if ( empty( $site_host ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'SPF check could not determine the site host', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'The plugin could not determine the current site host for SPF checks.', 'dns-info' )
		);
		return $result;
	}

	$is_subdomain = ! empty( $root_domain ) && strtolower( $site_host ) !== strtolower( $root_domain );
	$site_spf     = get_spf_record( $site_host );
	$root_spf     = $is_subdomain ? get_spf_record( $root_domain ) : '';

	$result['actions'] .= sprintf(
		'<p>%s <code>%s</code></p>',
		__( 'Checked site host:', 'dns-info' ),
		esc_html( $site_host )
	);

	if ( $is_subdomain ) {
		$result['actions'] .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Checked root domain:', 'dns-info' ),
			esc_html( $root_domain )
		);
	}

	if ( ! empty( $site_spf ) ) {
		$result['actions'] .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Site host SPF:', 'dns-info' ),
			esc_html( $site_spf )
		);
		return $result;
	}

	if ( $is_subdomain && ! empty( $root_spf ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'SPF record missing on site host (root domain record found)', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'The site host has no SPF record. A root-domain SPF record exists, but SPF does not automatically inherit to subdomains.', 'dns-info' )
		);
		$result['actions']    .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Root domain SPF:', 'dns-info' ),
			esc_html( $root_spf )
		);
		$result['actions']    .= sprintf(
			'<p>%s</p>',
			__( 'Create an SPF record on the site host if this subdomain sends email.', 'dns-info' )
		);
		return $result;
	}

	$result['status']      = 'recommended';
	$result['label']       = __( 'SPF record not found on site host or root domain', 'dns-info' );
	$result['description'] = sprintf(
		'<p>%s</p>',
		__( 'No SPF record was found for the site host, and no fallback record was found on the root domain.', 'dns-info' )
	);
	$result['actions']    .= sprintf(
		'<p>%s</p>',
		__( 'Please consult your DNS provider or system administrator to create an SPF record for your domain.', 'dns-info' )
	);

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
		'label'       => __( 'DMARC Record is properly configured for the site host', 'dns-info' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security', 'dns-info' ),
			'color' => 'blue',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your site host has a DMARC record, helping to prevent email spoofing.', 'dns-info' )
		),
		'actions'     => '',
		'test'        => 'dmarc_record',
	);

	$site_host   = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$root_domain = get_domain( $site_host );

	if ( empty( $site_host ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'DMARC check could not determine the site host', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'The plugin could not determine the current site host for DMARC checks.', 'dns-info' )
		);
		return $result;
	}

	$is_subdomain = ! empty( $root_domain ) && strtolower( $site_host ) !== strtolower( $root_domain );
	$site_dmarc   = get_dmarc_record( $site_host );
	$root_dmarc   = $is_subdomain ? get_dmarc_record( $root_domain ) : '';

	$result['actions'] .= sprintf(
		'<p>%s <code>%s</code></p>',
		__( 'Checked site host:', 'dns-info' ),
		esc_html( $site_host )
	);

	if ( $is_subdomain ) {
		$result['actions'] .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Checked root domain:', 'dns-info' ),
			esc_html( $root_domain )
		);
	}

	if ( ! empty( $site_dmarc ) ) {
		$result['actions'] .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Site host DMARC:', 'dns-info' ),
			esc_html( $site_dmarc )
		);
		return $result;
	}

	if ( $is_subdomain && ! empty( $root_dmarc ) ) {
		$result['status']      = 'recommended';
		$result['label']       = __( 'DMARC record missing on site host (root domain record found)', 'dns-info' );
		$result['description'] = sprintf(
			'<p>%s</p>',
			__( 'The site host has no DMARC record. A root-domain DMARC record exists and may apply to subdomains depending on policy (for example, the "sp" tag).', 'dns-info' )
		);
		$result['actions']    .= sprintf(
			'<p>%s <code>%s</code></p>',
			__( 'Root domain DMARC:', 'dns-info' ),
			esc_html( $root_dmarc )
		);
		$result['actions']    .= sprintf(
			'<p>%s</p>',
			__( 'Consider adding a DMARC record on the site host for explicit subdomain policy.', 'dns-info' )
		);
		return $result;
	}

	$result['status']      = 'recommended';
	$result['label']       = __( 'DMARC record not found on site host or root domain', 'dns-info' );
	$result['description'] = sprintf(
		'<p>%s</p>',
		__( 'No DMARC record was found for the site host, and no fallback record was found on the root domain.', 'dns-info' )
	);
	$result['actions']    .= sprintf(
		'<p>%s</p>',
		__( 'Please consult your DNS provider or system administrator to create a DMARC record for your domain.', 'dns-info' )
	);

	return $result;
}
