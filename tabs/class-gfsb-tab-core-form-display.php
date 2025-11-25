<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSB_Tab_Core_Form_Display {

	public function get_title() {
		return __( 'Core Form Display', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		?>
		<p class="gform-settings-description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Build an embed shortcode to display your form on any page or post.', 'gf-shortcode-builder' ); ?>
		</p>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label"><?php esc_html_e( 'Form ID', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_core_form_id" class="gform-input gform-input--large fullwidth-input" value="<?php echo esc_attr( $form['id'] ); ?>" readonly />
				<p class="gfsb-help-text"><?php esc_html_e( 'The ID of the current form.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_title"><?php esc_html_e( 'Display Title', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_core_title" class="gform-input gform-input--large fullwidth-input" onchange="gfsbCoreUpdate()">
					<option value="true"><?php esc_html_e( 'Show', 'gf-shortcode-builder' ); ?></option>
					<option value="false" selected><?php esc_html_e( 'Hide', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Whether to display the form title.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_description"><?php esc_html_e( 'Display Description', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_core_description" class="gform-input gform-input--large fullwidth-input" onchange="gfsbCoreUpdate()">
					<option value="true"><?php esc_html_e( 'Show', 'gf-shortcode-builder' ); ?></option>
					<option value="false" selected><?php esc_html_e( 'Hide', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Whether to display the form description.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_ajax"><?php esc_html_e( 'Enable AJAX', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_core_ajax" class="gform-input gform-input--large fullwidth-input" onchange="gfsbCoreUpdate()">
					<option value="true" selected><?php esc_html_e( 'Yes', 'gf-shortcode-builder' ); ?></option>
					<option value="false"><?php esc_html_e( 'No', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Submit the form without reloading the page.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_tabindex"><?php esc_html_e( 'Tab Index (Optional)', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="number" id="gfsb_core_tabindex" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'Leave blank for default', 'gf-shortcode-builder' ); ?>" oninput="gfsbCoreUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Starting tab index for the form (not recommended for accessibility).', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_field_values"><?php esc_html_e( 'Field Values (Optional)', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_core_field_values" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., parameter1=value1&parameter2=value2', 'gf-shortcode-builder' ); ?>" oninput="gfsbCoreUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Dynamically populate form fields using URL parameter format.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_core_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<textarea id="gfsb_core_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="3" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
				
				<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbCoreCopy()">
					<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
				</button>
				
				<div id="gfsb_core_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
					<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			function gfsbCoreUpdate() {
				var formId = document.getElementById('gfsb_core_form_id').value;
				var title = document.getElementById('gfsb_core_title').value;
				var description = document.getElementById('gfsb_core_description').value;
				var ajax = document.getElementById('gfsb_core_ajax').value;
				var tabindex = document.getElementById('gfsb_core_tabindex').value.trim();
				var fieldValues = document.getElementById('gfsb_core_field_values').value.trim();

				var shortcode = '[gravityform id="' + formId + '"';
				shortcode += ' title="' + title + '"';
				shortcode += ' description="' + description + '"';
				shortcode += ' ajax="' + ajax + '"';
				
				if (tabindex !== '') {
					shortcode += ' tabindex="' + tabindex + '"';
				}
				
				if (fieldValues !== '') {
					shortcode += ' field_values="' + fieldValues + '"';
				}
				
				shortcode += ']';

				document.getElementById('gfsb_core_result').value = shortcode;
			}

			function gfsbCoreCopy() {
				var textarea = document.getElementById('gfsb_core_result');
				textarea.select();
				textarea.setSelectionRange(0, 99999);
				try {
					var successful = document.execCommand('copy');
					if(successful) {
						var msg = document.getElementById('gfsb_core_copy_msg');
						msg.style.display = 'block';
						setTimeout(function(){ msg.style.display = 'none'; }, 2000);
					}
				} catch (err) {
					console.error('Unable to copy', err);
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				gfsbCoreUpdate();
			});
		</script>
		<?php
	}
}
