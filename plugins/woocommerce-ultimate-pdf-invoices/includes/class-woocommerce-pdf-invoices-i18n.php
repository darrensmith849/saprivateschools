<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://woocommerce-pdf-invoices.welaunch.io
 * @since      1.0.0
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/includes
 * @author     Daniel Barenkamp <support@welaunch.io>
 */
class WooCommerce_PDF_Invoices_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$loaded = load_plugin_textdomain(
			'woocommerce-pdf-invoices',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
