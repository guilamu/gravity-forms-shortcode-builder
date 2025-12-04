<?php
namespace GFSB\Tabs;

use GFAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SplitTest {

	public function get_title() {
		return __( 'Split Test', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		include GFSB_PATH . 'views/tabs/split-test.php';
	}
}
