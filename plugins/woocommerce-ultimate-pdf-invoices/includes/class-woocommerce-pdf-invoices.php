<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://woocommerce-pdf-invoices.welaunch.io
 * @since      1.0.0
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/includes
 * @author     Daniel Barenkamp <support@welaunch.io>
 */
class WooCommerce_PDF_Invoices {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WooCommerce_PDF_Invoices_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */

	public function __construct($version) {

		$this->plugin_name = 'woocommerce-pdf-invoices';
		$this->version = $version;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WooCommerce_PDF_Invoices_Loader. Orchestrates the hooks of the plugin.
	 * - WooCommerce_PDF_Invoices_i18n. Defines internationalization functionality.
	 * - WooCommerce_PDF_Invoices_Admin. Defines all hooks for the admin area.
	 * - WooCommerce_PDF_Invoices_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pdf-invoices-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pdf-invoices-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pdf-invoices-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-pdf-invoices-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-pdf-invoices-generator.php';

	    // Load MPDF library
     	if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php' ) && !class_exists('\Mpdf\Mpdf') ) {
	        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
	    }

		$this->loader = new WooCommerce_PDF_Invoices_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WooCommerce_PDF_Invoices_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WooCommerce_PDF_Invoices_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->plugin_admin = new WooCommerce_PDF_Invoices_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'plugins_loaded', $this->plugin_admin, 'load_redux' );
		$this->loader->add_action( 'init', $this->plugin_admin, 'init', 1);
        $this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles', 20);
        $this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts', 20);
		$this->loader->add_filter( 'woocommerce_admin_order_actions', $this->plugin_admin, 'add_custom_order_status_actions_button', 50, 2);
		$this->loader->add_action( 'add_meta_boxes', $this->plugin_admin, 'add_pdf_invoice_meta_box' );
		$this->loader->add_action( 'admin_footer', $this->plugin_admin, 'add_preview_frame' );

		$this->loader->add_action( 'woocommerce_order_status_changed', $this->plugin_admin, 'create_invoice_number', 50, 1 );
		$this->loader->add_filter( 'woocommerce_email_attachments', $this->plugin_admin, 'create_invoice_number_when_not_exists', 5, 3 );
		$this->loader->add_action( 'wp_insert_post', $this->plugin_admin, 'create_invoice_number', 5, 1 );
		$this->loader->add_action( 'wp_insert_post', $this->plugin_admin, 'create_manual_invoice_number', 10, 1 );

        $this->loader->add_filter( 'bulk_actions-edit-shop_order', $this->plugin_admin, 'add_bulk_action_download_invoice', 20, 1 );
        $this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $this->plugin_admin, 'handle_bulk_action_download_invoice', 10, 3 );
        
        if(isset($_GET['regenerate-invoice-numbers'])) {
            $this->loader->add_action( 'init', $this->plugin_admin, 'regenerate_invoice_numbers' );
        }

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$this->generator = new WooCommerce_PDF_Invoices_Generator( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'init', $this->generator, 'init', 20 );

		$this->plugin_public = new WooCommerce_PDF_Invoices_Public( $this->get_plugin_name(), $this->get_version(), $this->generator);

		$this->loader->add_action( 'init', $this->plugin_public, 'init', 20);
		$this->loader->add_action( 'init', $this->plugin_public,   'customer_download_pdf', 99, 1 );

		$this->loader->add_action( 'woocommerce_thankyou', $this->plugin_public,   'add_files_upload_form_to_thankyou_and_myaccount_page', 99, 1 );
		$this->loader->add_action( 'woocommerce_view_order', $this->plugin_public, 'add_files_upload_form_to_thankyou_and_myaccount_page', 99, 1 );

		$this->loader->add_action( 'woocommerce_order_status_changed', $this->plugin_public, 'create_pdf_invoice_automatically', 99, 1 );
		$this->loader->add_action( 'wp_insert_post', $this->plugin_public, 'create_pdf_invoice_manually', 10, 1 );

		$this->loader->add_filter( 'woocommerce_email_attachments', $this->plugin_public, 'add_files_to_email_attachments', 99, 3 );
		$this->loader->add_filter( 'woocommerce_order_get_tax_totals', $this->plugin_public, 'show_tax_rates', 99, 2 );			

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WooCommerce_PDF_Invoices_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Gets options
	 *
	 * @since    1.0.0
	 */
    protected function get_option($option)
    {
    	if(!is_array($this->options)) {
    		return false;
    	}
    	
    	if(!array_key_exists($option, $this->options))
    	{
    		return false;
    	}
    	return $this->options[$option];
    }

	protected function validate_order($order_id)
	{
	    // $order = new \WC_Order($order_id);
	    // $user_meta = get_user_meta($order->get_user_id());
	    // if($user_meta)
	    //     return true;
	    return true;
	}
	
	/**
	 * Return the current user role
	 * @author Daniel Barenkamp
	 * @version 1.0.0
	 * @since   1.0.0
	 * @link    https://www.welaunch.io
	 * @return  [type]                       [description]
	 */
	protected function get_user_role($userId)
	{
		$user = get_userdata($userId);
		if(!$user) {
			return false;
		}
		
		$user_roles = $user->roles;
		if(empty($user_roles)) {
			return false;
		}

		$user_role = array_shift($user_roles);

		return $user_role;
	}

}
