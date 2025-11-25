<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 1.0
 * Author: Guilamu
 * Text Domain: gf-shortcode-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GFSB_VERSION', '2.0' );
define( 'GFSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFSB_URL', plugin_dir_url( __FILE__ ) );

class GF_Shortcode_Builder {

	private static $instance = null;
	private $tabs = array();

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter( 'gform_form_settings_menu', array( $this, 'add_settings_menu' ), 10, 2 );
		add_action( 'gform_form_settings_page_gf_shortcode_builder', array( $this, 'settings_page' ) );
		add_action( 'wp_ajax_gfsb_save_tab_order', array( $this, 'save_tab_order' ) );
		
		// Load tab classes
		$this->load_tabs();
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
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		
		$tab_order = isset( $_POST['tab_order'] ) ? array_map( 'sanitize_text_field', $_POST['tab_order'] ) : array();
		
		update_user_meta( get_current_user_id(), 'gfsb_tab_order', $tab_order );
		
		wp_send_json_success( array( 'message' => 'Tab order saved' ) );
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
		<style>
			.gfsb-tabs {
				display: flex;
				gap: 0;
				border-bottom: 1px solid #dcdcde;
				margin-bottom: 24px;
				background: #fff;
				flex-wrap: nowrap;
				overflow-x: auto;
				overflow-y: hidden;
				position: relative;
			}
			.gfsb-tab {
				padding: 10px 10px;
				background: transparent;
				border: none;
				cursor: grab;
				font-size: 13px;
				font-weight: 500;
				color: #50575e;
				border-bottom: 3px solid transparent;
				transition: all 0.2s ease;
				flex: 0 0 auto;
				white-space: nowrap;
				display: flex;
				align-items: center;
				gap: 8px;
				position: relative;
			}
			.gfsb-tab:hover {
				color: #1d2327;
				background: #f6f7f7;
			}
			.gfsb-tab.active {
				color: #1d2327;
				border-bottom-color: #3858e9;
			}
			.gfsb-tab.dragging {
				opacity: 0.5;
				cursor: grabbing;
			}
			.gfsb-tab.drag-over {
				border-left: 3px solid #3858e9;
			}
			.gfsb-tab-drag-handle {
				cursor: grab;
				color: #a7aaad;
				font-size: 16px;
				line-height: 1;
				display: flex;
				align-items: center;
			}
			.gfsb-tab-drag-handle:hover {
				color: #1d2327;
			}
			.gfsb-tab.dragging .gfsb-tab-drag-handle {
				cursor: grabbing;
			}
			.gfsb-tab-content {
				display: none;
			}
			.gfsb-tab-content.active {
				display: block;
			}
			.gform-settings-field { 
				margin-bottom: 24px; 
			}
			.gform-settings-label { 
				font-weight: 600; 
				display: block; 
				margin-bottom: 8px; 
			}
			.fullwidth-input { 
				width: 100% !important; 
				max-width: 100% !important; 
				box-sizing: border-box; 
			}
			.gfsb-help-text {
				color: #646970;
				font-size: 13px;
				margin-top: 8px;
			}
			.gfsb-form-selector {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 12px;
				background: #f6f7f7;
				border-radius: 4px;
				margin-bottom: 10px;
			}
			.gfsb-form-selector select {
				flex: 1;
			}
			.gfsb-form-selector .button {
				flex-shrink: 0;
			}
			.gfsb-warning-box {
				background: #fcf9e8;
				border-left: 4px solid #dba617;
				padding: 15px;
				border-radius: 4px;
				margin-bottom: 20px;
			}
			.gfsb-warning-box h4 {
				margin-top: 0;
				color: #1d2327;
			}
			.gfsb-tab-order-notice {
				display: none;
				padding: 8px 12px;
				background: #d7f1ff;
				border-left: 4px solid #3858e9;
				margin-bottom: 16px;
				font-size: 13px;
				color: #1d2327;
			}
			.gfsb-tab-order-notice.show {
				display: block;
			}
		</style>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Shortcode Builder', 'gf-shortcode-builder' ); ?></h4>
			</header>

			<div class="gform-settings-panel__content">
				
				<div id="gfsb-tab-order-notice" class="gfsb-tab-order-notice">
					<?php esc_html_e( 'Tab order saved!', 'gf-shortcode-builder' ); ?>
				</div>

