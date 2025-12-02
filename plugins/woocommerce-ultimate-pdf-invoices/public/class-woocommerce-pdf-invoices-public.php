<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://woocommerce-pdf-invoices.db-dzine.de
 * @since      1.0.0
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_PDF_Invoices
 * @subpackage WooCommerce_PDF_Invoices/public
 * @author     Daniel Barenkamp <contact@db-dzine.de>
 */
class WooCommerce_PDF_Invoices_Public extends WooCommerce_PDF_Invoices {

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
	 * options of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $options
	 */
	protected $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $generator) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->generator = $generator;
	}
	
	/**
	 * Inits the print products
	 *
	 * @since    1.0.0
	 */
    public function init()
    {

		global $woocommerce_pdf_invoices_options;

		$this->options = $woocommerce_pdf_invoices_options;

		if (!$this->get_option('enable')) {
			return false;
		}

		$this->upload_dir = $this->get_uploads_dir( 'pdf-invoices' );
		if ( ! file_exists( $this->upload_dir ) ) {
			mkdir( $this->upload_dir, 0755, true );
		}

		$user_id = get_current_user_id();
		if(!$user_id) {
			return false;
		}

		if(isset($_POST['create_pdf_invoice']) && !empty($_POST['create_pdf_invoice'])) {
			$order = wc_get_order(intval($_POST['create_pdf_invoice']));
			$customer_id = $order->get_customer_id();
			if(!$this->is_user_role('administrator', $user_id) && !$this->is_user_role('shop_manager', $user_id) && ($customer_id !== $user_id) ) {
				return false;
			}

			$this->generator->setup_data($_POST['create_pdf_invoice']);
			$this->generator->create_pdf($this->upload_dir);
		}

		if(isset($_GET['create_pdf_invoice']) && !empty($_GET['create_pdf_invoice'])) {

			$order = wc_get_order(intval($_GET['create_pdf_invoice']));
			if(!$order) {
				return false;
			}

			$customer_id = $order->get_customer_id();

			if(!$this->is_user_role('administrator', $user_id) && !$this->is_user_role('shop_manager', $user_id) && ($customer_id !== $user_id) ) {
				return false;
			}

			$this->generator->setup_data($_GET['create_pdf_invoice']);
			$this->generator->create_pdf($this->upload_dir, true);
		}
    }

	/**
	 * add_files_to_email_attachments.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	public function add_files_to_email_attachments( $attachments, $status, $order ) {

		if(!$this->get_option('generalAttachToMail')) {
			return false;
		}

		if ( ! $order instanceof WC_Order ) {
			return $attachments;
		}

		$this->create_pdf_invoice_automatically($order->get_id());

		$allowed_statuses = $this->get_option('generalAttachToMailStatus');
		if(!$allowed_statuses) {
			$allowed_statuses = array( 
				'new_order', 
				'customer_invoice', 
				'customer_processing_order', 
				'customer_completed_order', 
				'customer_refunded_order',
				'customer_partially_refunded_order'
			);
		}

		if(in_array('customer_refunded_order', $allowed_statuses)) {
			$allowed_statuses[] = 'customer_partially_refunded_order';
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

		if($status == "customer_refunded_order" || $status == "customer_partially_refunded_order") {
			$this->generator->setup_data($order->get_id());
			if(!$this->generator->create_pdf($this->upload_dir, false, true)) {
				return false;
			}
		}

		if( isset( $status ) && in_array ( $status, $allowed_statuses ) ) {

			$order_id = $order->get_id();
			$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
			$invoice_exists = file_exists($invoice);
			if(!$invoice_exists) {
				return $attachments;
			}
			$attachments[] = $invoice;
		}

		$additionalPDF1 = $this->get_option('generalAttachToMailAdditionalPDF1');
		if(	isset($additionalPDF1['id']) && !empty($additionalPDF1['id'])) {
			
			$additionalPDF1 = get_attached_file($additionalPDF1['id']);
			if(!empty($additionalPDF1)) {
				$attachments[] = $additionalPDF1;
			}			
		}

		$additionalPDF2 = $this->get_option('generalAttachToMailAdditionalPDF2');
		if(	isset($additionalPDF2['id']) && !empty($additionalPDF2['id'])) {
			
			$additionalPDF2 = get_attached_file($additionalPDF2['id']);
			if(!empty($additionalPDF2)) {
				$attachments[] = $additionalPDF2;
			}			
		}

		$additionalPDF3 = $this->get_option('generalAttachToMailAdditionalPDF3');
		if(	isset($additionalPDF3['id']) && !empty($additionalPDF3['id'])) {
			
			$additionalPDF3 = get_attached_file($additionalPDF3['id']);
			if(!empty($additionalPDF3)) {
				$attachments[] = $additionalPDF3;
			}			
		}

		if($this->get_option('emailAttachmentsPerProduct')) {

			foreach ( $order->get_items() as $item_id => $item_values ) {

			    // Product_id
			    $product_id = $item_values->get_product_id();
				$documents = maybe_unserialize( get_post_meta( $product_id, 'woocommerce_pdf_invoices_documents', true ) );

				if(is_array($documents)) {
					$documents = array_filter($documents);
					if(!empty($documents)) {
						foreach($documents as $document) {
							$documentPath = get_attached_file($document);
							if(!empty($documentPath)) {
								$attachments[] = $documentPath;
							}	
						}
					}

				} 	
			}
		}

		return $attachments;
	}

	/**
	 * add_files_to_order.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function create_pdf_invoice_automatically( $order_id ) 
	{
		if(!$this->get_option('generalAutomatic')) {
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

		if ( ! file_exists( $this->upload_dir ) ) {
			mkdir( $this->upload_dir, 0755, true );
		}

		$this->generator->setup_data($order_id);
		if(!$this->generator->create_pdf($this->upload_dir)) {
			return false;
		}

		return true;
	}

	public function create_pdf_invoice_manually($order_id)
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

	        $this->create_pdf_invoice_automatically($order_id, true);
	    }
	    
	}

	public function customer_download_pdf()
	{
		if ( isset( $_GET['download_invoice_pdf'] ) && isset( $_GET['_wpnonce'] ) && ( false !== wp_verify_nonce( $_GET['_wpnonce'], 'download_pdf_invoice' ) ) ) {

			$order_id = isset( $_GET['order'] ) ? $_GET['order'] : '';
			if(empty($order_id)) {
				return false;
			}

			$user_id = get_current_user_id();
			$order = wc_get_order($order_id);
			$guestOrderValid = false;

			// Guest user check
			if(!$user_id) {

				$order_key          = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
				if ( !hash_equals( $order->get_order_key(), $order_key ) ) {
					return false;
				}
				$guestOrderValid = true;
			}

			$customer_id = $order->get_customer_id();
			if(!$this->is_user_role('administrator', $user_id) && !$this->is_user_role('shop_manager', $user_id) && ($customer_id !== $user_id) && !$guestOrderValid) {
				return false;
			}

			$invoice = $this->upload_dir . '/' . $order_id . '.pdf';
			$invoice_exists = file_exists($invoice);
			if(!$invoice_exists) {
				return false;
			}
			
			$disposition = 'attachment';
			if($this->get_option('generalRenderInvoice')) {
				$disposition = 'inline';
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

			// 
			header( "Expires: 0" );
			header('Content-Type: application/pdf');
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: private", false );
			header( 'Content-disposition: ' . $disposition . '; filename=' . $filename );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: ". filesize( $invoice ) );
			readfile( $invoice );
			exit();
		}
	}

	/**
	 * add_files_upload_form_to_thankyou_and_myaccount_page.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function add_files_upload_form_to_thankyou_and_myaccount_page( $order_id ) 
	{
		if(!$this->get_option('generalShowInvoice')) {
			return false;
		}

		$invoice_exists = file_exists($this->upload_dir . '/' . $order_id . '.pdf');
		if(!$invoice_exists) {
			return false;
		}

		$html = '<h2 class="pdf-invoice-title">' . __('Invoice (PDF)', 'woocommerce-pdf-invoices') . '</h2>';
		$query_params = array( 
			'download_invoice_pdf' => 'true', 
			'_wpnonce' => wp_create_nonce( 'download_pdf_invoice' ),
			'order' => $order_id,
		);
		$html .= '<a class="woocommerce-pdf-invoice-download pdf-invoice" target="_blank" href="' . add_query_arg( $query_params ) . '">' . __('Download Invoice (PDF)', 'woocommerce-pdf-invoices') . '</a>';

		$additionalPDF1 = $this->get_option('generalAttachToMailAdditionalPDF1');
		if(	isset($additionalPDF1['id']) && !empty($additionalPDF1['id'])) {
			$title = $additionalPDF1['url'];
			if(isset($additionalPDF1['title']) && !empty($additionalPDF1['title'])) {
				$title = $additionalPDF1['title'];
			}
			$html .= '<br><br><a class="woocommerce-pdf-invoice-download additional-pdf-1" target="_blank" href="' . $additionalPDF1['url'] . '">' . $title . '</a>';
		}

		$additionalPDF2 = $this->get_option('generalAttachToMailAdditionalPDF2');
		if(	isset($additionalPDF2['id']) && !empty($additionalPDF2['id'])) {
			$title = $additionalPDF2['url'];
			if(isset($additionalPDF2['title']) && !empty($additionalPDF2['title'])) {
				$title = $additionalPDF2['title'];
			}
			$html .= '<br><a class="woocommerce-pdf-invoice-download additional-pdf-2" target="_blank" href="' . $additionalPDF2['url'] . '">' . $title . '</a>';
		}

		$additionalPDF3 = $this->get_option('generalAttachToMailAdditionalPDF3');
		if(	isset($additionalPDF3['id']) && !empty($additionalPDF3['id'])) {
			$title = $additionalPDF3['url'];
			if(isset($additionalPDF3['title']) && !empty($additionalPDF3['title'])) {
				$title = $additionalPDF3['title'];
			}
			$html .= '<br><a class="woocommerce-pdf-invoice-download additional-pdf-3" target="_blank" href="' . $additionalPDF3['url'] . '">' . $title . '</a>';
		}

		if($this->get_option('emailAttachmentsPerProduct')) {

			$order = wc_get_order( $order_id );
			if($order) {
				foreach ( $order->get_items() as $item_id => $item_values ) {

				    // Product_id
				    $product_id = $item_values->get_product_id();
					$documents = maybe_unserialize( get_post_meta( $product_id, 'woocommerce_pdf_invoices_documents', true ) );

					if(is_array($documents)) {
						$documents = array_filter($documents);
						if(!empty($documents)) {
							foreach($documents as $document) {
								$documentURL = wp_get_attachment_url($document);
								if(!empty($documentURL)) {
									$html .= '<br><a class="woocommerce-pdf-invoice-download" target="_blank" href="' . $documentURL . '">' . get_the_title( $document ) . '</a>';
								}	
							}
						}

					} 	
				}
			}
		}

		echo $html;
	}

	public function show_tax_rates($tax_totals, $order)
	{
		if(!$this->get_option('generalShowTaxRate')) {
			return $tax_totals;
		}
		
		foreach ($tax_totals as $key => &$value) {
			$rate = WC_TAX::get_rate_percent( $value->rate_id);
			$value->label .= ' (' . $rate . ')';
		}
		return $tax_totals;
	}

	protected function get_uploads_dir( $subdir = '' ) 
	{
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		if ( '' != $subdir ) {
			$upload_dir = $upload_dir . '/' . $subdir;
		}
		return $upload_dir;
	}

	protected function is_user_role( $user_role, $user_id = 0 ) 
	{
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		if ( ! isset( $the_user->roles ) || empty( $the_user->roles ) ) {
			$the_user->roles = array( 'guest' );
		}
		return ( isset( $the_user->roles ) && is_array( $the_user->roles ) && in_array( $user_role, $the_user->roles ) );
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
}