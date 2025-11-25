<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSB_Tab_Conditional {

	public function get_title() {
		return __( 'Conditional Shortcodes', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$is_advanced_active = class_exists( 'GF_Advanced_Conditional_Shortcodes' ) || function_exists( 'gf_advanced_conditional_shortcodes' );
		?>
		<p class="gform-settings-description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Select a field, an operator, and a value to generate your conditional shortcode.', 'gf-shortcode-builder' ); ?>
		</p>

		<?php if ( $is_advanced_active ) : ?>
			<div class="gform-settings-field" id="gf_csb_relation_wrapper">
				<div class="gform-settings-field__header">
					<label class="gform-settings-label" for="gf_csb_relation"><?php esc_html_e( 'Relation', 'gf-shortcode-builder' ); ?></label>
				</div>
				<div class="gform-settings-input__container">
					<select id="gf_csb_relation" class="gform-input gform-input--large fullwidth-input" onchange="gfCSBUpdate()">
						<option value="and"><?php esc_html_e( 'Match ALL conditions (AND)', 'gf-shortcode-builder' ); ?></option>
						<option value="or"><?php esc_html_e( 'Match ANY condition (OR)', 'gf-shortcode-builder' ); ?></option>
					</select>
				</div>
			</div>
		<?php endif; ?>

		<div id="gf_csb_rows_container">
			<div class="gf-csb-row gform-settings-field" data-id="1" style="border-bottom: 1px dashed #e6e6e6; padding-bottom: 20px;">
				
				<div style="margin-bottom: 15px;">
					<label class="gform-settings-label"><?php esc_html_e( 'Field', 'gf-shortcode-builder' ); ?></label>
					<select class="gform-input gform-input--large fullwidth-input gf-csb-field-select" onchange="gfCSBUpdate()">
						<option value=""><?php esc_html_e( 'Select a Field', 'gf-shortcode-builder' ); ?></option>
						<?php
						foreach ( $form['fields'] as $field ) {
							if ( in_array( $field->type, array( 'page', 'section', 'html' ) ) ) {
								continue;
							}
							$label = GFCommon::get_label( $field );
							$merge_tag = '{' . $label . ':' . $field->id . '}';
							echo '<option value="' . esc_attr( $merge_tag ) . '">' . esc_html( $label ) . ' (ID: ' . esc_html( $field->id ) . ')</option>';
						}
						?>
					</select>
				</div>

				<div style="margin-bottom: 15px;">
					<label class="gform-settings-label"><?php esc_html_e( 'Operator', 'gf-shortcode-builder' ); ?></label>
					<select class="gform-input gform-input--large fullwidth-input gf-csb-operator-select" onchange="gfCSBUpdate()">
						<option value="is">is</option>
						<option value="isnot">isnot</option>
						<option value="greater_than">greater_than</option>
						<option value="less_than">less_than</option>
						<option value="contains">contains</option>
						<option value="starts_with">starts_with</option>
						<option value="ends_with">ends_with</option>
						<?php if ( $is_advanced_active ) : ?>
							<option value="pattern"><?php esc_html_e( 'Match Regex (Advanced)', 'gf-shortcode-builder' ); ?></option>
						<?php endif; ?>
					</select>
				</div>

				<div>
					<label class="gform-settings-label"><?php esc_html_e( 'Value', 'gf-shortcode-builder' ); ?></label>
					<input type="text" class="gform-input gform-input--large fullwidth-input gf-csb-value-input" placeholder="<?php esc_attr_e( 'Enter value...', 'gf-shortcode-builder' ); ?>" oninput="gfCSBUpdate()" />
				</div>
				
				<button type="button" class="button button-small gf-csb-remove-row" style="display:none; margin-top:10px; color: #a00;" onclick="gfCSBRemoveRow(this)"><?php esc_html_e( 'Remove Condition', 'gf-shortcode-builder' ); ?></button>
			</div>
		</div>

		<?php if ( $is_advanced_active ) : ?>
			<div style="margin-bottom: 24px; text-align: right;">
				<button type="button" class="button button-secondary" onclick="gfCSBAddRow()">
					<span class="dashicons dashicons-plus" style="line-height: 1.3;"></span> <?php esc_html_e( 'Add Condition', 'gf-shortcode-builder' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gf_csb_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<textarea id="gf_csb_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="4" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
				
				<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfCSBCopy()"><?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?></button>
				
				<div id="gf_csb_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
					<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			function gfCSBUpdate() {
				var rows = document.querySelectorAll('.gf-csb-row');
				var relation = document.getElementById('gf_csb_relation') ? document.getElementById('gf_csb_relation').value : 'and';
				var shortcode = '[gravityforms action="conditional"';

				if (rows.length > 1) {
					shortcode += ' relation="' + relation + '"';
				}

				var validConditions = 0;

				rows.forEach(function(row, index) {
					var field = row.querySelector('.gf-csb-field-select').value;
					var operator = row.querySelector('.gf-csb-operator-select').value;
					var value = row.querySelector('.gf-csb-value-input').value;

					if (field) {
						validConditions++;
						var suffix = (index === 0) ? '' : '_' + (index + 1);
						
						shortcode += ' merge_tag' + suffix + '="' + field + '"';
						shortcode += ' condition' + suffix + '="' + operator + '"';
						shortcode += ' value' + suffix + '="' + value + '"';
					}
				});

				shortcode += ']\n';
				shortcode += '   Insert your content here based on the condition.\n';
				shortcode += '[/gravityforms]';

				if (validConditions === 0) {
					document.getElementById('gf_csb_result').value = '';
				} else {
					document.getElementById('gf_csb_result').value = shortcode;
				}
			}

			function gfCSBAddRow() {
				var container = document.getElementById('gf_csb_rows_container');
				var firstRow = container.querySelector('.gf-csb-row');
				var newRow = firstRow.cloneNode(true);

				newRow.querySelector('.gf-csb-field-select').value = '';
				newRow.querySelector('.gf-csb-value-input').value = '';
				newRow.querySelector('.gf-csb-operator-select').selectedIndex = 0;
				newRow.querySelector('.gf-csb-remove-row').style.display = 'inline-block';

				container.appendChild(newRow);
				gfCSBUpdate();
			}

			function gfCSBRemoveRow(btn) {
				var row = btn.closest('.gf-csb-row');
				row.remove();
				gfCSBUpdate();
			}

			function gfCSBCopy() {
				var textarea = document.getElementById('gf_csb_result');
				textarea.select();
				textarea.setSelectionRange(0, 99999);
				try {
					var successful = document.execCommand('copy');
					if(successful) {
						var msg = document.getElementById('gf_csb_copy_msg');
						msg.style.display = 'block';
						setTimeout(function(){ msg.style.display = 'none'; }, 2000);
					}
				} catch (err) {
					console.error('Unable to copy', err);
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				gfCSBUpdate();
			});
		</script>
		<?php
	}
}
