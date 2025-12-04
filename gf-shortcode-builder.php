<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 1.2
 * Author: Guilamu
 * Text Domain: gf-shortcode-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GFSB_VERSION', '1.2.0' );
define( 'GFSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFSB_URL', plugin_dir_url( __FILE__ ) );

class GF_Shortcode_Builder {

	private static $instance = null;
	private $tabs = array();
	private $notification_tab_ids = array( 'conditional', 'user-info', 'entry-count', 'entries-left' );
	private $disabled_tabs = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function maybe_load_for_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && class_exists( 'GFForms' ) ) {
			self::get_instance();
		}
	}

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'gform_form_settings_menu', array( $this, 'add_settings_menu' ), 10, 2 );
		add_action( 'gform_form_settings_page_gf_shortcode_builder', array( $this, 'settings_page' ) );
		add_action( 'wp_ajax_gfsb_save_tab_order', array( $this, 'save_tab_order' ) );
		add_action( 'wp_ajax_gfsb_get_tab_content', array( $this, 'ajax_get_tab_content' ) );
		add_action( 'wp_ajax_gfsb_save_tab_visibility', array( $this, 'save_tab_visibility' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_footer', array( $this, 'add_notification_shortcode_modal' ) );
		
		// Load tab classes
		$this->load_tabs();
	}

	/**
	 * Enqueue admin CSS and JS assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on Gravity Forms pages.
		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		$page    = rgget( 'page' );
		$view    = rgget( 'view' );
		$subview = rgget( 'subview' );
		$form_id = rgget( 'id' );

		// Builder page assets.
		if ( 'gf_edit_forms' === $page && 'settings' === $view && 'gf_shortcode_builder' === $subview && $form_id ) {
			wp_enqueue_style(
				'gfsb-admin-builder',
				GFSB_URL . 'assets/css/admin-builder.css',
				array(),
				GFSB_VERSION
			);

			wp_enqueue_script(
				'gfsb-admin-builder',
				GFSB_URL . 'assets/js/admin-builder.js',
				array( 'jquery' ),
				GFSB_VERSION,
				true
			);

			wp_localize_script( 'gfsb-admin-builder', 'gfsbBuilder', array(
				'orderNonce'  => wp_create_nonce( 'gfsb_tab_order' ),
				'toggleNonce' => wp_create_nonce( 'gfsb_toggle_tabs' ),
			) );
		}

		// Notification modal assets.
		if ( 'gf_edit_forms' === $page && 'settings' === $view && 'notification' === $subview && $form_id ) {
			wp_enqueue_style(
				'gfsb-admin-modal',
				GFSB_URL . 'assets/css/admin-modal.css',
				array(),
				GFSB_VERSION
			);

			wp_enqueue_script(
				'gfsb-admin-modal',
				GFSB_URL . 'assets/js/admin-modal.js',
				array( 'jquery' ),
				GFSB_VERSION,
				true
			);

			// Prepare tabs list for modal dropdown.
			$tabs_json = array();
			foreach ( $this->tabs as $tab_id => $tab_instance ) {
				if ( ! in_array( $tab_id, $this->notification_tab_ids, true ) ) {
					continue;
				}
				if ( ! $this->is_tab_enabled( $tab_id ) ) {
					continue;
				}
				$tabs_json[] = array(
					'id'    => $tab_id,
					'title' => $tab_instance->get_title(),
				);
			}

			wp_localize_script( 'gfsb-admin-modal', 'gfsbModal', array(
				'formId'            => intval( $form_id ),
				'nonce'             => wp_create_nonce( 'gfsb_get_tab' ),
				'tabsList'          => $tabs_json,
				'buttonLabel'       => __( 'Shortcode Builder', 'gf-shortcode-builder' ),
				'buttonIcon'        => '↔',
				'insertButtonLabel' => __( 'Insert shortcode', 'gf-shortcode-builder' ),
				'baseTitle'         => __( 'Shortcode Builder', 'gf-shortcode-builder' ),
				'loadingText'       => esc_html__( 'Loading shortcode builder', 'gf-shortcode-builder' ),
				'copyButtonTexts'   => array(
					__( 'Copy to Clipboard', 'gf-shortcode-builder' ),
					'Copy to Clipboard',
					'Copier dans le presse-papiers',
				),
				'errorTabLoad'      => __( 'Unable to load shortcode builder tab.', 'gf-shortcode-builder' ),
				'errorRequest'      => __( 'The shortcode builder could not be loaded. Check the console for details.', 'gf-shortcode-builder' ),
				'errorNoShortcode'  => __( 'Please generate a shortcode first.', 'gf-shortcode-builder' ),
				'errorInsert'       => __( 'Unable to insert the shortcode. Please paste it manually.', 'gf-shortcode-builder' ),
			) );
		}
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'gf-shortcode-builder',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	private function load_tabs() {
		$tab_files = array(
			'core-form-display' => 'tabs/class-gfsb-tab-core-form-display.php',
			'conditional'       => 'tabs/class-gfsb-tab-conditional.php',
			'user-info'         => 'tabs/class-gfsb-tab-user-info.php',
			'login'             => 'tabs/class-gfsb-tab-login.php',
			'split-test'        => 'tabs/class-gfsb-tab-split-test.php',
			'entry-count'       => 'tabs/class-gfsb-tab-entry-count.php',
			'entries-left'      => 'tabs/class-gfsb-tab-entries-left.php',
			'progress-meter'    => 'tabs/class-gfsb-tab-progress-meter.php',
		);

		foreach ( $tab_files as $tab_id => $file ) {
			$file_path = GFSB_PATH . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				
				// Convert tab-id to ClassName format
				$class_name = 'GFSB_Tab_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $tab_id ) ) );
				
				if ( class_exists( $class_name ) ) {
					$tab_instance = new $class_name();
					
					// Check if tab should be displayed (for conditional tabs like Progress Meter)
					if ( method_exists( $tab_instance, 'should_display' ) && ! $tab_instance->should_display() ) {
						continue;
					}
					
					$this->tabs[ $tab_id ] = $tab_instance;
				}
			}
		}

		// Apply saved tab order
		$this->tabs = $this->get_ordered_tabs( $this->tabs );
	}

	private function get_ordered_tabs( $tabs ) {
		$saved_order = get_user_meta( get_current_user_id(), 'gfsb_tab_order', true );
		
		if ( empty( $saved_order ) || ! is_array( $saved_order ) ) {
			return $tabs;
		}

		$ordered_tabs = array();
		
		// First, add tabs in saved order
		foreach ( $saved_order as $tab_id ) {
			if ( isset( $tabs[ $tab_id ] ) ) {
				$ordered_tabs[ $tab_id ] = $tabs[ $tab_id ];
			}
		}
		
		// Then add any new tabs that weren't in saved order
		foreach ( $tabs as $tab_id => $tab_instance ) {
			if ( ! isset( $ordered_tabs[ $tab_id ] ) ) {
				$ordered_tabs[ $tab_id ] = $tab_instance;
			}
		}
		
		return $ordered_tabs;
	}

	public function save_tab_order() {
		check_ajax_referer( 'gfsb_tab_order', 'nonce' );
		
		if ( ! GFCommon::current_user_can_any( array( 'gravityforms_edit_forms', 'gravityforms_create_form', 'gravityforms_notification_settings' ) ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		
		$tab_order = isset( $_POST['tab_order'] ) ? array_map( 'sanitize_text_field', $_POST['tab_order'] ) : array();
		
		update_user_meta( get_current_user_id(), 'gfsb_tab_order', $tab_order );
		
		wp_send_json_success( array( 'message' => 'Tab order saved' ) );
	}

	public function ajax_get_tab_content() {
		check_ajax_referer( 'gfsb_get_tab', 'nonce' );
		
		if ( ! GFCommon::current_user_can_any( array( 'gravityforms_edit_forms', 'gravityforms_create_form', 'gravityforms_notification_settings' ) ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		
		$tab_id = isset( $_POST['tab_id'] ) ? sanitize_text_field( $_POST['tab_id'] ) : '';
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		
		if ( empty( $tab_id ) || ! $form_id ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
		}
		
		$form = GFAPI::get_form( $form_id );
		
		if ( ! $form ) {
			wp_send_json_error( array( 'message' => 'Form not found' ) );
		}
		
		if ( ! isset( $this->tabs[ $tab_id ] ) || ! $this->is_tab_enabled( $tab_id ) ) {
			wp_send_json_error( array( 'message' => 'Tab not found' ) );
		}
		
		ob_start();
		$this->tabs[ $tab_id ]->render( $form );
		$content = ob_get_clean();
		
		wp_send_json_success( array( 'content' => $content ) );
	}

	private function get_disabled_tabs() {
		if ( null === $this->disabled_tabs ) {
			$stored = get_user_meta( get_current_user_id(), 'gfsb_disabled_tabs', true );
			$this->disabled_tabs = is_array( $stored ) ? $stored : array();
		}
		return $this->disabled_tabs;
	}

	private function is_tab_enabled( $tab_id ) {
		$disabled_tabs = $this->get_disabled_tabs();
		return ! in_array( $tab_id, $disabled_tabs, true );
	}

	public function save_tab_visibility() {
		check_ajax_referer( 'gfsb_toggle_tabs', 'nonce' );

		if ( ! GFCommon::current_user_can_any( array( 'gravityforms_edit_forms', 'gravityforms_create_form', 'gravityforms_notification_settings' ) ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$tab_id = isset( $_POST['tab_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tab_id'] ) ) : '';
		$enabled = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];

		if ( empty( $tab_id ) || ! isset( $this->tabs[ $tab_id ] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid tab' ) );
		}

		$disabled_tabs = $this->get_disabled_tabs();

		if ( $enabled ) {
			$disabled_tabs = array_values( array_diff( $disabled_tabs, array( $tab_id ) ) );
		} else {
			if ( ! in_array( $tab_id, $disabled_tabs, true ) ) {
				$disabled_tabs[] = $tab_id;
			}
		}

		$this->disabled_tabs = $disabled_tabs;
		update_user_meta( get_current_user_id(), 'gfsb_disabled_tabs', $disabled_tabs );

		wp_send_json_success( array( 'disabled_tabs' => $disabled_tabs ) );
	}

	public function add_notification_shortcode_modal() {
		// Only add on notification settings page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gf_edit_forms' ) {
			return;
		}
		if ( ! isset( $_GET['view'] ) || $_GET['view'] !== 'settings' ) {
			return;
		}
		if ( ! isset( $_GET['subview'] ) || $_GET['subview'] !== 'notification' ) {
			return;
		}

		$form_id = rgget( 'id' );
		$form    = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return;
		}

		?>
		<div id="gfsb-notification-modal" style="display:none;">
			<div id="gfsb-notification-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 99999; display: none;"></div>
			<div id="gfsb-notification-modal-content" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 100000; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
				<div style="padding: 20px; border-bottom: 1px solid #dcdcde; display: flex; justify-content: space-between; align-items: center;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Shortcode Builder', 'gf-shortcode-builder' ); ?></h2>
					<button id="gfsb-close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #646970; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">&times;</button>
				</div>
				<div id="gfsb-modal-tab-content" style="padding: 20px;">
					<!-- Tab content will be loaded here -->
				</div>
			</div>
		</div>
		<?php
	}

	public function add_settings_menu( $menu_items, $form_id ) {
		$icon_svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><path d="M6.5 10V17.5a3.5 3.5 0 0 0 3.5 3.5H14"></path></svg>';

		$menu_items[] = array(
			'name'  => 'gf_shortcode_builder',
			'label' => __( 'Shortcode Builder', 'gf-shortcode-builder' ),
			'icon'  => $icon_svg,
		);
		return $menu_items;
	}

	public function settings_page() {
		$form_id = rgget( 'id' );
		$form    = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			echo '<p>' . esc_html__( 'Form not found.', 'gf-shortcode-builder' ) . '</p>';
			return;
		}

		GFFormSettings::page_header();
		$this->render_builder_ui( $form );
		GFFormSettings::page_footer();
	}

	private function render_builder_ui( $form ) {
		?>
		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Shortcode Builder', 'gf-shortcode-builder' ); ?></h4>
			</header>

			<div class="gform-settings-panel__content">
				
				<div id="gfsb-accordion-order-notice" class="gfsb-accordion-order-notice">
					<?php esc_html_e( 'Accordion order saved!', 'gf-shortcode-builder' ); ?>
				</div>

				<div class="gfsb-tab-toggle-panel">
					<h5><?php esc_html_e( 'Shortcode Tabs Visibility', 'gf-shortcode-builder' ); ?></h5>
					<p><?php esc_html_e( 'Choose which shortcode tabs are available in the builder and notification modal.', 'gf-shortcode-builder' ); ?></p>
					<div class="gfsb-tab-toggle-grid">
						<?php foreach ( $this->tabs as $tab_id => $tab_instance ) :
							$toggle_label = sprintf( __( 'Enable %s tab', 'gf-shortcode-builder' ), $tab_instance->get_title() );
							?>
							<label class="gfsb-tab-toggle">
								<input
									type="checkbox"
									class="gfsb-tab-toggle-input"
									data-tab="<?php echo esc_attr( $tab_id ); ?>"
									aria-label="<?php echo esc_attr( $toggle_label ); ?>"
									<?php checked( $this->is_tab_enabled( $tab_id ) ); ?>
								/>
								<span><?php echo esc_html( $tab_instance->get_title() ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Accordions -->
				<div class="gfsb-accordions" id="gfsb-accordions-container">
					<?php
					foreach ( $this->tabs as $tab_id => $tab_instance ) {
						?>
						<div 
							class="gfsb-accordion" 
							data-tab="<?php echo esc_attr( $tab_id ); ?>"
							data-tab-enabled="<?php echo $this->is_tab_enabled( $tab_id ) ? '1' : '0'; ?>"
							draggable="true"
							ondragstart="gfsbDragStart(event)"
							ondragend="gfsbDragEnd(event)"
							ondragover="gfsbDragOver(event)"
							ondrop="gfsbDrop(event)"
							ondragleave="gfsbDragLeave(event)">
							<button 
								class="gfsb-accordion-header" 
								onclick="gfsbToggleAccordion(event, '<?php echo esc_attr( $tab_id ); ?>')"
								type="button">
								<span class="gfsb-accordion-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'gf-shortcode-builder' ); ?>">⋮⋮</span>
								<span class="gfsb-accordion-title"><?php echo esc_html( $tab_instance->get_title() ); ?></span>
								<span class="gfsb-accordion-icon">▼</span>
							</button>
							<div class="gfsb-accordion-content">
								<div class="gfsb-accordion-content-inner">
									<?php $tab_instance->render( $form ); ?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
				</div>

			</div>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'GF_Shortcode_Builder', 'maybe_load_for_ajax' ), 11 );
add_action( 'gform_loaded', array( 'GF_Shortcode_Builder', 'get_instance' ) );