				<!-- Tab Navigation -->
				<div class="gfsb-tabs" id="gfsb-tabs-container">
					<?php
					$first_tab = true;
					foreach ( $this->tabs as $tab_id => $tab_instance ) {
						$active_class = $first_tab ? ' active' : '';
						$first_tab = false;
						?>
						<button 
							class="gfsb-tab<?php echo $active_class; ?>" 
							data-tab="<?php echo esc_attr( $tab_id ); ?>" 
							onclick="gfsbSwitchTab(event, '<?php echo esc_attr( $tab_id ); ?>')"
							draggable="true"
							ondragstart="gfsbDragStart(event)"
							ondragend="gfsbDragEnd(event)"
							ondragover="gfsbDragOver(event)"
							ondrop="gfsbDrop(event)"
							ondragleave="gfsbDragLeave(event)">
							<span class="gfsb-tab-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'gf-shortcode-builder' ); ?>">⋮⋮</span>
							<span><?php echo esc_html( $tab_instance->get_title() ); ?></span>
						</button>
						<?php
					}
					?>
				</div>

				<!-- Tab Content -->
				<?php
				$first_tab = true;
				foreach ( $this->tabs as $tab_id => $tab_instance ) {
					$active_class = $first_tab ? ' active' : '';
					$first_tab = false;
					?>
					<div id="tab-<?php echo esc_attr( $tab_id ); ?>" class="gfsb-tab-content<?php echo $active_class; ?>">
						<?php $tab_instance->render( $form ); ?>
					</div>
					<?php
				}
				?>

			</div>
		</div>

		<script type="text/javascript">
			var gfsbDraggedElement = null;

			function gfsbSwitchTab(event, tabId) {
				event.preventDefault();
				
				// Remove active class from all tabs and content
				document.querySelectorAll('.gfsb-tab').forEach(function(tab) {
					tab.classList.remove('active');
				});
				document.querySelectorAll('.gfsb-tab-content').forEach(function(content) {
					content.classList.remove('active');
				});
				
				// Add active class to selected tab and content
				event.currentTarget.classList.add('active');
				document.getElementById('tab-' + tabId).classList.add('active');
			}

			function gfsbDragStart(event) {
				gfsbDraggedElement = event.currentTarget;
				event.currentTarget.classList.add('dragging');
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData('text/html', event.currentTarget.innerHTML);
			}

			function gfsbDragEnd(event) {
				event.currentTarget.classList.remove('dragging');
				
				// Remove all drag-over classes
				document.querySelectorAll('.gfsb-tab').forEach(function(tab) {
					tab.classList.remove('drag-over');
				});

				// Save the new order
				gfsbSaveTabOrder();
			}

			function gfsbDragOver(event) {
				if (event.preventDefault) {
					event.preventDefault();
				}
				event.dataTransfer.dropEffect = 'move';
				
				var target = event.currentTarget;
				if (target !== gfsbDraggedElement) {
					target.classList.add('drag-over');
				}
				
				return false;
			}

			function gfsbDragLeave(event) {
				event.currentTarget.classList.remove('drag-over');
			}

			function gfsbDrop(event) {
				if (event.stopPropagation) {
					event.stopPropagation();
				}
				
				var target = event.currentTarget;
				
				if (gfsbDraggedElement !== target) {
					var container = document.getElementById('gfsb-tabs-container');
					var allTabs = Array.from(container.children);
					var draggedIndex = allTabs.indexOf(gfsbDraggedElement);
					var targetIndex = allTabs.indexOf(target);
					
					if (draggedIndex < targetIndex) {
						target.parentNode.insertBefore(gfsbDraggedElement, target.nextSibling);
					} else {
						target.parentNode.insertBefore(gfsbDraggedElement, target);
					}
				}
				
				target.classList.remove('drag-over');
				
				return false;
			}

			function gfsbSaveTabOrder() {
				var tabs = document.querySelectorAll('.gfsb-tab');
				var tabOrder = [];
				
				tabs.forEach(function(tab) {
					tabOrder.push(tab.getAttribute('data-tab'));
				});

				// Save via AJAX
				var data = {
					action: 'gfsb_save_tab_order',
					nonce: '<?php echo wp_create_nonce( 'gfsb_tab_order' ); ?>',
					tab_order: tabOrder
				};

				jQuery.post(ajaxurl, data, function(response) {
					if (response.success) {
						// Show success notice
						var notice = document.getElementById('gfsb-tab-order-notice');
						notice.classList.add('show');
						setTimeout(function() {
							notice.classList.remove('show');
						}, 3000);
					}
				});
			}
		</script>
		<?php
	}
}

add_action( 'gform_loaded', array( 'GF_Shortcode_Builder', 'get_instance' ) );
