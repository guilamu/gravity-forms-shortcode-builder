<?php
namespace GFSB\Tabs;

use GFAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EntryCount {

	public function get_title() {
		return __( 'Entry Count', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		include GFSB_PATH . 'views/tabs/entry-count.php';
	}
}
