<?php

// use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://woocommerce-pdf-invoices.db-dzine.de
 * @since      1.0.0
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/admin
 * @author     Daniel Barenkamp <contact@db-dzine.de>
 */
class WooCommerce_PDF_Invoices_Admin extends WooCommerce_PDF_Invoices {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of this plugin.
	 */
	protected $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function load_redux()
	{
        if(!is_admin() || !current_user_can('administrator')){
            return false;
        }

	    // Load the theme/plugin options
	    if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/options-init.php' ) ) {
	        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/options-init.php';
	    }
	}

    /**
     * Init
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://www.welaunch.io
     * @return  boolean
     */
    public function init()
    {
        global $woocommerce_pdf_invoices_options;

        if(!is_admin() || !current_user_can('administrator')){
            $woocommerce_pdf_invoices_options = get_option('woocommerce_pdf_invoices_options');
        }

        $this->upload_dir = $this->get_uploads_dir( 'pdf-invoices' );
        $this->options = $woocommerce_pdf_invoices_options;

		$this->invoiceNumberPrefix = $this->get_option('invoiceNumberPrefix');
		$this->invoiceNumberSuffix = $this->get_option('invoiceNumberSuffix');
		$this->invoiceNumberDateFormat = $this->get_option('invoiceNumberDateFormat');
		$this->invoiceNumberPadLength = (int) $this->get_option('invoiceNumberPadLength');


		if($this->get_option('emailAttachmentsPerProduct')) {

			add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'product_write_panel_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'product_write_panel' ) );
			add_action( 'woocommerce_process_product_meta',     array( $this, 'product_save_data' ), 10, 2 );
		}

    }

   /**
     * Enqueue Admin Styles
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://www.welaunch.io
     * @return  boolean
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name.'-admin', plugin_dir_url(__FILE__).'css/woocommerce-pdf-invoices-admin.css', array(), $this->version, 'all');
    }

    /**
     * Enqueue Admin Scripts
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://www.welaunch.io
     * @return  boolean
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name.'-admin', plugin_dir_url(__FILE__).'js/woocommerce-pdf-invoices-admin.js', array('jquery'), $this->version, true);
    }

	public function add_custom_order_status_actions_button( $actions, $order ) {

		$order_id = $order->get_id();

		$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
		$invoice_exists = file_exists($invoice);
		if(!$invoice_exists) {
			return $actions;
		}

		$query_params = array( 
			'download_invoice_pdf' => 'true', 
			'_wpnonce' => wp_create_nonce( 'download_pdf_invoice' ),
			'order' => $order_id,
		);

        // Set the action button
        $actions['invoice'] = array(
            'url'       => add_query_arg( $query_params ),
            'name'      => __( 'Download Invoice', 'woocommerce-pdf-invoices' ),
            'action'    => "download-invoice",
        );

	    return $actions;
	}

	protected function get_uploads_dir( $subdir = '' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		if ( '' != $subdir ) {
			$upload_dir = $upload_dir . '/' . $subdir;
		}
		return $upload_dir;
	}

    public function add_pdf_invoice_meta_box()
    {
    	$invoice_number = '';
		if($this->get_option('invoiceNumberEnable')) {
    		$order_id = get_the_ID();
    		$invoice_number = ' ' . get_post_meta($order_id, 'invoice_number', true);
    	}

		$screen   = 'shop_order';
		// $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		// ? wc_get_page_screen_id( 'shop-order' )
		// : 'shop_order';

		$context  = 'side';
		$priority = 'high';
		add_meta_box(
			'wc_invoice_pdfs_upload_metabox',
			__( 'PDF Invoice', 'woocommerce-pdf-invoices' ) . $invoice_number,
			array( $this, 'create_pdf_invoice_meta_box' ),
			$screen,
			$context,
			$priority
		);
    }

	/**
	 * create_pdf_invoice_meta_box.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function create_pdf_invoice_meta_box() 
	{
		$html = '';
		$order_id = get_the_ID();

		$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
		$invoice_exists = file_exists($invoice);

		if($invoice_exists) {

			$query_params = array( 
				'download_invoice_pdf' => 'true', 
				'_wpnonce' => wp_create_nonce( 'download_pdf_invoice' ),
				'order' => $order_id,
			);
			$html .= '<a target="_blank" href="' . add_query_arg( $query_params ) . '">' . __('Download Invoice (PDF)', 'woocommerce-pdf-invoices') . '</a>';
			$html .= '<hr><button type="submit" class="button button-primary" name="create_pdf_invoice" value="' . $order_id . '">' . __('Update Invoice', 'woocommerce-pdf-invoices') . '</button>';

			

			if($this->get_option('showOldInvoices')) {

				$html .= '<hr><div stlye="font-size: 80%;"><b>' . __('Old Invoices', 'woocommerce-pdf-invoices') . '</b><br>';

				$upload_path = wp_upload_dir()['baseurl'];
				$files = scandir($this->upload_dir . '/');
				$i = 1;
				foreach ($files as $file) {
					
				    if (strpos($file, (string) $order_id) !== false && $file != $order_id . '.pdf') {
				         $html .= '<a href="' . $upload_path . '/pdf-invoices/' . $file . '" target="_blank">' . sprintf( __('Old Invoice %d', 'woocommerce-pdf-invoices'), $i ) . '</a> | ';
				         $i++;
				    }

				}

				$html .= '</div>';
			}

			

		} else {
			$html .= '<p><em>' . __( 'No files uploaded.', 'woocommerce-pdf-invoices' ) . '</em></p>';
		
			$html .= '<hr><button type="submit" class="button button-primary" name="create_pdf_invoice" value="' . $order_id . '">' . __('Create Invoice', 'woocommerce-pdf-invoices') . '</button>';
		}

		echo $html;
	}

	public function add_preview_frame()
	{
		$shop_order_ids = get_posts(array(
		    'fields'          => 'ids',
		    'posts_per_page'  => 20,
		    'post_type' => 'shop_order',
		    'post_status' => 'any'
		));
		?>
		<div id="pdf-invoices-preview-frame-container" class="pdf-invoices-preview-frame-container">
			<div class="pdf-invoices-preview-frame-header">
				<label for="order_id"><?php _e('Select Order ID', 'woocommerce-pdf-invoices') ?></label>
				<select name="order_id" id="pdf-invoices-preview-order-id">
					<?php foreach ($shop_order_ids as $key => $shop_order_id) {
						if($key == 0) {
							echo '<option value="' . $shop_order_id . '" selected>' . $shop_order_id . '</option>';
							continue;
						}
						echo '<option value="' . $shop_order_id . '">' . $shop_order_id . '</option>';
					} ?>
				</select>
			</div>
			<div id="pdf-invoices-preview-spinner" class="pdf-invoices-preview-spinner">
				<i class="el el-refresh el-spin"></i>
			</div>
			<iframe id="pdf-invoices-preview-frame" src="" width="100%" height="100%" class="pdf-invoices-preview-frame">

			</iframe>
		</div>
		<div id="pdf-invoices-preview-frame-overlay" class="pdf-invoices-preview-frame-overlay"></div>
		<?php
	}

   	public function add_bulk_action_download_invoice($bulk_actions)
	{
        $bulk_actions['download_invoice'] = __( 'Download Invoice', 'woocommerce-pdf-invoices');
        return $bulk_actions;
    }

    public function handle_bulk_action_download_invoice($redirect_to, $action, $order_ids)
    {
        $checkAction = strpos($action, 'assign_printer_');
        
        if ( $action !== 'download_invoice') {
            return $redirect_to;
        }
		
		$files = array();
		foreach ($order_ids as $order_id) {
			$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
			$invoice_exists = file_exists($invoice);

			$order = wc_get_order($order_id);
			if(!$order) {
				continue;
			}

			$customFileName = $this->get_option('customFileName');
			if(empty($customFileName)) {
				$customFileName = '{{invoice_number}}';
			}
			
			$invoice_number = get_post_meta($order_id, 'invoice_number', true );
			if(empty($invoice_number)) {
				$invoice_number = $order_id;
			}

	    	$vars = array(
	    		'order_id' => $order_id,
				'invoice_number' => $invoice_number,
				'date' => $order->get_date_created(),
				'customer_id' => $customer_id,
				'billing_first_name' => $order->get_billing_first_name(),
				'billing_last_name' => $order->get_billing_last_name(),
	    	);

	    	$filename = $this->replace_vars($customFileName, $vars) . '.pdf';

			if($invoice_exists) {
				$files[$filename] = $invoice;
			}
		}

		if(empty($files)) {
			wp_die(__('No Invoice PDFs found', 'woocommerce-pdf-invoices'));
		}

		$zipname = 'invoices-' . time() . '.zip';
		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		foreach ($files as $filename => $file) {
			$zip->addFile($file, basename( str_replace('/', '-', $filename) ));
		}
		$zip->close();

		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.$zipname);
		header('Content-Length: ' . filesize($zipname));
		readfile($zipname);

        exit();
    }

    public function create_manual_invoice_number($order_id)
    {
	    if( function_exists('is_checkout') && !is_checkout() 
	        && get_post_type($order_id) == 'shop_order')
	    {

			$order = new WC_Order($order_id);
			$orderStatus = $order->get_status();

			$notAllowedStatus = array('auto-draft');
			if(in_array($orderStatus, $notAllowedStatus)) {
				return false; 
			}

			$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
			$invoice_exists = file_exists($invoice);
			if($invoice_exists) {
				return false;
			}
		    
		    $this->create_invoice_number($order_id, true);
		}
    }

    public function create_invoice_number_when_not_exists( $attachments, $status, $order )
    {
    	if(!$order) {
    		return;
    	}

    	if(!is_object($order)) {
    		return;
    	}

		$this->create_invoice_number( $order->get_id() );
    }

    public function create_invoice_number( $order_id ) 
    {
    	if(!$this->get_option('invoiceNumberEnable')) {
    		return false;
		}

    	$order = wc_get_order($order_id);
    	if(!$order) {
    		return;
    	}

		$orderStatus = 'wc-' . $order->get_status();
		$allowedStatuses = $this->get_option('createInvoiceStatus');
		if(!empty($allowedStatuses)) {
			if(!in_array($orderStatus, $allowedStatuses)) {	
				return;
			}
		}

		$invoiceNumberExists = get_post_meta($order_id, 'invoice_number', true);
		if(!empty($invoiceNumberExists)) {
			return;
		}

		if(!method_exists($order, 'get_customer_id')) {
			return;
		}

		$customerId = $order->get_customer_id();
		$rolesExclude = $this->get_option('rolesExclude');
		if(is_array($rolesExclude) && !empty($rolesExclude) && !empty($customerId)) {

			$currentUserRole = $this->get_user_role($customerId);
			if(in_array($currentUserRole, $rolesExclude)) {
				return false;
			}
		}

		$rolesInclude = $this->get_option('rolesInclude');
		if(is_array($rolesInclude) && !empty($rolesInclude)) {

			// No Invoice for guest users. 
			if(empty($customerId)) {
				return false;
			}

			$currentUserRole = $this->get_user_role($customerId);
			if(!in_array($currentUserRole, $rolesInclude)) {
				return false;
			}
		}

		$excludeWCB2BGroups = $this->get_option('excludeWCB2BGroups');
		if(is_array($excludeWCB2BGroups) && !empty($excludeWCB2BGroups) && !empty($customerId)) {

			$currentWCB2BGroup = get_user_meta($customerId, 'wcb2b_group', true);
			if(in_array($currentWCB2BGroup, $excludeWCB2BGroups)) {
				return false;
			}
		}

		$invoice_numbers = get_option('woocommerce_pdf_invoices_numbers');
		
    	if(empty($invoice_numbers)) {
    		$invoice_numbers = array();
    	}

		$invoiceNumberStart = $this->get_option('invoiceNumberStart');

    	if(empty($this->invoiceNumberDateFormat) || ( (strpos($this->invoiceNumberPrefix, '{{date}}') === false) && (strpos($this->invoiceNumberSuffix, '{{date}}') === false)) ) {
    		$date = '';
    	} else {
    		$createdDate = $order->get_date_created();
    		if(!$createdDate) {
				return;
			}
			$date = $createdDate->format( $this->invoiceNumberDateFormat );
    	}

    	if(isset($invoice_numbers[$date])) {
			$invoice_id = array_values(array_slice($invoice_numbers[$date], -1))[0] + 1;
    	} else {
    		$invoice_id = $invoiceNumberStart;
    	}

    	$invoice_numbers[$date][] = $invoice_id;

    	$vars = array(
    		'order_id' => $order->get_id(),
    		'customer_id' => $order->get_user_id(),
    		'date' => $date,
    		'invoice_id' => $invoice_id,
    	);

    	$invoice_number = $this->invoice_num($invoice_id, $vars);

    	$order->update_meta_data( 'invoice_number', $invoice_number );

	    $order->save();

    	$invoice_numbers = update_option('woocommerce_pdf_invoices_numbers', $invoice_numbers);

    }

    protected function invoice_num($input, $vars = array()) {

    	$input = (string) $input;
	    if ($this->invoiceNumberPadLength < strlen($input))
	        wp_die('<strong>Invoice Number Pad Length</strong> cannot be less than or equal to the length of <strong>Invoice number</strong> to generate invoice number');

	    $prefix = $this->replace_vars($this->invoiceNumberPrefix, $vars);
	    $suffix = $this->replace_vars($this->invoiceNumberSuffix, $vars);

	    if (!empty($prefix) || !empty($suffix)) {
	        return sprintf("%s%s%s", $prefix, str_pad($input, $this->invoiceNumberPadLength, "0", STR_PAD_LEFT), $suffix);
	    }

	    return str_pad($input, $this->invoiceNumberPadLength, "0", STR_PAD_LEFT);
	}

	public function regenerate_invoice_numbers()
	{
    	if(!isset($_GET['regenerate-invoice-numbers']) || !is_admin()) {
    		return false;
		}

		if(!current_user_can('manage_options')) {
			return false;
		}

		$invoice_numbers = array();

        $query = new WC_Order_Query( array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'ASC',
        ) );
        $orders = $query->get_orders();

        if(empty($orders)) {
            wp_die('No Orders found');
        }

		$allowedStatuses = $this->get_option('createInvoiceStatus');
		$invoiceNumberStart = $this->get_option('invoiceNumberStart');
		
        foreach ($orders as $order) {

			$orderStatus = 'wc-' . $order->get_status();
			if(!empty($allowedStatuses)) {
				if(!in_array($orderStatus, $allowedStatuses)) {	
					continue;
				}
			}

        	if(empty($this->invoiceNumberDateFormat) || ( (strpos($this->invoiceNumberPrefix, '{{date}}') === false) && (strpos($this->invoiceNumberSuffix, '{{date}}') === false)) ) {
        		$date = '';
        	} else {
        		$date = $order->get_date_created()->format( $this->invoiceNumberDateFormat );
        	}

        	if(isset($invoice_numbers[$date])) {       		
    			$invoice_id = array_values(array_slice($invoice_numbers[$date], -1))[0] + 1;
        	} else {
        		$invoice_id = $invoiceNumberStart;
        	}

        	$invoice_numbers[$date][] = $invoice_id;
	
        	$vars = array(
        		'order_id' => $order->get_id(),
        		'customer_id' => $order->get_user_id(),
        		'date' => $date,
        		'invoice_id' => $invoice_id,
        	);

        	$invoice_number = $this->invoice_num($invoice_id, $vars);

    	    $order->update_meta_data( 'invoice_number', $invoice_number );

    	    $order->save();

        }
        
    	update_option('woocommerce_pdf_invoices_numbers', $invoice_numbers);
    	wp_die('Invoice numbers updated');
	}

	public function replace_vars($string, $vars)
	{
		if (preg_match_all("/{{(.*?)}}/", $string, $m)) {
			foreach ($m[1] as $i => $var) {
				$string = str_replace($m[0][$i], $vars[$var], $string);
			}
	    }

		return $string;
	}


	/**
	 * Add Input Field Tab
	 * @author DB-Dzine
	 * @version 1.0.3
	 * @since   1.0.3
	 * @link    http://www.welaunch.io
	 */
	public function product_write_panel_tab() {
		echo '<li class="woocommerce_pdf_invoices"><a href="#woocommerce_pdf_invoices"><span>' . __( 'PDF Invoices', 'woocommerce-ultimate-tabs' ) . '</span></a></li>';
	}


	/**
	 * Panel Input Tab Fields
	 * @author DB-Dzine
	 * @version 1.0.3
	 * @since   1.0.3
	 * @link    http://www.welaunch.io
	 */
	public function product_write_panel() 
	{
		global $post;
		// the product

		// pull the custom tab data out of the database
		$documents = maybe_unserialize( get_post_meta( $post->ID, 'woocommerce_pdf_invoices_documents', true ) );

		if(!is_array($documents)) {
			$documents = array();
		}
		?>

		<div id="woocommerce_pdf_invoices" class="panel wc-metaboxes-wrapper woocommerce_options_panel">

			<h3 style="padding: 5px 15px;"><?php esc_html_e('Email Attachments', 'woocommerce-pdf-invoices') ?></h3>

			<?php for ($i=0; $i < 4; $i++) { ?>			

            <div class="woocommerce-pdf-invoices-upload-image-container">
            	<?php
            	$file_name = esc_html('Choose a file', 'woocommerce-pdf-invoices');
            	$file_id = "";
            	if(isset($documents[$i]) && !empty($documents[$i])) {
            		$file_id = $documents[$i];
            		$file_name = get_the_title( $file_id );
            	}

                ?>
                <a href="#" class="woocommerce-pdf-invoices-upload-image"><?php echo $file_name; ?></a>
                <input type="hidden" name="woocommerce_pdf_invoices_documents[<?php echo $i ?>]" value="<?php echo $file_id ?>">
                <a href="#" class="woocommerce-pdf-invoices-remove-image"><?php esc_html_e('Remove File', 'wordpress-form-wizard') ?></a>
            </div>
            

        	<?php } ?>

		</div>

		<?php
	}


	/**
	 * Save Input Fields
	 * @author DB-Dzine
	 * @version 1.0.3
	 * @since   1.0.3
	 * @link    http://www.welaunch.io
	 */
	public function product_save_data( $post_id, $post ) {

		$tab_data = array();

		if(isset($_POST['woocommerce_pdf_invoices_documents']) && !empty($_POST['woocommerce_pdf_invoices_documents']))	{

			$product = wc_get_product($post_id);
			if($product) {

				$documents = array_filter( $_POST['woocommerce_pdf_invoices_documents'] );
				$product->update_meta_data( 'woocommerce_pdf_invoices_documents', $documents );
				$product->save();

			}
		}
	}
}