<?php

namespace NGD_THEME;

if ( ! defined( 'ABSPATH' ) ) exit;

use NGD_THEME\Front\Front;
use NGD_THEME\Admin\Admin;
use NGD_THEME\Functions\WooCommerce;

class App {

	public function __construct( $run_hooks = false ) {
		if ( $run_hooks ) {
			$this->run_hooks();
			new Front($run_hooks);
			if (class_exists( 'WC_Product')) {
				new WooCommerce($run_hooks);
			}
			if (is_admin()) {
				new Admin($run_hooks);
			}
		}
	}

	public function run_hooks(): void {
		add_filter('mylisting/expiring-listings/days-notice', function( $days ) {
			return 14;
		});
	}
}