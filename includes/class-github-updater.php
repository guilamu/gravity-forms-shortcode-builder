<?php
/**
 * GitHub Auto-Updater for Gravity Forms Shortcode Builder.
 *
 * Enables automatic updates from GitHub releases through the standard
 * WordPress admin update interface.
 *
 * @package GFSB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GFSB_GitHub_Updater
 *
 * Handles automatic updates from GitHub releases.
 */
class GFSB_GitHub_Updater {

	// =========================================================================
	// CONFIGURATION
	// =========================================================================

	/** @var string GitHub username. */
	private const GITHUB_USER = 'guilamu';

	/** @var string GitHub repository name. */
	private const GITHUB_REPO = 'gravity-forms-shortcode-builder';

	/** @var string Plugin file path relative to the plugins directory. */
	private const PLUGIN_FILE = 'gravity-forms-shortcode-builder/gf-shortcode-builder.php';

	/** @var string Plugin slug. */
	private const PLUGIN_SLUG = 'gravity-forms-shortcode-builder';

	/** @var string Plugin display name. */
	private const PLUGIN_NAME = 'Gravity Forms Shortcode Builder';

	/** @var string Plugin description. */
	private const PLUGIN_DESCRIPTION = 'Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.';

	/** @var string Minimum WordPress version. */
	private const REQUIRES_WP = '5.8';

	/** @var string Tested up to WordPress version. */
	private const TESTED_WP = '6.7';

	/** @var string Minimum PHP version. */
	private const REQUIRES_PHP = '7.4';

	// =========================================================================
	// CACHE SETTINGS
	// =========================================================================

	/** @var string Transient key for caching release data. */
	private const CACHE_KEY = 'gfsb_github_release';

	/** @var int Cache expiration in seconds (12 hours). */
	private const CACHE_EXPIRATION = 43200;

	/**
	 * Optional GitHub token for private repos or to avoid rate limits.
	 * Leave empty for public repos.
	 * Recommended: define 'GFSB_GITHUB_TOKEN' in wp-config.php.
	 *
	 * @var string
	 */
	private const GITHUB_TOKEN = '';

