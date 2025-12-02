<?php

namespace NGD_THEME\Functions;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdvertisementProduct extends WC_Product  {

	// Set the custom product type
	protected string $product_type = 'advertisement_product';

	public function __construct( $product ) {
		parent::__construct( $product );
	}

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->product_type;
	}

}