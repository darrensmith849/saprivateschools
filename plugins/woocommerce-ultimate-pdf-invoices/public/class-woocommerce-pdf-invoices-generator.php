<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://woocommerce-pdf-invoices.welaunch.io
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
 * @author     Daniel Barenkamp <support@welaunch.io>
 */
class WooCommerce_PDF_Invoices_Generator extends WooCommerce_PDF_Invoices {

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
	public function __construct( $plugin_name, $version ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->data = new stdClass;
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
    }

	public function setup_data($order_id)
	{
    	global $post, $woocommerce, $wpdb;

    	$this->woocommerce_version = $woocommerce->version;

    	// default Variables
		$this->data->blog_name = get_bloginfo('name');
		$this->data->blog_description  = get_bloginfo('description');

		$this->data->order_id = $order_id;

		// Order Data
		$order = wc_get_order($order_id);
		if(!$order) {
			wp_die( __('No Woo Order Found', 'woocommerce-pdf-invoices') );
		}
		$this->data->order = $order;

		$order_data = $order->get_data();
		unset($order_data['meta_data']);
		unset($order_data['data_store']);
		unset($order_data['default_data']);
		unset($order_data['line_items']);
		unset($order_data['tax_lines']);

		if(!empty($order_data['billing'])) {
			foreach ($order_data['billing'] as $key => $value) {
				$order_data['billing_' . $key] = $value;
			}
			unset($order_data['billing']);
		}

		if(!empty($order_data['shipping'])) {
			foreach ($order_data['shipping'] as $key => $value) {
				$order_data['shipping_' . $key] = $value;
			}
			unset($order_data['shipping']);
		}

		if(isset($order_data['date_created']) && !empty($order_data['date_created'])) {
			$order_data['order_created'] = $order_data['date_created']->date_i18n($this->get_option('dateFormat'));	

			$invoiceDueDateDays = $this->get_option('generalInvoiceDueDateDays');
			$order_data['invoice_due_date'] = $order_data['date_created']->modify('+' . $invoiceDueDateDays . ' days')->date_i18n( $this->get_option('dateFormat') );
		}

		if(isset($order_data['date_modified']) && !empty($order_data['date_modified'])) {
			$order_data['order_modified'] = $order_data['date_modified']->date_i18n($this->get_option('dateFormat'));
		}
		
		unset($order_data['date_created']);
		unset($order_data['date_modified']);

		// Prices
		$order_data['subtotal'] = $order_data['total'] - $order_data['total_tax'];

		// Shipping
		$order_data['shipping_method_title'] = $order->get_shipping_method();

		// Order Meta Data
		$order_meta_data = get_post_meta( $order_id, '', true);
		if(!empty($order_meta_data)) {
			$tmp = array();
			foreach ($order_meta_data as $key => $value) {
				if(is_array($value) && !empty($value)) {
					$tmp[$key] = $value[0];
				} else {
					$tmp[$key] = $value;
				}
			}
			$order_meta_data = $tmp;
		}

		$order_meta_data = apply_filters('woocommerce_pdf_invoices_order_meta_data', $order_meta_data);
		$order_data = array_merge($order_data, $order_meta_data);

		// Customer Meta Data
		if(isset($order_data['customer_id']) && !empty($order_data['customer_id'])) {
			$customer_meta_data = get_user_meta( $order_data['customer_id'], '', true);
			if(!empty($customer_meta_data)) {
				$tmp = array();
				foreach ($customer_meta_data as $key => $value) {
					if(is_array($value) && !empty($value)) {
						$tmp['customer_' . $key] = $value[0];
					} else {
						$tmp['customer_' . $key] = $value;
					}
				}
				$customer_meta_data = $tmp;
			}
			$customer_meta_data = apply_filters('woocommerce_pdf_invoices_customer_meta_data', $customer_meta_data);
			$order_data = array_merge($order_data, $customer_meta_data);
		} else {
			$order_data['customer_id'] = __('Guest', 'woocommerce-pdf-invoices');
		}

		$order_data['coupons_used'] = "";
		$appliedCoupons = $order->get_used_coupons();
		if(!empty($appliedCoupons)) {
		
			$order_data['coupons_used'] = __('Coupons Used: ', 'woocommerce-pdf-invoices');

			$first = true;
			foreach ($appliedCoupons as $appliedCoupon) {

				if(!$first) {
					$order_data['coupons_used'] .= ', ';
				}

				$order_data['coupons_used'] .= $appliedCoupon;			

				$first = false;
			}
		}

		if(isset($order_data['date_completed']) && !empty($order_data['date_completed'])) {
			$order_data['date_completed'] = $order_data['date_completed']->date_i18n($this->get_option('dateFormat'));
		}
		if(isset($order_data['date_paid']) && !empty($order_data['date_paid'])) {
			$order_data['date_paid'] = $order_data['date_paid']->date_i18n($this->get_option('dateFormat'));
		}

		$billing_country = $order->get_billing_country();
		if(isset($order_data['billing_country']) && !empty($order_data['billing_country'])) {
			$order_data['billing_country'] = WC()->countries->countries[ $billing_country ];
		}

		if(isset($order_data['billing_state']) && !empty($order_data['billing_state']) && !empty($billing_country)) {

			$billing_states = WC()->countries->get_states( $billing_country );
			$order_data['billing_state']  = ! empty( $billing_states[ $order_data['billing_state'] ] ) ? $billing_states[ $order_data['billing_state'] ] : $order_data['billing_state'];
		}

		$shipping_country = $order->get_shipping_country();
		if(isset($order_data['shipping_country']) && !empty($order_data['shipping_country'])) {
			$order_data['shipping_country'] = WC()->countries->countries[ $shipping_country ];
		}

		if(isset($order_data['shipping_state']) && !empty($order_data['shipping_state']) && !empty($shipping_country)) {

			$shipping_states = WC()->countries->get_states( $shipping_country );
			$order_data['shipping_state']  = ! empty( $shipping_states[ $order_data['shipping_state'] ] ) ? $shipping_states[ $order_data['shipping_state'] ] : $order_data['shipping_state'];
		}

		$order_data['qr_code'] = '<div class="barcode-container"><barcode code="' . get_permalink($this->data->order_id) . '" type="QR" class="barcode" size="' . $this->get_option('qrCodeSize') . '" error="M" /></div>';

		$barcodeValue = $this->data->order_id;
		if($this->get_option('barCodeValue') == "invoice_number") {
			$barcodeValue = $order_data['invoice_number'];
		}

		$order_data['barcode'] = '<div class="barcode-container"><barcode code="' . $barcodeValue . '" type="' . $this->get_option('barCodeType') . '" text="1" class="barcode" size="' . $this->get_option('barCodeSize') . '" error="M" /></div>';

		unset($order_data['shipping_lines']);
		unset($order_data['fee_lines']);
		unset($order_data['tax_lines']);

		if(function_exists('welanch_woocommerce_delivery_run')) {
			if(isset($order_data['delivery_location'])) {
				global $woocommerce_delivery_options;

				$deliveryLocationOptions = array_filter( $woocommerce_delivery_options['deliveryLocationOptions'] );
				$deliveryLocationOptions = array_combine(range(1, count($deliveryLocationOptions)), array_values($deliveryLocationOptions));

				$order_data['delivery_location'] = $deliveryLocationOptions[$order_data['delivery_location']];
			}
		}


		if(class_exists('Barcode_Order') && $this->get_option('integrationsMame')) {

			$order_data['mame_barcode'] = "";

			// https://docs.mamedev.ch/integration-in-lieferscheine-rechnungen-2/
			ob_start();
			Barcode_Order::print_label_images_from_woocommerce_order($order); 
			$test = ob_get_clean();
			preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $test, $result);
			if(!empty($result)) {

			
				$foo = array_pop($result);
				if(!empty($foo)) {
					$tempFile = dirname( __FILE__ ) . '/../cache/mame_barcode.jpg';
					
					file_put_contents( $tempFile, file_get_contents($foo));

					// $original_img = imagecreatefromjpeg($tempFile);

					// $cropped = imagecropauto($original_img, IMG_CROP_WHITE);
					// var_dump($cropped);
					
					// imagejpg($cropped, $tempFile);
					// die();

					// Format the image SRC:  data:{mime};base64,{data};
					// $src = 'data:image/jpg;base64,'.$imageData;

					// Echo out a sample image
					$src = '<img src="' . $tempFile . '" width="' . $this->get_option('integrationsMameBarcodeWidth') . 'px">';

					$order_data['mame_barcode'] = $src;
				}
			}

			$order_data['mame_webstamp'] = "";
			// https://docs.mamedev.ch/integration-in-lieferscheine-rechnungen/
			ob_start();
			Webstamp_Order::print_stamp_images_from_woocommerce_order($order); 
			$test = ob_get_clean();
			preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $test, $result);
			if(!empty($result)) {

			
				$foo = array_pop($result);
				if(!empty($foo)) {
					$tempFile = dirname( __FILE__ ) . '/../cache/mame_webstamp.jpg';
					
					file_put_contents( $tempFile, file_get_contents($foo));

					// $original_img = imagecreatefromjpeg($tempFile);

					// $cropped = imagecropauto($original_img, IMG_CROP_WHITE);
					// var_dump($cropped);
					
					// imagejpg($cropped, $tempFile);
					// die();

					// Format the image SRC:  data:{mime};base64,{data};
					// $src = 'data:image/jpg;base64,'.$imageData;

					// Echo out a sample image
					$src = '<img src="' . $tempFile . '" width="' . $this->get_option('integrationsMameWebstampWidth') . 'px">';

					$order_data['mame_webstamp'] = $src;
				}
			}

			

		}

		
		$this->data->order_data = apply_filters('woocommerce_pdf_invoices_order_data', $order_data);
		$this->data->items = apply_filters('woocommerce_pdf_invoices_order_items', $order->get_items());

		return TRUE;
	}

    public function create_pdf($upload_dir, $output = false, $credit_note = false)
    {
    	if(!class_exists('\Mpdf\Mpdf')) return FALSE;

    	$customFonts = array();

    	require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'fonts/customFonts.php');

    	if(!is_array( $customFonts ) ) {
    		$customFonts = array();
    	}

    	$headerMargin = $this->get_option('headerMargin');
    	$footerMargin = $this->get_option('footerMargin');
    	$format = $this->get_option('format');

		$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
		$fontData = $defaultFontConfig['fontdata'];

    	try {
			$mpdfConfig = array(
				'mode' => '+aCJK', 
				'format' => $format,    // format - A4, for example, default ''
				'default_font_size' => 0,     // font size - default 0
				'default_font' => '',    // default font family
				'margin_left' => 0,    	// 15 margin_left
				'margin_right' => 0,    	// 15 margin right
				'margin_top' => $headerMargin,     // 16 margin top
				'margin_bottom' => $footerMargin,    	// margin bottom
				'margin_header' => 0,     // 9 margin header
				'margin_footer' => 0,     // 9 margin footer
				'orientation' => 'P',  	// L - landscape, P - portrait
				'tempDir' => dirname( __FILE__ ) . '/../cache/',
				'fontDir' => array(
					plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/mpdf/mpdf/ttfonts/',
					plugin_dir_path( dirname( __FILE__ ) ) . 'fonts/',
				),
			    'fontdata' => array_merge($fontData, $customFonts),
			    'curlUserAgent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:110.0) Gecko/20100101 Firefox/110.0',
			);

			$mpdfConfig = apply_filters('woocommerce_pdf_invoices_mpdf_config', $mpdfConfig);

			$mpdf = new \Mpdf\Mpdf($mpdfConfig);	


			$mpdf->useAdobeCJK = true;
			$mpdf->autoScriptToLang = true;
			$mpdf->autoLangToFont = true;

			if($this->get_option('debugMPDF')) {
				$mpdf->debug = true;
				$mpdf->debugfonts = true;
				$mpdf->showImageErrors = true;
			}

			$css = $this->build_CSS();

			if($this->get_option('enableHeader')) {
				$header =  apply_filters('woocommerce_pdf_invoices_header', $this->get_header());
				$header = $this->replace_vars($header);
				$mpdf->SetHTMLHeader($header);
			}

			$html = '<div class="frame">';

				$html .= apply_filters('woocommerce_pdf_invoices_address', $this->get_address());
				$html .= apply_filters('woocommerce_pdf_invoices_content', $this->get_content());

			$html .= '</div>';

			if($this->get_option('enableFooter')) {
				$footer =  apply_filters('woocommerce_pdf_invoices_footer', $this->get_footer());
				$footer = $this->replace_vars($footer);
				$mpdf->SetHTMLFooter($footer);
			}

			$html = $this->replace_vars($html);

			if($credit_note || $this->data->order->get_status() == "refunded" ) {
				$html = str_replace( __('Invoice', 'woocommerce-pdf-invoices'), __('Credit Note', 'woocommerce-pdf-invoices'), $html);
			}

			$filename = $this->escape_filename($this->data->order_data['id']);

			if($this->get_option('debugMode')) {
				echo $header;
				echo $css . $html;
				echo $footer;
				die();
			}

			$mpdf->WriteHTML($css . $html);

			if($output) {
				$mpdf->Output($upload_dir . '/' . $filename . '.pdf', 'I');
				die();
			}
			
			if(file_exists($upload_dir . '/' . $filename . '.pdf') && $this->get_option('showOldInvoices')){
				rename($upload_dir . '/' . $filename . '.pdf', $upload_dir . '/' . $filename . '_old_' . uniqid() . '.pdf');
			}

			$mpdf->Output($upload_dir . '/' . $filename . '.pdf', 'F');
			return true;
    	} catch (Exception $e) {
    		echo $e->getMessage();
    		return false;
    	}

		exit;
    }

    public function build_CSS()
    {
    	$layoutPadding = $this->get_option('layoutPadding');
    	
    	// Font
    	$layoutFontFamily = $this->get_option('layoutFontFamily') ? $this->get_option('layoutFontFamily') : 'dejavusans';
    	$layoutTextColor = $this->get_option('layoutTextColor');

    	$layoutFontSize = $this->get_option('layoutFontSize') ? $this->get_option('layoutFontSize') : '11';
    	$layoutFontSize = intval($layoutFontSize);

    	$layoutFontLineHeight =  $this->get_option('layoutFontLineHeight') ? $this->get_option('layoutFontLineHeight') : $layoutFontSize + 6; 
    	$layoutFontLineHeight = intval($layoutFontLineHeight);

		$css = '
		<head>
			<style media="all">';


		$css .= '
			@page noheader {
	            header: none;
	            footer: none;   
	            margin-top: 0;
	            margin-bottom: 0;
	        }
	        div.noheader {
	            page-break-before: right;
	            page: noheader;
	        }
			body, table { 
				color: ' . $layoutTextColor . ';
				font-family: ' . $layoutFontFamily . ', sans-serif;
				font-size: ' . $layoutFontSize . 'pt;
				line-height: ' . $layoutFontLineHeight . 'pt;
	 		}

			table {
				width: 100%;
				text-align: left;
				border-spacing: 0;
			}

			table th, table td {
				padding: 4px 5px;
				text-align: left;
			}

	 		.header, .footer {
				padding-top: 10px;
				padding-bottom: 10px;
				padding-right: ' . $layoutPadding['padding-right'] . '; 
				padding-left: ' . $layoutPadding['padding-left'] . '; 
	 		}

	 		h1 {
				font-size: 20pt;
				line-height: 26pt;
	 		}

	 		h2 {
				font-size: 18pt;
				line-height: 24pt;
	 		}

	 		h3 {
				font-size: 16pt;
				line-height: 22pt;
	 		}

	 		h4 {
				font-size: 14pt;
				line-height: 20pt;
	 		}

	 		h5 {
				font-size: 12pt;
				line-height: 18pt;
	 		}

	 		.col {
				float: left;
	 		}

	 		.col-2 {
	 			width: 15%;
	 		}

	 		.col-3 {
	 			width: 24%;
	 		}
			
			.col-4 {
				width: 32%;
			}

			.col-6 {
				width: 49%;
			}
	
	 		.col-8 {
	 			width: 66%;
	 		}

	 		.header-text-right {
	 			text-align: right;
	 		}

	 		.header-text-center {
	 			text-align: center;
	 		}

	 		.header-text-left {
	 			text-align: left;
	 		}

	 		.footer-text-right {
	 			text-align: right;
	 		}

	 		.footer-text-center {
	 			text-align: center;
	 		}

	 		.footer-text-left {
	 			text-align: left;
	 		}

	 		.row {
	 			clear: both;
	 			float: none;
	 		}

	 		.frame {
				padding-top: ' . $layoutPadding['padding-top'] . '; 
				padding-right: ' . $layoutPadding['padding-right'] . '; 
				padding-bottom: ' . $layoutPadding['padding-bottom'] . '; 
				padding-left: ' . $layoutPadding['padding-left'] . '; 
 			}
 			';

	 	// Header
	 	$headerBackgroundColor = $this->get_option('headerBackgroundColor');
		$headerTextColor = $this->get_option('headerTextColor');
		$headerFontSize = intval($this->get_option('headerFontSize'));

		$css .= '
			.header {
				color: ' . $headerTextColor . ';
				background-color: ' . $headerBackgroundColor . ';
				font-size: ' . $headerFontSize . 'pt;
			}';

    	// Items
    	$contentItemsEvenBackgroundColor = $this->get_option('contentItemsEvenBackgroundColor');
		$contentItemsEvenTextColor = $this->get_option('contentItemsEvenTextColor');

    	$contentItemsOddBackgroundColor = $this->get_option('contentItemsOddBackgroundColor');
		$contentItemsOddTextColor = $this->get_option('contentItemsOddTextColor');

		$css .= '
			table.content-items {
				margin-top: 20px;
				margin-bottom: 20px;
			}

			table.content-items tr.even { 
				background-color: ' . $contentItemsEvenBackgroundColor . ';
				color: ' . $contentItemsEvenTextColor . ';
	 		}
	 		table.content-items tr.odd { 
				background-color: ' . $contentItemsOddBackgroundColor . ';
				color: ' . $contentItemsOddTextColor . ';
	 		}
	 		table.content-items tfoot tr.black-border td {
				border-top: 1px solid #cecece;
 			}
 			table.content-items .content-item-total, table th.th-showTotal {
 				text-align: right;
			}

			table.content-items .discount {
				color: #F44336;
			}

			.content-items td {
			    vertical-align: top;
			}

			.td-showProduct {
			    font-weight: bold;
			}

			.wc-item-meta, .wc-item-meta-label {
			    font-weight: normal;
			}
			';


	 	// Foooter
	 	$footerBackgroundColor = $this->get_option('footerBackgroundColor');
		$footerTextColor = $this->get_option('footerTextColor');
		$footerFontSize = intval($this->get_option('footerFontSize'));

		$css .= '
			.footer {
				color: ' . $footerTextColor . ';
				background-color: ' . $footerBackgroundColor . ';
				font-size: ' . $footerFontSize . 'pt;
			}';

		$customCSS = $this->get_option('customCSS');
		if(!empty($customCSS))
		{
			$css .= $customCSS;
		}

		$css .= '
			</style>

		</head>';

		return $css;
    }

    public function get_address()
    {
    	
    	$addressTextLeft = apply_filters('woocommerce_pdf_invoices_address_left', $this->get_option('addressTextLeft'));
    	$addressTextRight = apply_filters('woocommerce_pdf_invoices_address_right', $this->get_option('addressTextRight'));

		$address = '
		<div id="address-container" class="row">
			<div id="address-left" class="col col-8">' . wpautop( $addressTextLeft ) . '</div>
			<div id="address-right" class="col col-10">' . wpautop( $addressTextRight ) . '</div>
		</div>';

		return $address;
    }

    public function get_content()
    {
    	$contentTextIntro = apply_filters('woocommerce_pdf_invoices_content_intro', $this->get_option('contentTextIntro'));
    	$contentTextOutro = apply_filters('woocommerce_pdf_invoices_content_outro', $this->get_option('contentTextOutro'));

    	$content = '<div id="content-container" class="row">';

	    	if(!empty($contentTextIntro)) {
				$content .= '
				<div class="content-text-intro">
					' . wpautop( $contentTextIntro ) . '
				</div>';
			}

			if(!empty($this->data->items)) {
				$content .= '
				<div class="content-items-container">
					' . $this->get_items_table($this->data->items) . '
				</div>';
			}

			if(!empty($contentTextOutro)) {
				$content .= '
				<div class="content-text-outro">
					' . wpautop( $contentTextOutro ) . '
				</div>';
			}

		$content .= '</div>';

		return $content;
    }

    public function get_items_table($items) 
    {
    	$html = "";
    	if(empty($items)) {
    		return $html;
    	}

    	$currency = array('currency' => $this->data->order->get_currency() );

    	$showData = array(
			'showPos' => array(
				'width' => $this->get_option('contentItemsShowPosWidth'),
				'name' => $this->get_option('contentItemsShowPosName'),
			),
			'showImage' => array(
				'width' => $this->get_option('contentItemsShowImageWidth'),
				'name' => $this->get_option('contentItemsShowImageName'),
			),
			'showProduct' => array(
				'width' => $this->get_option('contentItemsShowProductWidth'),
				'name' => $this->get_option('contentItemsShowProductName'),
			),
			'showSKU' => array(
				'width' => $this->get_option('contentItemsShowSKUWidth'),
				'name' => $this->get_option('contentItemsShowSKUName'),
			),
			'showWeight' => array(
				'width' => $this->get_option('contentItemsShowWeightWidth'),
				'name' => $this->get_option('contentItemsShowWeightName'),
			),
			'showDimensions' => array(
				'width' => $this->get_option('contentItemsShowDimensionsWidth'),
				'name' => $this->get_option('contentItemsShowDimensionsName'),
			),
			'showShortDescription' => array(
				'width' => $this->get_option('contentItemsShowShortDescriptionWidth'),
				'name' => $this->get_option('contentItemsShowShortDescriptionName'),
			),
			'showDescription' => array(
				'width' => $this->get_option('contentItemsShowDescriptionWidth'),
				'name' => $this->get_option('contentItemsShowDescriptionName'),
			),
			'showQty' => array(
				'width' => $this->get_option('contentItemsShowQtyWidth'),
				'name' => $this->get_option('contentItemsShowQtyName'),
			),

			'showPriceWithoutTaxes' => array(
				'width' => $this->get_option('contentItemsShowPriceWithoutTaxesWidth'),
				'name' => $this->get_option('contentItemsShowPriceWithoutTaxesName'),
			),
			'showPrice' => array(
				'width' => $this->get_option('contentItemsShowPriceWidth'),
				'name' => $this->get_option('contentItemsShowPriceName'),
			),
			'showProductTaxes' => array(
				'width' => $this->get_option('contentItemsShowProductTaxesWidth'),
				'name' => $this->get_option('contentItemsShowProductTaxesName'),
			),


			'showTotalWithoutVAT' => array(
				'width' => $this->get_option('contentItemsShowTotalWithoutVATWidth'),
				'name' => $this->get_option('contentItemsShowTotalWithoutVATName'),
			),
			'showVATInPercent' => array(
				'width' => $this->get_option('contentItemsShowVATInPercentWidth'),
				'name' => $this->get_option('contentItemsShowVATInPercentName'),
			),
			'showVAT' => array(
				'width' => $this->get_option('contentItemsShowVATWidth'),
				'name' => $this->get_option('contentItemsShowVATName'),
			),
			'showCouponTotal' => array(
				'width' => $this->get_option('contentItemsShowCouponTotalWidth'),
				'name' => $this->get_option('contentItemsShowCouponTotalName'),
			),
			'showTotal' => array(
				'width' => $this->get_option('contentItemsShowTotalWidth'),
				'name' => $this->get_option('contentItemsShowTotalName'),
			),

			'showWPOvernightBarcode' => array(
				'width' => $this->get_option('contentItemsShowWPOvernightBarcodeWidth'),
				'name' => $this->get_option('contentItemsShowWPOvernightBarcodeName'),
			),

    	);

    	$itemData = $this->get_option('itemData');
    	if(isset($itemData['enabled'])) {
			$itemData = $itemData['enabled'];
			unset($itemData['placebo']);
    	} else {
    		$itemData = $showData;
    	}
 
		$itemData = apply_filters('woocommerce_pdf_invoices_data_to_show', $itemData);

    	$html .= 
    	'<table class="content-items">
	    	<thead>
				<tr class="odd">';

					foreach($itemData as $showDataKey => $showDataValue) {

						if(!isset($showData[$showDataKey])) {
							continue;
						}

						if(!empty($itemData)) {
							$showDataValue = $showData[$showDataKey];
						}

						$html .= '<th class="th-' . $showDataKey . '" width="' . $showDataValue['width'] . '%">' . $showDataValue['name'] . '</th>';
					}

					$html .= '
				</tr>
	    	</thead>
	    	<tbody>';

			do_action( 'woocommerce_order_details_before_order_table_items', $this->data->order );

			$tax_display = get_option( 'woocommerce_tax_display_cart' );
			$productLink = $this->get_option('contentItemsShowLinks');

	    	$i = 1;
	    	$totalSubtotalWithoutTaxes = 0;
	    	$totalSubtotalWithTaxes = 0;
	    	foreach ($items as $item) {
	    		
	    		$item_id			= $item->get_id();
	    		$item_data 			= $item->get_data();
    			$product 			= $item->get_product();
    			
				$is_visible        	= $product && $product->is_visible();
				$couponAmount = 0;
				if($productLink) {
					$product_permalink 	= apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $this->data->order );
				} else {
					$product_permalink = "";
				}

				if ( 'excl' === $tax_display ) {
					$subtotal = $this->data->order->get_line_subtotal( $item );
				} else {
					$subtotal = $this->data->order->get_line_subtotal( $item, true );
				}

				$subtotalWithoutTaxes = $this->data->order->get_line_subtotal( $item, false );
				$totalSubtotalWithoutTaxes += $this->data->order->get_line_subtotal( $item, false);

				$appliedCoupons = $this->data->order->get_used_coupons();
				if(!empty($appliedCoupons)) {
				
					foreach ($appliedCoupons as $appliedCoupon) {
						$appliedCoupon = new WC_Coupon($appliedCoupon);

						if(!$appliedCoupon->is_valid_for_product($product)) {
							continue;
						}

						if($appliedCoupon->discount_type == "percent") {
							$couponAmount = $couponAmount + ( $product->get_price() * ($appliedCoupon->amount / 100) * $item->get_quantity());
						} elseif($appliedCoupon->discount_type == "fixed_product") {
							$couponAmount = $couponAmount + ( $appliedCoupon->amount * $item->get_quantity());
						}
						
					}
				}

				$totalSubtotalWithTaxes +=  $this->data->order->get_line_subtotal( $item, true );

				if($this->get_option('itemsRemoveDiscount')) {
					$subtotal = $subtotal - $couponAmount;
					$totalSubtotalWithoutTaxes = $totalSubtotalWithoutTaxes - $couponAmount;
					$totalSubtotalWithTaxes = $totalSubtotalWithTaxes - $couponAmount;
				}

				$itemTotalRefund = $this->data->order->get_total_refunded_for_item( $item_id ); // Get the refunded amount for a line item.
				if($itemTotalRefund > 0) {
					// $subtotal = $subtotal - $itemTotalRefund;
					$totalSubtotalWithoutTaxes = $totalSubtotalWithoutTaxes - $itemTotalRefund;
					$totalSubtotalWithTaxes = $totalSubtotalWithTaxes - $itemTotalRefund;
				}

	    		$tr_class = ($i % 2 == 0) ? 'odd' : 'even';

	    		$html .= 
	    			'<tr class="' . $tr_class . '">';

	    				foreach($itemData as $showDataKey => $showDataValue) {

							if(!isset($showData[$showDataKey])) {
								continue;
							}

							if(!empty($itemData)) {
								$showDataValue = $showData[$showDataKey];
							}

		    				if($showDataKey == "showPos") {
		    					$html .= '<td class="td-' . $showDataKey . '">' . $i . '</td>';
		    				}

							elseif($showDataKey == "showImage") {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
									$imageSrc	   = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id(), 'full' ) );
									if(!isset($imageSrc[0]) && $product->is_type('variation')) {
										$imageSrc	   = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_parent_id(), 'full' ) );
									}

									if(!isset($imageSrc[0])) {
										$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
									} else {
										$imageSize = $this->get_option( 'contentItemsShowImageSize' );
										$html .= '<td class="td-' . $showDataKey . '"><img src="' . $imageSrc[0] . '" width="' . $imageSize . 'px" alt=""></td>';
									}
								}
		    				}

							elseif($showDataKey == "showProduct") {
		    					$html .=  '<td class="td-' . $showDataKey . '">' . 
		    						apply_filters( 'woocommerce_order_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(), $item, $is_visible );

		    						$attributes = $this->get_option('contentItemsShowAttributes');		    						
		    						if(!empty($attributes)) {
		    							foreach ($attributes as $attribute) {
		    								$attributeValue = $product->get_attribute( $attribute );
		    								if(empty($attributeValue)) {
		    									continue;
		    								}

		    								$attributeLabel = wc_attribute_label($attribute);

		    								$html .= '<span class="woocommerce-pdf-invoices-attribute"><br><b class="woocommerce-pdf-invoices-attribute-name">' . $attributeLabel . ':</b> <span class="woocommerce-pdf-invoices-attribute-value">' . $attributeValue . '</span></span>';
		    							}
		    						}

		    						ob_start();
									do_action( 'woocommerce_order_item_meta_start', $item->get_id(), $item, $this->data->order, false );
									$html .=  '<div class="woocommerce-meta-start">' . ob_get_clean() . '</div>';
									
									$html .= wc_display_item_meta( $item, array('echo' => false) );

									ob_start();
									do_action( 'woocommerce_order_item_meta_end', $item->get_id(), $item, $this->data->order, false );
									$html .=  '<div class="woocommerce-meta-end">' . ob_get_clean() . '</div>';

								$html .= '</td>';
		    				}
							elseif($showDataKey == "showWPOvernightBarcode") {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
									ob_start();
									echo do_shortcode('[wcub_product_barcode id="' . $product->get_id() . '"]');
									$barcode = ob_get_clean();
		    						$html .= '<td class="td-' . $showDataKey . '">' . $barcode . '</td>';
		    					}
		    					
		    				}
							elseif($showDataKey == "showSKU") {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
		    						$html .= '<td class="td-' . $showDataKey . '">' . ($product->get_sku() ? $product->get_sku() : __('N/A', 'woocommerce-pdf-invoices')) . '</td>';
		    					}
		    				}

							elseif($showDataKey == "showWeight") {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
		    						$html .= '<td class="td-' . $showDataKey . '">' . ($product->get_weight() ? $product->get_weight() : __('N/A', 'woocommerce-pdf-invoices')) . '</td>';
		    					}
		    				}

							elseif($showDataKey == "showDimensions") {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
		    						$html .= '<td class="td-' . $showDataKey . '">' . ($product->get_dimensions() ? $product->get_dimensions() : __('N/A', 'woocommerce-pdf-invoices')) . '</td>';
		    					}
		    				}

		    				elseif($showDataKey == 'showShortDescription') {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
		    						$html .= '<td class="td-' . $showDataKey . '">' . ($product->get_short_description() ? do_shortcode($product->get_short_description()) : __('N/A', 'woocommerce-pdf-invoices')) . '</td>';
		    					}
		    				} 

		    				elseif($showDataKey == 'showDescription') {
								if(!$product) {
									$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
								} else {
		    						$html .= '<td class="td-' . $showDataKey . '">' . ($product->get_description() ? do_shortcode($product->get_description()) : __('N/A', 'woocommerce-pdf-invoices')) . '</td>';
		    					}
		    				} 

							elseif($showDataKey == "showQty") {
		    					$html .= '<td class="td-' . $showDataKey . '">' . apply_filters( 
										'woocommerce_order_item_quantity_html', 
										$item->get_quantity(), 
										$item ) .
									'</td>';
		    				}

		    				elseif($showDataKey == "showPriceWithoutTaxes") {
    							$html .= '<td class="td-' . $showDataKey . '">' . wc_price( $subtotalWithoutTaxes / $item->get_quantity(), $currency) . '</td>';
	    					}

		    				elseif($showDataKey == "showPrice") {
								$html .= '<td class="td-' . $showDataKey . '">' . wc_price( $subtotal / $item->get_quantity(), $currency) . '</td>';
	    					}
		    				elseif($showDataKey == "showProductTaxes") {
		    					$html .= '<td class="td-' . $showDataKey . '">' . wc_price( $item->get_total_tax() /  $item->get_quantity(), $currency) . '</td>';
	    					}
		    				elseif($showDataKey == "showVAT") {
		    					$html .= '<td class="td-' . $showDataKey . '">' . wc_price( $item->get_total_tax() , $currency) . '</td>';
	    					} 

		    				elseif($showDataKey == "showTotalWithoutVAT") {
    							$html .= '<td class="td-' . $showDataKey . '">' . wc_price( $subtotalWithoutTaxes , $currency) . '</td>';
	    					} 

		    				elseif($showDataKey == "showVATInPercent") {

		    					$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
								if (!empty($tax_rates) && !$this->get_option('contentItemsShowVATInPercentDisableAutomatic') ) {

									$html .= '<td class="td-' . $showDataKey . '">';

									if(count($tax_rates) > 1) {
										foreach ($tax_rates as $tax_rate) {
											$html .= $tax_rate['label'] . ': ' . $tax_rate['rate'] . '%<br>';
										}
									} else {
										$tax_rate = reset($tax_rates);
										$html .= $tax_rate['label'] . ': ' . $tax_rate['rate'] . '%<br>';
									}

									$html .= '</td>';

		    					} else {
		    						$calculate = $item->get_total_tax() / $item->get_total();
		    						$calculate =  round((float)$calculate * 100 ) . '%';
		    						if($calculate) {
		    							$html .= '<td class="td-' . $showDataKey . '">' . $calculate . '</td>';
	    							} else {
		    							$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
		    						}
		    					}
	    					} 

		    				elseif($showDataKey == 'showCouponTotal') {

	    						if($couponAmount == 0) {
	    							$html .= '<td class="td-' . $showDataKey . '">' . __('N/A', 'woocommerce-pdf-invoices') . '</td>';
	    						} else {
    								$html .= '<td class="td-' . $showDataKey . '">' . wc_price($couponAmount * -1, $currency) . '</td>';
	    						}

	    					} 
	    					elseif($showDataKey == "showTotal") {

	    						$savedAmount = 0;
	    						$savedAmountHTML = "";

	    						if($this->get_option('contentItemsShowTotalSaved')) {

	    							if($product->is_on_sale()) {
	    								$savedAmount = $item->get_quantity() * ($product->get_regular_price() - $product->get_sale_price());
	    							}

	    							if($couponAmount > 0) {
	    								$savedAmount = $savedAmount + $couponAmount;
	    							}
	    						}

	    						if($savedAmount > 0) {
	    							$savedAmountHTML = '<br><span class="saved-price">' . sprintf($this->get_option('contentItemsShowTotalSavedText'), wc_price($savedAmount, $currency) ) . '</span>';
	    						}

	    						if($itemTotalRefund > 0) {
	    							$savedAmountHTML .= '<br><small><span class="item-refunded">' . $this->get_option('contentItemsTotalsShowRefundedTotalName') . ': ' . wc_price($itemTotalRefund * -1, $currency) . '</span></small>';
	    						}

								$html .= '<td class="td-' . $showDataKey . ' content-item-total">' . wc_price($subtotal, $currency) . $savedAmountHTML . '</td>';
							} 

							else {
								$html .= apply_filters('woocommerce_pdf_invoices_data_to_show_custom_value_' . $showDataKey, '');
							}
						}

						$html .= '
	    			</tr>';
				$i++;
	    	}

	    	do_action( 'woocommerce_order_details_after_order_table_items', $this->data->order );

    	$html .= 
    		'</tbody>
    		<tfoot>';

    			$orderItemTotals = array();

    			if($this->get_option('totalsDataEnable')) {
    				
	   				$orderItemTotals['tax_rates'] = '';

					$orderItemTotals['subtotal_without_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowSubtotalWithoutTaxesName'),
						'value' => wc_price( $totalSubtotalWithoutTaxes, $currency)
					);

					$orderItemTotals['subtotal_with_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowSubtotalWithTaxesName'),
						'value' => wc_price( $totalSubtotalWithTaxes, $currency)
					);

					$orderItemTotals['total_without_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowTotalWithoutTaxesName'),
						'value' => wc_price( $this->data->order->get_total( ) - $this->data->order->get_total_tax() - $this->data->order->get_total_refunded() , $currency)
					);

					$discountTotal = $this->data->order->get_discount_total( );
					if($discountTotal > 0) {
						$discountTotal = $discountTotal * -1;
					}
					$orderItemTotals['discount_total'] = array(
						'label' => $this->get_option('contentItemsTotalsShowDiscountsTotal'),
						'value' => wc_price( $discountTotal, $currency)
					);

					$orderItemTotals['total_with_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowTotalWithTaxesName'),
						'value' => wc_price( $this->data->order->get_total( ) - $this->data->order->get_total_refunded( ), $currency)
					);

					$orderItemTotals['refunded'] = array(
						'label' => $this->get_option('contentItemsTotalsShowRefundedTotalName'),
						'value' => wc_price( $this->data->order->get_total_refunded( ) * -1, $currency)
					);

					$orderItemTotals['shipping_without_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowShippingWithoutTaxesName'),
						'value' => wc_price( $this->data->order->get_shipping_total(), $currency)
					);

					$orderItemTotals['shipping_taxes'] = array(
						'label' => $this->get_option('contentItemsTotalsShowShippingTaxesName'),
						'value' => wc_price( $this->data->order->get_shipping_tax(), $currency)
					);
					
				}

				$orderItemTotals = array_merge($orderItemTotals, $this->data->order->get_order_item_totals() );


		    	$totalsData = $this->get_option('totalsData');
		    	if($this->get_option('totalsDataEnable') && isset($totalsData['enabled'])) {
	    			// $totalsData = array_diff_key( $orderItemTotals, $totalsData['disabled'] );
	    			$totalsData = $totalsData['enabled'];
	    			unset($totalsData['placebo']);
		    	} else {
		    		$totalsData = $orderItemTotals;
		    	}

				foreach ( $totalsData as $key => $total ) {

					if($this->get_option('totalsDataEnable') && !isset($orderItemTotals[$key]) && !stristr($key, "refund") && !stristr($key, "fee")) {
						continue;
					}

					if(!empty($totalsData)) {
						$total = $orderItemTotals[$key];
					}

					$tr_class = ($i % 2 == 0) ? 'odd' : 'even';

					if($key == "tax_rates") {

						$generalShowTaxesBeforeTotalText = $this->get_option('generalShowTaxesBeforeTotalText');

						foreach($this->data->order->get_items('tax') as $tax_item ){
							$html .= '<tr class="' . $tr_class . ' single-taxes">
					    			<td></td>
								    <td colspan="' . (count(array_filter($itemData)) - 3) . '">' . 
								    str_replace( 
								    	array(
								    		'{{taxRate}}', 
								    		'{{taxName}}', 
								    		'{{taxLabel}}'
								    	), array(
								    		WC_TAX::get_rate_percent( $tax_item->get_rate_id() ),
											$tax_item->get_name(),
											$tax_item->get_label()

								    	), $generalShowTaxesBeforeTotalText
								    ) .    	
								    '</td>
								    <td colspan="2" style="text-align: right;">' . wc_price( $tax_item->get_tax_total() + $tax_item->get_shipping_tax_total(), $currency) . '</td>
						    </tr>';
						    $i++;
						    $tr_class = ($i % 2 == 0) ? 'odd' : 'even';
						}
						continue;
					} elseif($key == "fees") {

						foreach($orderItemTotals as $orderItemTotalsKey => $orderItemTotalsValue) {

							if(!stristr($orderItemTotalsKey, "fee")) {
								continue;
							}
						
							$html .= '
							<tr class="' . $tr_class . '">
								<td></td>
								<td colspan="' . (count(array_filter($itemData)) - 3) . '">' . $orderItemTotalsValue['label'] . '</td>
								<td colspan="2" class="content-item-total">' . $orderItemTotalsValue['value'] . '</td>
							</tr>';
						    $i++;
						    $tr_class = ($i % 2 == 0) ? 'odd' : 'even';
						}
						continue;

					}

					$html .= '
					<tr class="' . $tr_class . '">
						<td></td>
						<td colspan="' . (count(array_filter($itemData)) - 3) . '">' . $total['label'] . '</td>
						<td colspan="2" class="content-item-total">' . $total['value'] . '</td>
					</tr>';

					$i++;
				}

    	$html .= '
    		</tfoot>
    	</table>';
    	
    	return $html;
    }

	public function get_header()
    {
    	$headerLayout = $this->get_option('headerLayout');
    	$this->get_option('headerHeight') ? $headerHeight = $this->get_option('headerHeight') : $headerHeight = 'auto';
		$headerVAlign = $this->get_option('headerVAlign');

    	$topLeft = $this->get_option('headerTopLeft');
    	$topMiddle = $this->get_option('headerTopMiddle');
    	$topRight = $this->get_option('headerTopRight');

    	$header = '<div class="header" style="height: ' . $headerHeight .'px">';

    	if($headerLayout == "oneCol")
    	{
			$header .= '
			<div class="row">
				<div class="col col-12 header-text-left header-one">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</div>
			</div>';
    	} elseif($headerLayout == "threeCols") {
			$header .= '
			<div class="row">
				<div class="col col-4 header-text-left header-one">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</div>
				<div class="col col-4 header-text-center header-two">' . $this->get_header_footer_type($topMiddle, 'headerTopMiddle') . '</div>
				<div class="col col-4 header-text-right header-three">' . $this->get_header_footer_type($topRight, 'headerTopRight') . '</div>
			</div>';
		} else {
			$header .= '
			<div class="row">
				<div class="col col-6 header-text-left header-one">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</div>
				<div class="col col-6 header-text-right header-two">' . $this->get_header_footer_type($topRight, 'headerTopRight') . '</div>
			</div>';
		}

		$header .= '</div>';


		return $header;
    }

    public function get_footer()
    {
    	$footerLayout = $this->get_option('footerLayout');
    	$this->get_option('footerHeight') ? $footerHeight = $this->get_option('footerHeight') : $footerHeight = 'auto';

    	$topLeft = $this->get_option('footerTopLeft');
    	$topRight = $this->get_option('footerTopRight');
    	$topMiddleLeft = $this->get_option('footerTopMiddleLeft');
    	$topMiddleRight = $this->get_option('footerTopMiddleRight');
    	
    	$footer = '<div class="footer" style="height: ' . $footerHeight .'px">';

    	if($footerLayout == "oneCol")
    	{
			$footer .= '
			<div class="row">
				<div class="col col-12 footer-text-left footer-one">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</div>
			</div>';
    	} elseif($footerLayout == "threeCols") {
			$footer .= '
			<div class="row">
				<div class="col col-4 footer-text-left footer-one">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</div>
				<div class="col col-4 footer-text-left footer-two">' . $this->get_header_footer_type($topMiddle, 'footerTopMiddleRight') . '</div>
				<div class="col col-4 footer-text-right footer-three">' . $this->get_header_footer_type($topRight, 'footerTopRight') . '</div>
			</div>';
		} elseif($footerLayout == "fourCols") {
			$footer .= '
			<div class="row">
				<div class="col col-3 footer-text-left footer-one">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</div>
				<div class="col col-3 footer-text-left footer-two">' . $this->get_header_footer_type($topMiddle, 'footerTopMiddleLeft') . '</div>
				<div class="col col-3 footer-text-left footer-three">' . $this->get_header_footer_type($topRight, 'footerTopMiddleRight') . '</div>
				<div class="col col-3 footer-text-left footer-four">' . $this->get_header_footer_type($topRight, 'footerTopRight') . '</div>
			</div>';
		} else {
			$footer .= '
			<div class="row">
				<div class="col col-6 footer-text-left footer-one">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</div>
				<div class="col col-6 footer-text-right footer-two">' . $this->get_header_footer_type($topRight, 'footerTopRight') . '</div>
			</div>';
		}

		$footer .= '</div>';

		return $footer;
    }

    private function get_header_footer_type($type, $position)
    {
    	switch ($type) {
    		case 'text':
    			return wpautop( do_shortcode( $this->get_option($position.'Text') ) );
    			break;
    		case 'bloginfo':
    			return $this->data->blog_name.'<br/>'.$this->data->blog_description;
    			break;
    		case 'pagenumber':
				return __( 'Page:', 'woocommerce-pdf-invoices').' {PAGENO}';
    		case 'image':
    			$image = $this->get_option($position.'Image');
    			$imageSrc = $image['url'];
    			$imageHTML = '<img src="' . $image['url'] . '">';
    			return $imageHTML;
    			break;
    		case 'exportinfo':
    			return date('d.m.y');
    			break;
    		default:
    			return '';
    			break;
    	}
    }

    private function escape_filename($file)
    {
		// everything to lower and no spaces begin or end
		$file = strtolower(trim($file));

		// adding - for spaces and union characters
		$find = array(' ', '&', '\r\n', '\n', '+',',');
		$file = str_replace ($find, '-', $file);

		//delete and replace rest of special chars
		$find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
		$repl = array('', '-', '');
		$file = preg_replace ($find, $repl, $file);

		return $file;
    }

	public function replace_vars($string)
	{
		if (preg_match_all("/{{(.*?)}}/", $string, $m)) {
			foreach ($m[1] as $i => $var) {

				if(!isset($this->data->order_data[$var])) {
					$this->data->order_data[$var] = "";
				}

				$string = str_replace($m[0][$i], $this->data->order_data[$var], $string);
			}
	    }

		return $string;
	}
}	