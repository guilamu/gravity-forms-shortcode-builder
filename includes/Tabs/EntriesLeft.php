<?php
namespace GFSB\Tabs;

use GFAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EntriesLeft {

	public function get_title() {
		return __( 'Entries Left', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		$has_limit = isset( $form['limitEntries'] ) && $form['limitEntries'];
		
		include GFSB_PATH . 'views/tabs/entries-left.php';
	}
}
