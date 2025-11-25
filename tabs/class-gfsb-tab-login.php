<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSB_Tab_Login {

	public function get_title() {
		return __( 'Login Form', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		?>
		<p class="gform-settings-description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Display a custom login form on any page or post. Perfect for creating branded login experiences.', 'gf-shortcode-builder' ); ?>
		</p>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_title"><?php esc_html_e( 'Display Title', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_login_title" class="gform-input gform-input--large fullwidth-input" onchange="gfsbLoginUpdate()">
					<option value="true" selected><?php esc_html_e( 'Show', 'gf-shortcode-builder' ); ?></option>
					<option value="false"><?php esc_html_e( 'Hide', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Whether to display the login form title.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_description"><?php esc_html_e( 'Display Description', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<select id="gfsb_login_description" class="gform-input gform-input--large fullwidth-input" onchange="gfsbLoginUpdate()">
					<option value="true"><?php esc_html_e( 'Show', 'gf-shortcode-builder' ); ?></option>
					<option value="false" selected><?php esc_html_e( 'Hide', 'gf-shortcode-builder' ); ?></option>
				</select>
				<p class="gfsb-help-text"><?php esc_html_e( 'Whether to display the login form description.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_logged_in"><?php esc_html_e( 'Logged In Message', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_login_logged_in" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., Welcome back! You are already logged in.', 'gf-shortcode-builder' ); ?>" oninput="gfsbLoginUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Message displayed to users who are already logged in.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_registration"><?php esc_html_e( 'Registration Link Text', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_login_registration" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., Register Now', 'gf-shortcode-builder' ); ?>" oninput="gfsbLoginUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Text for the registration link (defaults to "Register").', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_forgot_password"><?php esc_html_e( 'Forgot Password Link Text', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="text" id="gfsb_login_forgot_password" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., Forgot Password?', 'gf-shortcode-builder' ); ?>" oninput="gfsbLoginUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'Text for the forgot password link (defaults to "Forgot Password").', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_redirect"><?php esc_html_e( 'Redirect URL (Optional)', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<input type="url" id="gfsb_login_redirect" class="gform-input gform-input--large fullwidth-input" placeholder="<?php esc_attr_e( 'e.g., https://example.com/dashboard', 'gf-shortcode-builder' ); ?>" oninput="gfsbLoginUpdate()" />
				<p class="gfsb-help-text"><?php esc_html_e( 'URL to redirect users after successful login. Leave blank to stay on the current page.', 'gf-shortcode-builder' ); ?></p>
			</div>
		</div>

		<div class="gform-settings-field">
			<div class="gform-settings-field__header">
				<label class="gform-settings-label" for="gfsb_login_result"><?php esc_html_e( 'Generated Shortcode', 'gf-shortcode-builder' ); ?></label>
			</div>
			<div class="gform-settings-input__container">
				<textarea id="gfsb_login_result" class="gform-textarea gform-textarea--large fullwidth-input" rows="4" readonly onclick="this.select();" style="font-family:monospace; background:#fafafa;"></textarea>
				
				<button type="button" class="button button-primary fullwidth-input" style="margin-top: 15px; text-align:center;" onclick="gfsbLoginCopy()">
					<?php esc_html_e( 'Copy to Clipboard', 'gf-shortcode-builder' ); ?>
				</button>
				
				<div id="gfsb_login_copy_msg" style="margin-top: 8px; text-align: center; color: #007cba; display:none; font-weight:600;">
					<?php esc_html_e( 'Copied to clipboard!', 'gf-shortcode-builder' ); ?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			function gfsbLoginUpdate() {
				var title = document.getElementById('gfsb_login_title').value;
				var description = document.getElementById('gfsb_login_description').value;
				var loggedIn = document.getElementById('gfsb_login_logged_in').value.trim();
				var registration = document.getElementById('gfsb_login_registration').value.trim();
				var forgotPassword = document.getElementById('gfsb_login_forgot_password').value.trim();
				var redirect = document.getElementById('gfsb_login_redirect').value.trim();

				var shortcode = '[gravityform action="login"';
				shortcode += ' title="' + title + '"';
				shortcode += ' description="' + description + '"';
				
				if (loggedIn !== '') {
					shortcode += ' logged_in_message="' + loggedIn + '"';
				}
				
				if (registration !== '') {
					shortcode += ' registration_link_text="' + registration + '"';
				}
				
				if (forgotPassword !== '') {
					shortcode += ' forgot_password_text="' + forgotPassword + '"';
				}
				
				if (redirect !== '') {
					shortcode += ' redirect="' + redirect + '"';
				}
				
				shortcode += ']';

				document.getElementById('gfsb_login_result').value = shortcode;
			}

			function gfsbLoginCopy() {
				var textarea = document.getElementById('gfsb_login_result');
				textarea.select();
				textarea.setSelectionRange(0, 99999);
				try {
					var successful = document.execCommand('copy');
					if(successful) {
						var msg = document.getElementById('gfsb_login_copy_msg');
						msg.style.display = 'block';
						setTimeout(function(){ msg.style.display = 'none'; }, 2000);
					}
				} catch (err) {
					console.error('Unable to copy', err);
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				gfsbLoginUpdate();
			});
		</script>
		<?php
	}
}
