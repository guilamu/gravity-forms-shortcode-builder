<?php
namespace GFSB\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login {

	public function get_title() {
		return __( 'Login Form', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		include GFSB_PATH . 'views/tabs/login.php';
	}
}
