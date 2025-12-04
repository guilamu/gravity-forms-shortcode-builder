<?php
namespace GFSB\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoreFormDisplay {

	public function get_title() {
		return __( 'Core Form Display', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		include GFSB_PATH . 'views/tabs/core-form-display.php';
	}
}
