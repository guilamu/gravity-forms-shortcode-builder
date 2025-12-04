<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="gform-settings-description" style="margin-bottom: 20px;">
	<?php esc_html_e( 'Display information about the current user or a specific user on your page or post.', 'gf-shortcode-builder' ); ?>
</p>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_user_id"><?php esc_html_e( 'User ID (Optional)', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<input type="number" id="gfsb_user_id" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'Leave blank for current user', 'gf-shortcode-builder' ); ?>" oninput="gfsbUserInfoUpdate()" />
		<p class="gfsb-help-text"><?php esc_html_e( 'The ID of the user to display information for. Leave blank to display info for the currently logged-in user.', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_user_key"><?php esc_html_e( 'User Meta Key', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<select id="gfsb_user_key" class="gform-input gform-input--large fullwidth-input" onchange="gfsbUserInfoUpdate(); gfsbUserKeyChange();">
			<option value=""><?php esc_html_e( 'Select a user meta key', 'gf-shortcode-builder' ); ?></option>
			<optgroup label="<?php esc_attr_e( 'Basic User Data', 'gf-shortcode-builder' ); ?>">
				<option value="user_login"><?php esc_html_e( 'Username (user_login)', 'gf-shortcode-builder' ); ?></option>
				<option value="user_email"><?php esc_html_e( 'Email (user_email)', 'gf-shortcode-builder' ); ?></option>
				<option value="display_name"><?php esc_html_e( 'Display Name (display_name)', 'gf-shortcode-builder' ); ?></option>
				<option value="user_nicename"><?php esc_html_e( 'Nice Name (user_nicename)', 'gf-shortcode-builder' ); ?></option>
			</optgroup>
			<optgroup label="<?php esc_attr_e( 'Profile Information', 'gf-shortcode-builder' ); ?>">
				<option value="nickname"><?php esc_html_e( 'Nickname (nickname)', 'gf-shortcode-builder' ); ?></option>
				<option value="first_name"><?php esc_html_e( 'First Name (first_name)', 'gf-shortcode-builder' ); ?></option>
				<option value="last_name"><?php esc_html_e( 'Last Name (last_name)', 'gf-shortcode-builder' ); ?></option>
				<option value="description"><?php esc_html_e( 'Biographical Info (description)', 'gf-shortcode-builder' ); ?></option>
			</optgroup>
			<optgroup label="<?php esc_attr_e( 'Website & Social', 'gf-shortcode-builder' ); ?>">
				<option value="user_url"><?php esc_html_e( 'Website (user_url)', 'gf-shortcode-builder' ); ?></option>
			</optgroup>
			<optgroup label="<?php esc_attr_e( 'Other', 'gf-shortcode-builder' ); ?>">
				<option value="user_registered"><?php esc_html_e( 'Registration Date (user_registered)', 'gf-shortcode-builder' ); ?></option>
				<option value="custom"><?php esc_html_e( 'Custom Meta Key', 'gf-shortcode-builder' ); ?></option>
			</optgroup>
		</select>
		<p class="gfsb-help-text"><?php esc_html_e( 'The user meta key to retrieve (e.g., nickname, first_name, last_name).', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field" id="gfsb_user_custom_key_wrapper" style="display:none;">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_user_custom_key"><?php esc_html_e( 'Custom Meta Key', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<input type="text" id="gfsb_user_custom_key" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'Enter custom meta key', 'gf-shortcode-builder' ); ?>" oninput="gfsbUserInfoUpdate()" />
		<p class="gfsb-help-text"><?php esc_html_e( 'Enter the custom user meta key you want to retrieve.', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_user_output"><?php esc_html_e( 'Output Format', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<select id="gfsb_user_output" class="gform-input gform-input--large fullwidth-input" onchange="gfsbUserInfoUpdate()">
			<option value="raw"><?php esc_html_e( 'Raw (JSON)', 'gf-shortcode-builder' ); ?></option>
			<option value="csv"><?php esc_html_e( 'CSV (Comma-Separated)', 'gf-shortcode-builder' ); ?></option>
			<option value="list"><?php esc_html_e( 'List (Formatted HTML)', 'gf-shortcode-builder' ); ?></option>
		</select>
		<p class="gfsb-help-text"><?php esc_html_e( 'How the data should be formatted when displayed.', 'gf-shortcode-builder' ); ?></p>
	</div>
</div>

<div class="gform-settings-field">
	<div class="gform-settings-field__header">
		<label class="gform-settings-label" for="gfsb_user_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
	</div>
	<div class="gform-settings-input__container">
		<textarea id="gfsb_user_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="3" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
		
		<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbUserInfoCopy()">
			<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
		</button>
		
		<div id="gfsb_user_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
			<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
		</div>
	</div>
</div>

<script type="text/javascript">
	function gfsbUserKeyChange() {
		var key = document.getElementById('gfsb_user_key').value;
		var customWrapper = document.getElementById('gfsb_user_custom_key_wrapper');
		
		if (key === 'custom') {
			customWrapper.style.display = 'block';
		} else {
			customWrapper.style.display = 'none';
		}
	}

	function gfsbUserInfoUpdate() {
		var userId = document.getElementById('gfsb_user_id').value.trim();
		var key = document.getElementById('gfsb_user_key').value;
		var customKey = document.getElementById('gfsb_user_custom_key').value.trim();
		var output = document.getElementById('gfsb_user_output').value;

		if (key === 'custom' && customKey !== '') {
			key = customKey;
		}

		if (key === '' || key === 'custom') {
			document.getElementById('gfsb_user_result').value = '';
			return;
		}

		var shortcode = '[gravityforms action="user"';
		
		if (userId !== '') {
			shortcode += ' id="' + userId + '"';
		}
		
		shortcode += ' key="' + key + '"';
		shortcode += ' output="' + output + '"';
		shortcode += ']';

		document.getElementById('gfsb_user_result').value = shortcode;
	}

	function gfsbUserInfoCopy() {
		var textarea = document.getElementById('gfsb_user_result');
		textarea.select();
		textarea.setSelectionRange(0, 99999);
		try {
			var successful = document.execCommand('copy');
			if(successful) {
				var msg = document.getElementById('gfsb_user_copy_msg');
				msg.style.display = 'block';
				setTimeout(function(){ msg.style.display = 'none'; }, 2000);
			}
		} catch (err) {
			console.error('Unable to copy', err);
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		gfsbUserInfoUpdate();
	});
</script>
