<?php
namespace GFSB\Tabs;

use GFCommon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Conditional {

	public function get_title() {
		return __( 'Conditional Shortcodes', 'gf-shortcode-builder' );
	}

	public function render( $form ) {
		$is_advanced_active = class_exists( 'GF_Advanced_Conditional_Shortcodes' ) || function_exists( 'gf_advanced_conditional_shortcodes' );
		
		include GFSB_PATH . 'views/tabs/conditional.php';
	}
}
