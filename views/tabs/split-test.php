<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="gform-settings-description" style="margin-bottom: 20px;">
	<?php esc_html_e( 'Test different versions of your forms to see which converts better. Select 2 or more forms to randomly display to visitors.', 'gf-shortcode-builder' ); ?>
</p>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label"><?php esc_html_e( 'Forms to Test', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<div id="gfsb_split_forms_list">
			<div class="gfsb-form-selector">
				<select class="gform-input gform-input--large gfsb-split-form-select" onchange="gfsbSplitUpdate()">
					<option value=""><?php esc_html_e( 'Select a form', 'gf-shortcode-builder' ); ?></option>
					<?php foreach ( $forms as $form_item ) : ?>
						<?php if ( $form_item['is_active'] ) : ?>
							<option value="<?php echo esc_attr( $form_item['id'] ); ?>" <?php selected( $form['id'], $form_item['id'] ); ?>>
								<?php echo esc_html( $form_item['title'] ) . ' (ID: ' . esc_html( $form_item['id'] ) . ')'; ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button gfsb-remove-split-form" onclick="gfsbRemoveSplitForm(this)" style="display:none; color: #a00;">
					<?php esc_html_e( 'Remove', 'gf-shortcode-builder' ); ?>
				</button>
			</div>
		</div>
		
		<button type="button" class="button button-secondary" onclick="gfsbAddSplitForm()" style="margin-top: 10px;">
			<span class="dashicons dashicons-plus" style="line-height: 1.3;"></span> <?php esc_html_e( 'Add Another Form', 'gf-shortcode-builder' ); ?>
		</button>
		
		<p class="gfsb-help-text"><?php esc_html_e( 'Add at least 2 forms to run a split test. Each form will be randomly displayed to visitors.', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_split_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<textarea id="gfsb_split_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="3" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
		
		<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbSplitCopy()">
			<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
		</button>
		
		<div id="gfsb_split_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
			<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
		</div>
	</div>
</div>

<div class="gform-settings-field" style="background: #f0f6fc; padding: 15px; border-left: 4px solid #3858e9; border-radius: 4px;">
	<h4 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( 'How Split Testing Works', 'gf-shortcode-builder' ); ?></h4>
	<ul style="margin: 0; padding-left: 20px; color: #50575e;">
		<li><?php esc_html_e( 'Each visitor will be randomly shown one of the selected forms', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Gravity Forms tracks views and conversions for each form', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Check your form entries to see which version performs better', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'The more traffic you have, the more accurate your results will be', 'gf-shortcode-builder' ); ?></li>
	</ul>
</div>

<script type="text/javascript">
	function gfsbSplitUpdate() {
		var selects = document.querySelectorAll('.gfsb-split-form-select');
		var formIds = [];
		
		selects.forEach(function(select) {
			var value = select.value.trim();
			if (value !== '') {
				formIds.push(value);
			}
		});

		var removeButtons = document.querySelectorAll('.gfsb-remove-split-form');
		if (selects.length > 1) {
			removeButtons.forEach(function(btn) {
				btn.style.display = 'inline-block';
			});
		} else {
			removeButtons.forEach(function(btn) {
				btn.style.display = 'none';
			});
		}

		if (formIds.length < 2) {
			document.getElementById('gfsb_split_result').value = '';
			return;
		}

		var shortcode = '[gravityforms action="split_test" ids="' + formIds.join(',') + '"]';
		document.getElementById('gfsb_split_result').value = shortcode;
	}

	function gfsbAddSplitForm() {
		var container = document.getElementById('gfsb_split_forms_list');
		var firstSelector = container.querySelector('.gfsb-form-selector');
		var newSelector = firstSelector.cloneNode(true);
		
		newSelector.querySelector('.gfsb-split-form-select').value = '';
		
		container.appendChild(newSelector);
		gfsbSplitUpdate();
	}

	function gfsbRemoveSplitForm(btn) {
		var selector = btn.closest('.gfsb-form-selector');
		selector.remove();
		gfsbSplitUpdate();
	}

	function gfsbSplitCopy() {
		var textarea = document.getElementById('gfsb_split_result');
		textarea.select();
		textarea.setSelectionRange(0, 99999);
		try {
			var successful = document.execCommand('copy');
			if(successful) {
				var msg = document.getElementById('gfsb_split_copy_msg');
				msg.style.display = 'block';
				setTimeout(function(){ msg.style.display = 'none'; }, 2000);
			}
		} catch (err) {
			console.error('Unable to copy', err);
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		gfsbSplitUpdate();
	});
</script>
