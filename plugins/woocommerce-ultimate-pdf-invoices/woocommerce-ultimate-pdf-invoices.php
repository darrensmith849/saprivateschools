<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              http://www.welaunch.io
 * @since             1.0.
 * @package           WooCommerce_PDF_Invoices
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce PDF Invoices
 * Plugin URI:        https://welaunch.io/plugins/woocommerce-pdf-invoices/
 * Description:       Generate PDF Invoices for WooCommerce with Ease.
 * Version:           1.5.0
 * Author:            weLaunch 
 * Author URI:        https://welaunch.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-pdf-invoices
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-pdf-invoices-activator.php
 */
function activate_woocommerce_pdf_invoices() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-pdf-invoices-activator.php';
	WooCommerce_PDF_Invoices_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-pdf-invoices-deactivator.php
 */
function deactivate_woocommerce_pdf_invoices() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-pdf-invoices-deactivator.php';
	WooCommerce_PDF_Invoices_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_pdf_invoices' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_pdf_invoices' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-pdf-invoices.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_pdf_invoices() {

	$plugin_data = get_plugin_data( __FILE__ );
	$version = $plugin_data['Version'];

	$plugin = new WooCommerce_PDF_Invoices($version);
	$plugin->run();

	return $plugin;

}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php') && (is_plugin_active('redux-dev-master/redux-framework.php') || is_plugin_active('redux-framework/redux-framework.php') ||  is_plugin_active('welaunch-framework/welaunch-framework.php') ) ){
	$WooCommerce_PDF_Invoices = run_woocommerce_pdf_invoices();
} else {
	add_action( 'admin_notices', 'woocommerce_pdf_invoices_installed_notice' );
}

function woocommerce_pdf_invoices_installed_notice()
{
	?>
    <div class="error">
      <p><?php _e( 'WooCommerce PDF Invoices requires the WooCommerce and weLaunch Framework plugin. Please install or activate them before: https://www.welaunch.io/updates/welaunch-framework.zip', 'woocommerce-pdf-invoices'); ?></p>
    </div>
    <?php
}