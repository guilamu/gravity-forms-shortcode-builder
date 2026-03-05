<?php
/**
 * Gravity Forms Shortcode Builder – Uninstall
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all transients and user meta created by the plugin.
 *
 * @package GFSB
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete GitHub updater transient.
delete_transient( 'gfsb_github_release' );

// Delete per-user tab order and visibility preferences.
delete_metadata( 'user', 0, 'gfsb_tab_order', '', true );
delete_metadata( 'user', 0, 'gfsb_disabled_tabs', '', true );
