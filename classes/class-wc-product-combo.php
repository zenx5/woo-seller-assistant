<?php

defined( 'ABSPATH' ) || exit;

class WC_Product_Grouped extends WC_Product {
    protected $extra_data = array(
		'children' => array(),
	);

    public function get_type() {
		return 'combo';
	}

    public function get_children( $context = 'view' ) {
		return $this->get_prop( 'children', $context );
	}

    public function set_children( $children ) {
		$this->set_prop( 'children', array_filter( wp_parse_id_list( (array) $children ) ) );
	}
}