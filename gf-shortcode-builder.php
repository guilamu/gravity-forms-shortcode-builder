<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 2.0
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
				flex-wrap: wrap;
			}
			.gfsb-tab {
				padding: 12px 20px;
				background: transparent;
				border: none;
				cursor: pointer;
				font-size: 14px;
				font-weight: 500;
				color: #50575e;
				border-bottom: 3px solid transparent;
				transition: all 0.2s ease;
			}
			.gfsb-tab:hover {
				color: #1d2327;
				background: #f6f7f7;
			}
			.gfsb-tab.active {
				color: #1d2327;
				border-bottom-color: #3858e9;
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
		</style>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Shortcode Builder', 'gf-shortcode-builder' ); ?></h4>
			</header>

			<div class="gform-settings-panel__content">
				
				<!-- Tab Navigation -->
				<div class="gfsb-tabs">
					<?php
					$first_tab = true;
					foreach ( $this->tabs as $tab_id => $tab_instance ) {
						$active_class = $first_tab ? ' active' : '';
						$first_tab = false;
						?>
						<button class="gfsb-tab<?php echo $active_class; ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>" onclick="gfsbSwitchTab(event, '<?php echo esc_attr( $tab_id ); ?>')">
							<?php echo esc_html( $tab_instance->get_title() ); ?>
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
		</script>
		<?php
	}
}

add_action( 'gform_loaded', array( 'GF_Shortcode_Builder', 'get_instance' ) );
