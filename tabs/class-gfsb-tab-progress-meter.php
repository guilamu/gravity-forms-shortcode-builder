<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSB_Tab_Progress_Meter {

	public function get_title() {
		return __( 'Progress Meter', 'gf-shortcode-builder' );
	}

	public function should_display() {
		return class_exists( 'GW_Progress_Meter' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		?>
		<p class="gform-settings-description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Display a visual progress meter showing progression towards a goal based on form entries. Perfect for fundraising campaigns, event registrations, or any goal-based tracking.', 'gf-shortcode-builder' ); ?>
		</p>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_form"><?php esc_html_e( 'Select Form', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_pm_form" class="gform-input gform-input--large fullwidth-input" onchange="gfsbProgressMeterUpdate()">
					<option value=""><?php esc_html_e( 'Select a form', 'gf-shortcode-builder' ); ?></option>
					<?php foreach ( $forms as $form_item ) : ?>
						<option value="<?php echo esc_attr( $form_item['id'] ); ?>" <?php selected( $form['id'], $form_item['id'] ); ?>>
							<?php echo esc_html( $form_item['title'] ) . ' (ID: ' . esc_html( $form_item['id'] ) . ')'; ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'The form to track progress for.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_goal"><?php esc_html_e( 'Goal', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="number" id="gfsb_pm_goal" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., 1000', 'gf-shortcode-builder' ); ?>" oninput="gfsbProgressMeterUpdate()" required />
				<p class="gfsb-help-text"><?php esc_html_e( 'The target number you want to reach (e.g., 1000 entries or $5000 raised).', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_field"><?php esc_html_e( 'Field to Track (Optional)', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_pm_field" class="gform-input gform-input--large fullwidth-input" onchange="gfsbProgressMeterUpdate(); gfsbPMFieldChange();">
					<option value=""><?php esc_html_e( 'Count entries (default)', 'gf-shortcode-builder' ); ?></option>
					<option value="payment_amount"><?php esc_html_e( 'Payment Amount (sum of payments)', 'gf-shortcode-builder' ); ?></option>
					<option value="custom"><?php esc_html_e( 'Custom Field (enter field ID)', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'What to track: entry count, payment amounts, or a specific field value.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field" id="gfsb_pm_custom_field_wrapper" style="display:none;">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_custom_field"><?php esc_html_e( 'Custom Field ID', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_pm_custom_field" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., 5', 'gf-shortcode-builder' ); ?>" oninput="gfsbProgressMeterUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Enter the field ID to sum values from (e.g., a number field).', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_status"><?php esc_html_e( 'Entry Status', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_pm_status" class="gform-input gform-input--large fullwidth-input" onchange="gfsbProgressMeterUpdate()">
					<option value="total" selected><?php esc_html_e( 'Total (All entries)', 'gf-shortcode-builder' ); ?></option>
					<option value="unread"><?php esc_html_e( 'Unread', 'gf-shortcode-builder' ); ?></option>
					<option value="starred"><?php esc_html_e( 'Starred', 'gf-shortcode-builder' ); ?></option>
					<option value="spam"><?php esc_html_e( 'Spam', 'gf-shortcode-builder' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Trash', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Which entries to include in the count.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_start"><?php esc_html_e( 'Starting Count (Optional)', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="number" id="gfsb_pm_start" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'Leave blank to start from 0', 'gf-shortcode-builder' ); ?>" oninput="gfsbProgressMeterUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Set a starting count (useful if you want to offset initial progress).', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_count_label"><?php esc_html_e( 'Count Label', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_pm_count_label" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., %s donations or Raised $%s', 'gf-shortcode-builder' ); ?>" value="<?php esc_attr_e( '%s submissions', 'gf-shortcode-builder' ); ?>" oninput="gfsbProgressMeterUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Label for the current count. Use %s as placeholder for the number.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_goal_label"><?php esc_html_e( 'Goal Label', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_pm_goal_label" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., Goal: %s or Target $%s', 'gf-shortcode-builder' ); ?>" value="<?php esc_attr_e( '%s goal', 'gf-shortcode-builder' ); ?>" oninput="gfsbProgressMeterUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Label for the goal. Use %s as placeholder for the goal number.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_pm_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<textarea id="gfsb_pm_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="5" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
				
				<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbProgressMeterCopy()">
					<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
				</button>
				
				<div id="gfsb_pm_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
					<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
				</div>
			</div>
		</div>

		<div class="gform-settings-field" style="background: #f0f6fc; padding: 15px; border-left: 4px solid #3858e9; border-radius: 4px;">
			<h4 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( 'Usage Examples', 'gf-shortcode-builder' ); ?></h4>
			<ul style="margin: 0; padding-left: 20px; color: #50575e;">
				<li><?php esc_html_e( 'Fundraising: Track donations with payment_amount field', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Event registrations: Count total registrations towards capacity', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Petition signatures: Track progress towards signature goal', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Contest entries: Show how many people have entered', 'gf-shortcode-builder' ); ?></li>
			</ul>
		</div>

		<script type="text/javascript">
			function gfsbPMFieldChange() {
				var field = document.getElementById('gfsb_pm_field').value;
				var customWrapper = document.getElementById('gfsb_pm_custom_field_wrapper');
				
				if (field === 'custom') {
					customWrapper.style.display = 'block';
				} else {
					customWrapper.style.display = 'none';
				}
			}

			function gfsbProgressMeterUpdate() {
				var formId = document.getElementById('gfsb_pm_form').value;
				var goal = document.getElementById('gfsb_pm_goal').value.trim();
				var field = document.getElementById('gfsb_pm_field').value;
				var customField = document.getElementById('gfsb_pm_custom_field').value.trim();
				var status = document.getElementById('gfsb_pm_status').value;
				var start = document.getElementById('gfsb_pm_start').value.trim();
				var countLabel = document.getElementById('gfsb_pm_count_label').value.trim();
				var goalLabel = document.getElementById('gfsb_pm_goal_label').value.trim();

				if (formId === '' || goal === '') {
					document.getElementById('gfsb_pm_result').value = '';
					return;
				}

				// Use custom field if selected
				if (field === 'custom' && customField !== '') {
					field = customField;
				} else if (field === 'custom') {
					field = '';
				}

				var shortcode = '[gravityforms id="' + formId + '"';
				shortcode += ' action="meter"';
				shortcode += ' goal="' + goal + '"';
				
				if (field !== '') {
					shortcode += ' field="' + field + '"';
				}
				
				if (status !== 'total') {
					shortcode += ' status="' + status + '"';
				}
				
				if (start !== '') {
					shortcode += ' start="' + start + '"';
				}
				
				if (countLabel !== '' && countLabel !== '%s submissions') {
					shortcode += ' count_label="' + countLabel + '"';
				}
				
				if (goalLabel !== '' && goalLabel !== '%s goal') {
					shortcode += ' goal_label="' + goalLabel + '"';
				}
				
				shortcode += ']';

				document.getElementById('gfsb_pm_result').value = shortcode;
			}

			function gfsbProgressMeterCopy() {
				var textarea = document.getElementById('gfsb_pm_result');
				textarea.select();
				textarea.setSelectionRange(0, 99999);
				try {
					var successful = document.execCommand('copy');
					if(successful) {
						var msg = document.getElementById('gfsb_pm_copy_msg');
						msg.style.display = 'block';
						setTimeout(function(){ msg.style.display = 'none'; }, 2000);
					}
				} catch (err) {
					console.error('Unable to copy', err);
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				gfsbProgressMeterUpdate();
			});
		</script>
		<?php
	}
}
