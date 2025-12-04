<?php
namespace GFSB\Tabs;

use GFAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProgressMeter {

	public function get_title() {
		return __( 'Progress Meter', 'gf-shortcode-builder' );
	}

	public function should_display() {
		return class_exists( 'GW_Progress_Meter' );
	}

	public function render( $form ) {
		$forms = GFAPI::get_forms();
		include GFSB_PATH . 'views/tabs/progress-meter.php';
	}
}
