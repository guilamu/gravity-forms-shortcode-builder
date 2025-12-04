<?php
/**
 * Plugin Name: Gravity Forms Shortcode Builder
 * Description: Adds a tool in Form Settings to easily build various Gravity Forms shortcodes. Compatible with GF Advanced Conditional Shortcodes by GravityWiz.
 * Version: 1.1.1
 * Author: Guilamu
 * Text Domain: gf-shortcode-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GFSB_VERSION', '1.1.1' );
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
		add_action( 'admin_footer', array( $this, 'add_notification_shortcode_modal' ) );
		
		// Load tab classes
		$this->load_tabs();
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
		<style>
			.gfsb-notification-dropdown {
				display: none;
				position: absolute;
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.15);
				z-index: 10000;
				min-width: 250px;
				margin-top: 4px;
			}
			.gfsb-notification-dropdown.show {
				display: block;
			}
			.gfsb-notification-dropdown-item {
				padding: 10px 16px;
				cursor: pointer;
				border-bottom: 1px solid #f0f0f1;
				transition: background 0.2s ease;
			}
			.gfsb-notification-dropdown-item:last-child {
				border-bottom: none;
			}
			.gfsb-notification-dropdown-item:hover {
				background: #f6f7f7;
			}
			#gfsb-modal-tab-content.gfsb-modal-fullwidth .gform-settings-field {
				max-width: none;
				width: 100%;
			}
			#gfsb-modal-tab-content.gfsb-modal-fullwidth .gform-settings-input__container,
			#gfsb-modal-tab-content.gfsb-modal-fullwidth .gform-input,
			#gfsb-modal-tab-content.gfsb-modal-fullwidth .fullwidth-input,
			#gfsb-modal-tab-content.gfsb-modal-fullwidth select,
			#gfsb-modal-tab-content.gfsb-modal-fullwidth input,
			#gfsb-modal-tab-content.gfsb-modal-fullwidth textarea {
				width: 100% !important;
				max-width: 100% !important;
				box-sizing: border-box;
			}
			#gfsb-modal-tab-content .gfsb-modal-preview-field .gform-settings-field__header,
			#gfsb-modal-tab-content .gfsb-modal-preview-field textarea,
			#gfsb-modal-tab-content .gfsb-modal-preview-field #gf_csb_copy_msg {
				display: none !important;
			}
			#gfsb-modal-tab-content .gfsb-modal-preview-field button {
				width: 100%;
				margin-top: 0;
			}
			#gfsb-modal-tab-content .gfsb-modal-preview-field {
				padding-top: 0;
			}
		</style>
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

		<script type="text/javascript">
			window.gfsbNotificationEditorId = window.gfsbNotificationEditorId || '_gform_setting_message';
			window.gfsbNotificationTextarea = window.gfsbNotificationTextarea || null;
			window.gfsbGetNotificationTextarea = function() {
				var selectors = [
					'.wp-_gform_setting_message-editor-container .wp-editor-area',
					'.wp-_gform_setting_message-editor-container textarea',
					'textarea[id^="gform_notification_"][id$="_message"]',
					'#gform_notification_message',
					'#_gform_setting_message'
				];
				if (window.gfsbNotificationTextarea) {
					var cached = jQuery(window.gfsbNotificationTextarea);
					if (cached.length) {
						return cached;
					}
					window.gfsbNotificationTextarea = null;
				}
				for (var i = 0; i < selectors.length; i++) {
					var el = jQuery(selectors[i]).first();
					if (el.length) {
						window.gfsbNotificationTextarea = el.get(0);
						if (el.attr('id')) {
							window.gfsbNotificationEditorId = el.attr('id');
							console.log('GFSB: detected editor ID', window.gfsbNotificationEditorId, 'via selector', selectors[i]);
						} else {
							console.log('GFSB: detected editor textarea without ID via selector', selectors[i]);
						}
						return el;
					}
				}
				return null;
			};
			window.gfsbResolveNotificationEditorId = function() {
				var textarea = window.gfsbGetNotificationTextarea ? window.gfsbGetNotificationTextarea() : null;
				if (textarea && textarea.length && textarea.attr('id')) {
					window.gfsbNotificationEditorId = textarea.attr('id');
					return window.gfsbNotificationEditorId;
				}
				return window.gfsbNotificationEditorId;
			};
			var gfsbButtonLabel = <?php echo wp_json_encode( __( 'Shortcode Builder', 'gf-shortcode-builder' ) ); ?>;
			var gfsbInsertButtonLabel = <?php echo wp_json_encode( __( 'Insert shortcode', 'gf-shortcode-builder' ) ); ?>;
			var gfsbCopyButtonTexts = <?php echo wp_json_encode( array(
				__( 'Copy to Clipboard', 'gf-shortcode-builder' ),
				'Copy to Clipboard',
				'Copier dans le presse-papiers',
			) ); ?>;
			var gfsbButtonIcon = '↔';
			jQuery(document).ready(function($) {
				var attempts = 0;
				var interval = setInterval(function() {
					attempts++;
					var resolvedId = window.gfsbResolveNotificationEditorId();
					if (resolvedId || attempts >= 10) {
						clearInterval(interval);
					}
				}, 500);
				window.gfsbResolveNotificationEditorId();
				var dropdownVisible = false;
				var currentTabId = null;

				function gfsbEnsureBuilderButton() {
					var $button = $('#gfsb-notification-toggle');
					if ($button.length) {
						return $button;
					}
					$button = $('<button/>', {
						type: 'button',
						id: 'gfsb-notification-toggle',
						class: 'button gfsb-notification-toggle',
						css: { display: 'none' }
					}).html('<span style="font-size: 16px; line-height: 1; display: inline-block; margin-right: 4px;">' + gfsbButtonIcon + '</span> ' + gfsbButtonLabel);
					$('body').append($button);
					return $button;
				}

				function gfsbMoveButtonNextToMedia() {
					var $button = gfsbEnsureBuilderButton();
					var selectors = [];
					if (window.gfsbNotificationEditorId) {
						selectors.push('#wp-' + window.gfsbNotificationEditorId + '-wrap .wp-media-buttons');
					}
					selectors.push('#wp-_gform_setting_message-wrap .wp-media-buttons');
					selectors.push('.wp-media-buttons');
					var $mediaButtons = null;
					for (var i = 0; i < selectors.length; i++) {
						var candidate = $(selectors[i]).first();
						if (candidate.length) {
							$mediaButtons = candidate;
							break;
						}
					}
					if (!$mediaButtons || !$mediaButtons.length) {
						return;
					}
					if ($mediaButtons.find('#gfsb-notification-toggle').length) {
						$button.show();
						return;
					}
					var $insertMedia = $mediaButtons.find('.insert-media').first();
					$button.css({ marginBottom: '0', marginLeft: '6px', display: '' });
					$button.detach();
					if ($insertMedia.length) {
						$button.insertAfter($insertMedia);
					} else {
						$mediaButtons.append($button);
					}
				}

				gfsbMoveButtonNextToMedia();
				setTimeout(gfsbMoveButtonNextToMedia, 500);
				setTimeout(gfsbMoveButtonNextToMedia, 1500);

				function gfsbApplyModalLayout() {
					var $content = $('#gfsb-modal-tab-content');
					$content.addClass('gfsb-modal-fullwidth');
					var $previewField = $content.find('#gf_csb_result').closest('.gform-settings-field');
					if ($previewField.length) {
						$previewField.addClass('gfsb-modal-preview-field');
					}
				}

				function gfsbSetupInsertButton() {
					var handled = false;
					$('#gfsb-modal-tab-content').find('button').each(function() {
						var $btn = $(this);
						var btnText = $.trim($btn.text());
						var matchesCopy = gfsbCopyButtonTexts.some(function(text) {
							return text && btnText.indexOf(text) > -1;
						});
						if (matchesCopy || btnText === gfsbInsertButtonLabel) {
							$btn.text(gfsbInsertButtonLabel).attr('onclick', '').off('click').on('click', function(e) {
								e.preventDefault();
								insertShortcodeIntoEditor(currentTabId);
							});
							handled = true;
						}
					});
					return handled;
				}

				// Create dropdown
				$('body').append('<div class="gfsb-notification-dropdown" id="gfsb-dropdown"></div>');

				// Toggle dropdown
				$(document).on('click', '#gfsb-notification-toggle', function(e) {
					e.preventDefault();
					e.stopPropagation();
					
					var dropdown = $('#gfsb-dropdown');
					var button = $(this);
					
					if (dropdownVisible) {
						dropdown.removeClass('show');
						dropdownVisible = false;
					} else {
						// Position dropdown
						var offset = button.offset();
						dropdown.css({
							top: offset.top + button.outerHeight(),
							left: offset.left
						});
						
						// Populate dropdown
						dropdown.empty();
						
						<?php
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
						echo 'var tabsList = ' . wp_json_encode( $tabs_json ) . ';';
						echo 'window.gfsbTabsListLookup = window.gfsbTabsListLookup || {}; tabsList.forEach(function(tab){ window.gfsbTabsListLookup[tab.id] = tab; });';
						?>
						
						tabsList.forEach(function(tab) {
							dropdown.append('<div class="gfsb-notification-dropdown-item" data-tab-id="' + tab.id + '">' + tab.title + '</div>');
						});
						
						dropdown.addClass('show');
						dropdownVisible = true;
					}
				});

				// Close dropdown when clicking outside
				$(document).on('click', function(e) {
					if (!$(e.target).closest('#gfsb-notification-toggle, #gfsb-dropdown').length) {
						$('#gfsb-dropdown').removeClass('show');
						dropdownVisible = false;
					}
				});

				// Handle dropdown item click
				$(document).on('click', '.gfsb-notification-dropdown-item', function() {
					var tabId = $(this).data('tab-id');
					currentTabId = tabId;
					
					// Hide dropdown
					$('#gfsb-dropdown').removeClass('show');
					dropdownVisible = false;
					
					// Show modal with tab content
					showTabModal(tabId);
				});

				function showTabModal(tabId) {
					// Show modal immediately with loading state
					$('#gfsb-notification-modal').show();
					$('#gfsb-notification-overlay').show();
					$('#gfsb-notification-modal-content').show();
					var baseTitle = <?php echo wp_json_encode( __( 'Shortcode Builder', 'gf-shortcode-builder' ) ); ?>;
					var dropdown = window.gfsbTabsListLookup || {};
					var selected = dropdown[tabId];
					var modalTitle = baseTitle;
					if (selected && selected.title) {
						modalTitle = baseTitle + ' : ' + selected.title;
					}
					$('#gfsb-notification-modal-content h2').text(modalTitle);
					$('#gfsb-modal-tab-content').html('<p style="margin:0;">' + <?php echo json_encode( esc_html__( 'Loading shortcode builder', 'gf-shortcode-builder' ) ); ?> + '</p>');

					// Load tab content via AJAX
					var requestData = {
						action: 'gfsb_get_tab_content',
						tab_id: tabId,
						form_id: <?php echo $form_id; ?>,
						nonce: '<?php echo wp_create_nonce( 'gfsb_get_tab' ); ?>'
					};
					console.log('GFSB: loading tab', tabId, requestData);
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: requestData,
						success: function(response) {
							console.log('GFSB: tab response', response);
							if (response && response.success) {
								$('#gfsb-modal-tab-content').html(response.data.content);
								gfsbApplyModalLayout();
								setTimeout(function() {
									gfsbSetupInsertButton();
								}, 100);
							} else {
								var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Unable to load shortcode builder tab.';
								console.error('GFSB: tab response indicated failure', response);
								alert(errorMsg);
							}
						},
						error: function(xhr, status, error) {
							console.error('GFSB: tab request failed', status, error, xhr.responseText);
							alert('<?php echo esc_js( __( 'The shortcode builder could not be loaded. Check the console for details.', 'gf-shortcode-builder' ) ); ?>');
						}
					});
				}

				function insertShortcodeIntoEditor(tabId) {
					// Get the generated shortcode from the result textarea
					var shortcode = $('#gfsb-modal-tab-content').find('textarea[readonly]').first().val();
					
					if (!shortcode) {
						alert('<?php esc_html_e( 'Please generate a shortcode first.', 'gf-shortcode-builder' ); ?>');
						return;
					}

					var $textarea = window.gfsbGetNotificationTextarea ? window.gfsbGetNotificationTextarea() : null;
					if (!$textarea || !$textarea.length) {
						console.error('GFSB: Notification textarea not found.');
						alert('<?php esc_html_e( 'Unable to insert the shortcode. Please paste it manually.', 'gf-shortcode-builder' ); ?>');
						return;
					}

					var editorId = $textarea.attr('id') || '_gform_setting_message';
					console.log('GFSB: inserting shortcode into editor', editorId);
					var inserted = false;

					// Try TinyMCE (Visual tab)
					if (typeof tinymce !== 'undefined' || typeof tinyMCE !== 'undefined') {
						var candidateEditor = null;
						if (editorId) {
							candidateEditor = (typeof tinymce !== 'undefined' ? tinymce.get(editorId) : null) || (typeof tinyMCE !== 'undefined' ? tinyMCE.get(editorId) : null);
						}
						if (!candidateEditor && typeof tinymce !== 'undefined' && tinymce.editors) {
							for (var i = 0; i < tinymce.editors.length; i++) {
								var ed = tinymce.editors[i];
								if ($textarea && ed && ed.getElement && $textarea.length && ed.getElement() === $textarea.get(0)) {
									candidateEditor = ed;
									break;
								}
							}
						}
						if (!candidateEditor && typeof tinymce !== 'undefined' && tinymce.activeEditor) {
							var active = tinymce.activeEditor;
							if ($textarea && active.getElement && active.getElement() === $textarea.get(0)) {
								candidateEditor = active;
							}
						}
						if (!candidateEditor && typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
							var activeAlt = tinyMCE.activeEditor;
							if ($textarea && activeAlt.getElement && activeAlt.getElement() === $textarea.get(0)) {
								candidateEditor = activeAlt;
							}
						}
						if (candidateEditor && !candidateEditor.isHidden()) {
							candidateEditor.focus();
							candidateEditor.execCommand('mceInsertContent', false, shortcode);
							if (candidateEditor.fire) {
								candidateEditor.fire('change');
							}
							inserted = true;
						}
					}

					// Fallback to textarea (Text tab)
					if (!inserted) {
						var textareaEl = $textarea.get(0);
						var value = textareaEl.value;
						if (typeof textareaEl.selectionStart === 'number') {
							var start = textareaEl.selectionStart;
							var end = textareaEl.selectionEnd;
							textareaEl.value = value.substring(0, start) + shortcode + value.substring(end);
							textareaEl.selectionStart = textareaEl.selectionEnd = start + shortcode.length;
						} else {
							textareaEl.value = value + shortcode;
						}
						jQuery(textareaEl).trigger('change');
						inserted = true;
					}

					if (inserted) {
						closeModal();
					} else {
						console.error('GFSB: Unable to locate notification editor. Last known ID', editorId, 'textarea', $textarea);
						alert('<?php esc_html_e( 'Unable to insert the shortcode. Please paste it manually.', 'gf-shortcode-builder' ); ?>');
					}
				}

				function closeModal() {
					$('#gfsb-notification-modal-content').fadeOut(200);
					$('#gfsb-notification-overlay').fadeOut(200);
					currentTabId = null;
				}

				// Close modal on close button click
				$(document).on('click', '#gfsb-close-modal', function() {
					closeModal();
				});

				// Close modal on overlay click
				$(document).on('click', '#gfsb-notification-overlay', function() {
					closeModal();
				});

				// Close modal on escape key
				$(document).on('keydown', function(e) {
					if (e.key === 'Escape' && $('#gfsb-notification-modal-content').is(':visible')) {
						closeModal();
					}
				});
			});
		</script>
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
		<style>
			.gform-settings-panel .gform-settings-panel__content {
				padding: 1rem 1rem 0 !important;
			}
			.gfsb-tab-toggle-panel {
				margin-bottom: 20px;
				padding: 16px;
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				border-radius: 4px;
			}
			.gfsb-tab-toggle-panel h5 {
				margin: 0 0 6px;
				font-size: 14px;
				font-weight: 600;
			}
			.gfsb-tab-toggle-panel p {
				margin: 0 0 12px;
				color: #50575e;
				font-size: 13px;
			}
			.gfsb-tab-toggle-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
				gap: 10px;
			}
			.gfsb-tab-toggle {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 10px 12px;
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
			}
			.gfsb-tab-toggle input[type="checkbox"] {
				margin: 0;
				width: 18px;
				height: 18px;
			}
			.gfsb-tab-toggle span {
				font-size: 13px;
				color: #1d2327;
			}
			.gfsb-accordions {
				display: flex;
				flex-direction: column;
				gap: 8px;
				margin-bottom: 24px;
			}
			.gfsb-accordion {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				overflow: hidden;
				transition: all 0.2s ease;
			}
			.gfsb-accordion[data-tab-enabled="0"] {
				display: none;
			}
			.gfsb-accordion.dragging {
				opacity: 0.5;
			}
			.gfsb-accordion.drag-over {
				border-top: 3px solid #3858e9;
			}
			.gfsb-accordion-header {
				display: flex;
				align-items: center;
				gap: 12px;
				padding: 16px;
				cursor: pointer;
				background: #fff;
				border: none;
				width: 100%;
				text-align: left;
				font-size: 14px;
				font-weight: 600;
				color: #1d2327;
				transition: background 0.2s ease;
			}
			.gfsb-accordion-header:hover {
				background: #f6f7f7;
			}
			.gfsb-accordion-drag-handle {
				cursor: grab;
				color: #a7aaad;
				font-size: 18px;
				line-height: 1;
				display: flex;
				align-items: center;
				flex-shrink: 0;
			}
			.gfsb-accordion-drag-handle:hover {
				color: #1d2327;
			}
			.gfsb-accordion.dragging .gfsb-accordion-drag-handle {
				cursor: grabbing;
			}
			.gfsb-accordion-title {
				flex: 1;
			}
			.gfsb-accordion-icon {
				width: 20px;
				height: 20px;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #50575e;
				transition: transform 0.2s ease;
				flex-shrink: 0;
			}
			.gfsb-accordion.open .gfsb-accordion-icon {
				transform: rotate(180deg);
			}
			.gfsb-accordion-content {
				max-height: 0;
				overflow: hidden;
				transition: max-height 0.3s ease;
				border-top: 1px solid #dcdcde;
			}
			.gfsb-accordion.open .gfsb-accordion-content {
				max-height: 5000px;
			}
			.gfsb-accordion-content-inner {
				padding: 20px;
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
			.gfsb-accordion-order-notice {
				display: none;
				padding: 8px 12px;
				background: #d7f1ff;
				border-left: 4px solid #3858e9;
				margin-bottom: 16px;
				font-size: 13px;
				color: #1d2327;
				border-radius: 4px;
			}
			.gfsb-accordion-order-notice.show {
				display: block;
			}
		</style>

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

		<script type="text/javascript">
			var gfsbDraggedElement = null;
			var gfsbToggleNonce = '<?php echo wp_create_nonce( 'gfsb_toggle_tabs' ); ?>';

			function gfsbSetAccordionVisibility(tabId, enabled) {
				var accordion = document.querySelector('.gfsb-accordion[data-tab="' + tabId + '"]');
				if (accordion) {
					accordion.setAttribute('data-tab-enabled', enabled ? '1' : '0');
					accordion.style.display = enabled ? '' : 'none';
				}
			}

			function gfsbInitTabToggles() {
				var toggles = document.querySelectorAll('.gfsb-tab-toggle-input');
				if (!toggles.length) {
					return;
				}
				toggles.forEach(function(toggle) {
					var tabId = toggle.getAttribute('data-tab');
					gfsbSetAccordionVisibility(tabId, toggle.checked);
					toggle.addEventListener('change', function(event) {
						var target = event.target;
						var id = target.getAttribute('data-tab');
						var isEnabled = target.checked;
						gfsbSetAccordionVisibility(id, isEnabled);

						var data = {
							action: 'gfsb_save_tab_visibility',
							nonce: gfsbToggleNonce,
							tab_id: id,
							enabled: isEnabled ? '1' : '0'
						};

						jQuery.post(ajaxurl, data);
					});
				});
			}

			function gfsbToggleAccordion(event, tabId) {
				event.preventDefault();
				event.stopPropagation();
				
				var accordion = event.currentTarget.closest('.gfsb-accordion');
				accordion.classList.toggle('open');
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
				document.querySelectorAll('.gfsb-accordion').forEach(function(accordion) {
					accordion.classList.remove('drag-over');
				});

				// Save the new order
				gfsbSaveAccordionOrder();
			}

			function gfsbDragOver(event) {
				if (event.preventDefault) {
					event.preventDefault();
				}
				event.dataTransfer.dropEffect = 'move';
				
				var target = event.currentTarget;
				if (target !== gfsbDraggedElement && target.classList.contains('gfsb-accordion')) {
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
				
				if (gfsbDraggedElement !== target && target.classList.contains('gfsb-accordion')) {
					var container = document.getElementById('gfsb-accordions-container');
					var allAccordions = Array.from(container.children);
					var draggedIndex = allAccordions.indexOf(gfsbDraggedElement);
					var targetIndex = allAccordions.indexOf(target);
					
					if (draggedIndex < targetIndex) {
						target.parentNode.insertBefore(gfsbDraggedElement, target.nextSibling);
					} else {
						target.parentNode.insertBefore(gfsbDraggedElement, target);
					}
				}
				
				target.classList.remove('drag-over');
				
				return false;
			}

			function gfsbSaveAccordionOrder() {
				var accordions = document.querySelectorAll('.gfsb-accordion');
				var accordionOrder = [];
				
				accordions.forEach(function(accordion) {
					accordionOrder.push(accordion.getAttribute('data-tab'));
				});

				// Save via AJAX
				var data = {
					action: 'gfsb_save_tab_order',
					nonce: '<?php echo wp_create_nonce( 'gfsb_tab_order' ); ?>',
					tab_order: accordionOrder
				};

				jQuery.post(ajaxurl, data, function(response) {
					if (response.success) {
						// Show success notice
						var notice = document.getElementById('gfsb-accordion-order-notice');
						notice.classList.add('show');
						setTimeout(function() {
							notice.classList.remove('show');
						}, 3000);
					}
				});
			}

			// Initialize tab toggles
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', gfsbInitTabToggles);
			} else {
				gfsbInitTabToggles();
			}
		</script>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'GF_Shortcode_Builder', 'maybe_load_for_ajax' ), 11 );
add_action( 'gform_loaded', array( 'GF_Shortcode_Builder', 'get_instance' ) );
