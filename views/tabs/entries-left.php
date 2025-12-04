<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="gform-settings-description" style="margin-bottom: 20px;">
	<?php esc_html_e( 'Display the number of entries remaining before a form reaches its entry limit. Perfect for limited offers, event registrations, or contests.', 'gf-shortcode-builder' ); ?>
</p>

<?php if ( ! $has_limit ) : ?>
	<div class="gfsb-warning-box">
		<h4><?php esc_html_e( 'Entry Limits Not Enabled', 'gf-shortcode-builder' ); ?></h4>
		<p style="margin: 0; color: #646970;">
			<?php esc_html_e( 'The current form does not have entry limits enabled. To use the Entries Left shortcode, you need to enable "Limit number of entries" in Form Settings > Restrictions.', 'gf-shortcode-builder' ); ?>
		</p>
	</div>
<?php endif; ?>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_entries_left_form"><?php esc_html_e( 'Select Form', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<select id="gfsb_entries_left_form" class="gform-input gform-input--large fullwidth-input" onchange="gfsbEntriesLeftUpdate()">
			<option value=""><?php esc_html_e( 'Select a form', 'gf-shortcode-builder' ); ?></option>
			<?php foreach ( $forms as $form_item ) : ?>
				<?php 
				$form_has_limit = isset( $form_item['limitEntries'] ) && $form_item['limitEntries'];
				$label_suffix = $form_has_limit ? '' : ' ' . __( '(No limit set)', 'gf-shortcode-builder' );
				?>
				<option value="<?php echo esc_attr( $form_item['id'] ); ?>" <?php selected( $form['id'], $form_item['id'] ); ?>>
					<?php echo esc_html( $form_item['title'] ) . ' (ID: ' . esc_html( $form_item['id'] ) . ')' . esc_html( $label_suffix ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="gfsb-help-text"><?php esc_html_e( 'The form for which you want to display entries remaining. Forms without entry limits will show unlimited entries.', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_entries_left_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<textarea id="gfsb_entries_left_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="3" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
		
		<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbEntriesLeftCopy()">
			<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
		</button>
		
		<div id="gfsb_entries_left_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
			<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
		</div>
	</div>
</div>

<div class="gform-settings-field" style="background: #f0f6fc; padding: 15px; border-left: 4px solid #3858e9; border-radius: 4px;">
	<h4 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( 'Usage Examples', 'gf-shortcode-builder' ); ?></h4>
	<ul style="margin: 0; padding-left: 20px; color: #50575e;">
		<li><?php esc_html_e( 'Create urgency: "Only 23 spots left! Register now!"', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Limited offers: "15 early bird tickets remaining"', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Contest entries: "47 entries left before we close submissions"', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Workshop signups: "3 seats available for this workshop"', 'gf-shortcode-builder' ); ?></li>
	</ul>
</div>

<div class="gform-settings-field" style="background: #fff8e5; padding: 15px; border-left: 4px solid #f0b849; border-radius: 4px;">
	<h4 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( 'How to Enable Entry Limits', 'gf-shortcode-builder' ); ?></h4>
	<ol style="margin: 0; padding-left: 20px; color: #646970;">
		<li><?php esc_html_e( 'Go to your form settings', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Click on "Restrictions" in the left sidebar', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Enable "Limit number of entries"', 'gf-shortcode-builder' ); ?></li>
		<li><?php esc_html_e( 'Set your desired entry limit', 'gf-shortcode-builder' ); ?></li>
	</ol>
</div>

<script type="text/javascript">
	function gfsbEntriesLeftUpdate() {
		var formId = document.getElementById('gfsb_entries_left_form').value;

		if (formId === '') {
			document.getElementById('gfsb_entries_left_result').value = '';
			return;
		}

		var shortcode = '[gravityforms action="entries_left" id="' + formId + '"]';
		document.getElementById('gfsb_entries_left_result').value = shortcode;
	}

	function gfsbEntriesLeftCopy() {
		var textarea = document.getElementById('gfsb_entries_left_result');
		textarea.select();
		textarea.setSelectionRange(0, 99999);
		try {
			var successful = document.execCommand('copy');
			if(successful) {
				var msg = document.getElementById('gfsb_entries_left_copy_msg');
				msg.style.display = 'block';
				setTimeout(function(){ msg.style.display = 'none'; }, 2000);
			}
		} catch (err) {
			console.error('Unable to copy', err);
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		gfsbEntriesLeftUpdate();
	});
</script>