	// =========================================================================
	// IMPLEMENTATION
	// =========================================================================

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'update_plugins_github.com', array( self::class, 'check_for_update' ), 10, 4 );
		add_filter( 'plugins_api', array( self::class, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( self::class, 'fix_folder_name' ), 10, 4 );
	}

	/**
	 * Get the GitHub token, checking wp-config constant first.
	 *
	 * @return string
	 */
	private static function get_token(): string {
		if ( defined( 'GFSB_GITHUB_TOKEN' ) ) {
			$token = (string) constant( 'GFSB_GITHUB_TOKEN' );
			if ( '' !== $token ) {
				return $token;
			}
		}
		return self::GITHUB_TOKEN;
	}

	/**
	 * Fetch and cache the latest release data from GitHub.
	 *
	 * @return array|null Release data array or null on failure.
	 */
	private static function get_release_data(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$token   = self::get_token();
		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . self::PLUGIN_SLUG,
		);

		if ( ! empty( $token ) ) {
			$headers['Authorization'] = 'token ' . $token;
		}

		$response = wp_remote_get(
			sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', self::GITHUB_USER, self::GITHUB_REPO ),
			array(
				'headers' => $headers,
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( self::PLUGIN_NAME . ' updater error: ' . $response->get_error_message() );
			}
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( self::PLUGIN_NAME . " updater error: HTTP {$code}" );
			}
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['tag_name'] ) ) {
			return null;
		}

		set_transient( self::CACHE_KEY, $data, self::CACHE_EXPIRATION );

		return $data;
	}

	/**
	 * Resolve the download URL for the plugin package.
	 * Prefers a custom .zip asset over GitHub's auto-generated zipball.
	 *
	 * @param array $release_data GitHub release data.
	 * @return string Download URL.
	 */
	private static function get_package_url( array $release_data ): string {
		if ( ! empty( $release_data['assets'] ) && is_array( $release_data['assets'] ) ) {
			foreach ( $release_data['assets'] as $asset ) {
				if (
					isset( $asset['browser_download_url'], $asset['name'] ) &&
					'.zip' === substr( $asset['name'], -4 )
				) {
					return $asset['browser_download_url'];
				}
			}
		}

		return $release_data['zipball_url'] ?? '';
	}

	/**
	 * Check for a newer version on GitHub.
	 *
	 * @param array|false $update      Current update data.
	 * @param array       $plugin_data Plugin header data.
	 * @param string      $plugin_file Plugin relative file path.
	 * @param array       $locales     Active locales.
	 * @return array|false Update data or original value.
	 */
	public static function check_for_update( $update, array $plugin_data, string $plugin_file, $locales ) {
		if ( self::PLUGIN_FILE !== $plugin_file ) {
			return $update;
		}

		$release = self::get_release_data();
		if ( null === $release ) {
			return $update;
		}

		$new_version = ltrim( $release['tag_name'], 'v' );

		if ( version_compare( $plugin_data['Version'], $new_version, '>=' ) ) {
			return $update;
		}

		return array(
			'version'       => $new_version,
			'package'       => self::get_package_url( $release ),
			'url'           => $release['html_url'],
			'tested'        => self::TESTED_WP,
			'requires_php'  => self::REQUIRES_PHP,
			'compatibility' => new stdClass(),
			'icons'         => array(),
			'banners'       => array(),
		);
	}

	/**
	 * Provide plugin details for the WordPress plugin information popup.
	 *
	 * @param false|object|array $res    Existing result.
	 * @param string             $action API action.
	 * @param object             $args   API arguments.
	 * @return false|object Plugin info object.
	 */
	public static function plugin_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		if ( ! isset( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $res;
		}

		$release = self::get_release_data();

		// Even if GitHub is unavailable, return our own object to prevent
		// WordPress.org API fallback which would show "Plugin not found".
		if ( null === $release ) {
			$plugin_data     = get_plugin_data( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE, false, false );
			$current_version = $plugin_data['Version'] ?? '1.5.0';

			$res               = new stdClass();
			$res->name         = self::PLUGIN_NAME;
			$res->slug         = self::PLUGIN_SLUG;
			$res->version      = $current_version;
			$res->author       = sprintf( '<a href="https://github.com/%s">%s</a>', self::GITHUB_USER, self::GITHUB_USER );
			$res->homepage     = sprintf( 'https://github.com/%s/%s', self::GITHUB_USER, self::GITHUB_REPO );
			$res->requires     = self::REQUIRES_WP;
			$res->tested       = self::TESTED_WP;
			$res->requires_php = self::REQUIRES_PHP;
			$res->sections     = array(
				'description' => self::PLUGIN_DESCRIPTION,
				'changelog'   => sprintf(
					'<p>Unable to fetch changelog from GitHub. Visit <a href="https://github.com/%s/%s/releases" target="_blank">GitHub releases</a> for the latest information.</p>',
					self::GITHUB_USER,
					self::GITHUB_REPO
				),
			);
			return $res;
		}

		$new_version = ltrim( $release['tag_name'], 'v' );

		$res               = new stdClass();
		$res->name         = self::PLUGIN_NAME;
		$res->slug         = self::PLUGIN_SLUG;
		$res->version      = $new_version;
		$res->author       = sprintf( '<a href="https://github.com/%s">%s</a>', self::GITHUB_USER, self::GITHUB_USER );
		$res->homepage     = sprintf( 'https://github.com/%s/%s', self::GITHUB_USER, self::GITHUB_REPO );
		$res->download_link = self::get_package_url( $release );
		$res->requires     = self::REQUIRES_WP;
		$res->tested       = self::TESTED_WP;
		$res->requires_php = self::REQUIRES_PHP;
		$res->last_updated = $release['published_at'] ?? '';
		$res->sections     = array(
			'description' => self::PLUGIN_DESCRIPTION,
			'changelog'   => ! empty( $release['body'] )
				? nl2br( esc_html( $release['body'] ) )
				: sprintf(
					'See <a href="https://github.com/%s/%s/releases" target="_blank">GitHub releases</a> for the changelog.',
					self::GITHUB_USER,
					self::GITHUB_REPO
				),
		);

		return $res;
	}

	/**
	 * Rename the extracted update folder to the correct plugin folder name.
	 * GitHub zipballs extract as "username-repo-hash/" which breaks WordPress.
	 *
	 * @param string      $source        Extracted source path.
	 * @param string      $remote_source Remote file source.
	 * @param WP_Upgrader $upgrader      Upgrader instance.
	 * @param array       $hook_extra    Extra hook arguments.
	 * @return string|WP_Error Corrected source path or error.
	 */
	public static function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || self::PLUGIN_FILE !== $hook_extra['plugin'] ) {
			return $source;
		}

		$correct_folder = dirname( self::PLUGIN_FILE );
		$source_folder  = basename( untrailingslashit( $source ) );

		if ( $source_folder === $correct_folder ) {
			return $source;
		}

		$new_source = trailingslashit( $remote_source ) . $correct_folder . '/';

		if ( $wp_filesystem && $wp_filesystem->move( $source, $new_source ) ) {
			return $new_source;
		}

		if ( $wp_filesystem && $wp_filesystem->copy( $source, $new_source, true ) && $wp_filesystem->delete( $source, true ) ) {
			return $new_source;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf(
				'%s updater: failed to rename update folder from %s to %s',
				self::PLUGIN_NAME,
				$source,
				$new_source
			) );
		}

		return new WP_Error(
			'rename_failed',
			__( 'Unable to rename the update folder. Please retry or update manually.', 'gf-shortcode-builder' )
		);
	}
}
