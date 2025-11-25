<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSB_Tab_Entry_Count {

	public function get_title() {
		return __( 'Entry Count', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		?>
		<p class="gform-settings-description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Display the number of entries submitted for a form. Great for showing social proof or interest levels.', 'gf-shortcode-builder' ); ?>
		</p>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_entry_count_form"><?php esc_html_e( 'Select Form', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_entry_count_form" class="gform-input gform-input--large fullwidth-input" onchange="gfsbEntryCountUpdate()">
					<option value=""><?php esc_html_e( 'Select a form', 'gf-shortcode-builder' ); ?></option>
					<?php foreach ( $forms as $form_item ) : ?>
						<option value="<?php echo esc_attr( $form_item['id'] ); ?>" <?php selected( $form['id'], $form_item['id'] ); ?>>
							<?php echo esc_html( $form_item['title'] ) . ' (ID: ' . esc_html( $form_item['id'] ) . ')'; ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'The form for which you want to display the entry count.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_entry_count_status"><?php esc_html_e( 'Entry Status', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_entry_count_status" class="gform-input gform-input--large fullwidth-input" onchange="gfsbEntryCountUpdate()">
					<option value="total" selected><?php esc_html_e( 'Total (All entries)', 'gf-shortcode-builder' ); ?></option>
					<option value="unread"><?php esc_html_e( 'Unread', 'gf-shortcode-builder' ); ?></option>
					<option value="starred"><?php esc_html_e( 'Starred', 'gf-shortcode-builder' ); ?></option>
					<option value="spam"><?php esc_html_e( 'Spam', 'gf-shortcode-builder' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Trash', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Type of entries to count (defaults to "total").', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_entry_count_format"><?php esc_html_e( 'Number Format', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_entry_count_format" class="gform-input gform-input--large fullwidth-input" onchange="gfsbEntryCountUpdate()">
					<option value="comma" selected><?php esc_html_e( 'Comma (e.g., 1,234)', 'gf-shortcode-builder' ); ?></option>
					<option value="decimal"><?php esc_html_e( 'Decimal (e.g., 1.234)', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'How to format the displayed number.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_entry_count_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<textarea id="gfsb_entry_count_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="3" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
				
				<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbEntryCountCopy()">
					<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
				</button>
				
				<div id="gfsb_entry_count_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
					<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
				</div>
			</div>
		</div>

		<div class="gform-settings-field" style="background: #f0f6fc; padding: 15px; border-left: 4px solid #3858e9; border-radius: 4px;">
			<h4 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( 'Usage Examples', 'gf-shortcode-builder' ); ?></h4>
			<ul style="margin: 0; padding-left: 20px; color: #50575e;">
				<li><?php esc_html_e( 'Show social proof: "Join 1,234 other subscribers!"', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Display event registrations: "523 people registered"', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Track contest entries: "15,678 entries submitted"', 'gf-shortcode-builder' ); ?></li>
				<li><?php esc_html_e( 'Monitor form engagement across your site', 'gf-shortcode-builder' ); ?></li>
			</ul>
		</div>

		<script type="text/javascript">
			function gfsbEntryCountUpdate() {
				var formId = document.getElementById('gfsb_entry_count_form').value;
				var status = document.getElementById('gfsb_entry_count_status').value;
				var format = document.getElementById('gfsb_entry_count_format').value;

				if (formId === '') {
					document.getElementById('gfsb_entry_count_result').value = '';
					return;
				}

				var shortcode = '[gravityforms action="entry_count"';
				shortcode += ' id="' + formId + '"';
				shortcode += ' status="' + status + '"';
				shortcode += ' format="' + format + '"';
				shortcode += ']';

				document.getElementById('gfsb_entry_count_result').value = shortcode;
			}

			function gfsbEntryCountCopy() {
				var textarea = document.getElementById('gfsb_entry_count_result');
				textarea.select();
				textarea.setSelectionRange(0, 99999);
				try {
					var successful = document.execCommand('copy');
					if(successful) {
						var msg = document.getElementById('gfsb_entry_count_copy_msg');
						msg.style.display = 'block';
						setTimeout(function(){ msg.style.display = 'none'; }, 2000);
					}
				} catch (err) {
					console.error('Unable to copy', err);
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				gfsbEntryCountUpdate();
			});
		</script>
		<?php
	}
}
