<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 1.3
 * Author: Guilamu
 * Text Domain: gf-shortcode-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GFSB_VERSION', '1.3.0' );
define( 'GFSB_FILE', __FILE__ );
define( 'GFSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFSB_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function( $class ) {
	$prefix = 'GFSB\\';
	$base_dir = GFSB_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

use GFSB\Plugin;

add_action( 'plugins_loaded', [ Plugin::class, 'maybe_load_for_ajax' ], 11 );
add_action( 'gform_loaded', [ Plugin::class, 'get_instance' ] );
