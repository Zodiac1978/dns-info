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

// Load DNS section.
require dirname( __FILE__ ) . '/inc/dns-section.php';

// Load DNS Checks like SPF and DMARC.
require dirname( __FILE__ ) . '/inc/dns-checks.php';
