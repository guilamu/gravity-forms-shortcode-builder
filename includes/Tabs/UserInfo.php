<?php
namespace GFSB\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserInfo {

	public function get_title() {
		return __( 'User Information', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		include GFSB_PATH . 'views/tabs/user-info.php';
	}
}
