<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Plugin URI: https://github.com/guilamu/gravity-forms-shortcode-builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 1.5.0
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * Text Domain: gf-shortcode-builder
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/guilamu/gravity-forms-shortcode-builder/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail if another copy of this plugin already loaded its constants.
if ( defined( 'GFSB_VERSION' ) ) {
	return;
}

// Define plugin constants.
define( 'GFSB_VERSION', '1.5.0' );
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
		require_once $file;
	}
} );

use GFSB\Plugin;

// GitHub auto-updater.
require_once __DIR__ . '/includes/class-github-updater.php';
GFSB_GitHub_Updater::init();

add_action( 'plugins_loaded', [ Plugin::class, 'maybe_load_for_ajax' ], 11 );
add_action( 'gform_loaded', [ Plugin::class, 'get_instance' ] );
