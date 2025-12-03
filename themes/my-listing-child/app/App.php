<?php

namespace NGD_THEME;

if ( ! defined( 'ABSPATH' ) ) exit;

use NGD_THEME\Front\Front;
use NGD_THEME\Admin\Admin;
use NGD_THEME\Functions\WooCommerce;
use NGD_THEME\Functions\RenewalCron;   // <-- Added
use NGD_THEME\Functions\PaymentWebhook; // <-- Added

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

            // --- AUTOMATION LOADING ---
            new RenewalCron();
            new PaymentWebhook();
            // --------------------------
		}
	}

	public function run_hooks(): void {
		add_filter('mylisting/expiring-listings/days-notice', function( $days ) {
			return 14;
		});
	}
}