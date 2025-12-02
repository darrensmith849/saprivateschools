<?php

    /**
     * For full documentation, please visit: http://docs.reduxframework.com/
     * For a more extensive sample-config file, you may look at:
     * https://github.com/reduxframework/redux-framework/blob/master/sample/sample-config.php
     */

    if ( ! class_exists( 'weLaunch' ) && ! class_exists( 'Redux' ) ) {
        return;
    }

    if( class_exists( 'weLaunch' ) ) {
        $framework = new weLaunch();
    } else {
        $framework = new Redux();
    }

    // This is your option name where all the Redux data is stored.
    $opt_name = "woocommerce_pdf_invoices_options";

    /**
     * ---> SET ARGUMENTS
     * All the possible arguments for Redux.
     * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
     * */

    $attribute_taxonomy_names = wc_get_attribute_taxonomy_names();
    $attribute_taxonomy_names = array_combine($attribute_taxonomy_names, $attribute_taxonomy_names);


    $args = array(
        'opt_name' => 'woocommerce_pdf_invoices_options',
        'use_cdn' => TRUE,
        'dev_mode' => FALSE,
        'display_name' => __('WooCommerce PDF Invoices', 'woocommerce-pdf-invoices'),
        'display_version' => '1.5.0',
        'page_title' => __('WooCommerce PDF Invoices', 'woocommerce-pdf-invoices'),
        'update_notice' => TRUE,
        'intro_text' => '',
        'footer_text' => '&copy; ' . date('Y') . ' weLaunch',
        'admin_bar' => TRUE,
        'menu_type' => 'submenu',
        'menu_title' => __('PDF Invoices', 'woocommerce-pdf-invoices'),
        'allow_sub_menu' => TRUE,
        'page_parent' => 'woocommerce',
        'page_parent_post_type' => 'your_post_type',
        'customizer' => FALSE,
        'default_mark' => '*',
        'hints' => array(
            'icon_position' => 'right',
            'icon_color' => 'lightgray',
            'icon_size' => 'normal',
            'tip_style' => array(
                'color' => 'light',
            ),
            'tip_position' => array(
                'my' => 'top left',
                'at' => 'bottom right',
            ),
            'tip_effect' => array(
                'show' => array(
                    'duration' => '500',
                    'event' => 'mouseover',
                ),
                'hide' => array(
                    'duration' => '500',
                    'event' => 'mouseleave unfocus',
                ),
            ),
        ),
        'output' => TRUE,
        'output_tag' => TRUE,
        'settings_api' => TRUE,
        'cdn_check_time' => '1440',
        'compiler' => TRUE,
        'page_permissions' => 'manage_options',
        'save_defaults' => TRUE,
        'show_import_export' => TRUE,
        'database' => 'options',
        'transient_time' => '3600',
        'network_sites' => TRUE,
    );

    global $weLaunchLicenses;
    if( (isset($weLaunchLicenses['woocommerce-ultimate-pdf-invoices']) && !empty($weLaunchLicenses['woocommerce-ultimate-pdf-invoices'])) || (isset($weLaunchLicenses['woocommerce-plugin-bundle']) && !empty($weLaunchLicenses['woocommerce-plugin-bundle'])) ) {
        $args['display_name'] = '<span class="dashicons dashicons-yes-alt" style="color: #9CCC65 !important;"></span> ' . $args['display_name'];
    } else {
        $args['display_name'] = '<span class="dashicons dashicons-dismiss" style="color: #EF5350 !important;"></span> ' . $args['display_name'];
    }

    $framework::setArgs( $opt_name, $args );

    /*
     * ---> END ARGUMENTS
     */

    /*
     *
     * ---> START SECTIONS
     *
     */

    // if(isset($_POST['post_ID']) || (isset($_GET['page']) && $_GET['page'] == "wc-settings")) { 
    //     return;
    // }
    if(isset($_GET['tab']) && $_GET['tab'] == "email") {
        return;
    }
    
    global $woocommerce;
    $mailer = $woocommerce->mailer();
    $wc_emails = $mailer->get_emails();

    $non_order_emails = array(
        'customer_note',
        'customer_reset_password',
        'customer_new_account'
    );

    $emails = array();
    foreach ($wc_emails as $class => $email) {
        if ( !in_array( $email->id, $non_order_emails ) ) {
            switch ($email->id) {
                case 'new_order':
                    $emails[$email->id] = sprintf('%s (%s)', $email->title, __( 'Admin email', 'woocommerce-pdf-invoices' ) );
                    break;
                case 'customer_invoice':
                    $emails[$email->id] = sprintf('%s (%s)', $email->title, __( 'Manual email', 'woocommerce-pdf-invoices' ) );
                    break;
                default:
                    $emails[$email->id] = $email->title;
                    break;
            }
        }
    }

    $framework::setSection( $opt_name, array(
        'title'  => __( 'PDF Invoices', 'woocommerce-pdf-invoices' ),
        'id'     => 'general',
        'desc'   => __( 'Need support? Please use the comment function on codecanyon.', 'woocommerce-pdf-invoices' ),
        'icon'   => 'el el-home',
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'General', 'woocommerce-pdf-invoices' ),
        'desc'       => __( 'To get auto updates please <a href="' . admin_url('tools.php?page=welaunch-framework') . '">register your License here</a>.', 'woocommerce-pdf-invoices' ),
        'id'         => 'general-settings',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enable',
                'type'     => 'switch',
                'title'    => __( 'Enable', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Enable PDF Invoices to use the options below', 'woocommerce-pdf-invoices' ),
                'default' => 1,
            ),
            array(
                'id'       => 'createInvoiceStatus',
                'type'     => 'select', 
                'multi'    => true,
                'title'    => esc_html__('Create Invoices & Numbers only on this status', 'woocommerce-reward-points'),
                'subtitle' => esc_html__('Will not create any invoices when this status is not reached.', 'woocommerce-reward-points', 'woocommerce-reward-points'),
                'options'  => wc_get_order_statuses(),
                'default'  => array(),
                'required' => array('enable','equals','1'),
            ),

            array(
                'id'       => 'generalAutomatic',
                'type'     => 'checkbox',
                'title'    => __( 'Create Invoices Automatically', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'PDF invoices will be created for each order automatically.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
                'required' => array('enable','equals','1'),
            ),
            array(
                'id'       => 'generalAttachToMail',
                'type'     => 'checkbox',
                'title'    => __( 'Attach Invoice to Email', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Attach invoices automatically to orders.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
                'required' => array('enable','equals','1'),
            ),
            array(
                'id'     =>'generalAttachToMailStatus',
                'type'  => 'select',
                'title' => __('Attach to Email order Statuses', 'woocommerce-pdf-invoices'), 
                'multi' => true,
                'options' => $emails,
                'default' => array(
                    'new_order',
                    'customer_invoice',
                    'customer_processing_order',
                    'customer_completed_order',
                    'customer_refunded_order',
                    'customer_partially_refunded_order',
                ),
                'required' => array('generalAttachToMail','equals','1'),
            ),
            array(
                'id'       => 'generalShowInvoice',
                'type'     => 'checkbox',
                'title'    => __( 'Show Invoice to Customers', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Show Invoice in Thank you and Order detail pages.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
            ),
            array(
                'id'       => 'generalInvoiceDueDateDays',
                'type'     => 'spinner', 
                'title'    => __('Invoice Due Date Days', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Use {{invoice_due_date}} as a variable.', 'woocommerce-pdf-invoices' ),
                'default'  => '30',
                'min'      => '1',
                'step'     => '1',
                'max'      => '300',
            ),
            array(
                'id'       => 'generalRenderInvoice',
                'type'     => 'checkbox',
                'title'    => __( 'Render Invoice instead of Download', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Instead of downloading the invoice it will show the PDF directly in browser.', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),
            array(
                'id'       => 'customFileName',
                'type'     => 'text',
                'title'    => __('Custom File Name', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Use the following vars: {{invoice_number}} {{date}}, {{order_id}}, {{customer_id}} {{billing_first_name}} {{billing_last_name}}.', 'woocommerce-pdf-invoices' ),
                'default'  => __( '{{invoice_number}}', 'woocommerce-pdf-invoices'),
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Invoice Number', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'invoice-number',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'invoiceNumberEnable',
                'type'     => 'switch',
                'title'    => __( 'Enable Invoice Numbering', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'If enabled you can use {{invoice_number}} in the invoice document. Click on regenerate if you enable this for the first time.', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),
            array(
                'id'       => 'invoiceNumberPrefix',
                'type'     => 'text',
                'title'    => __('Invoice Number Prefix', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'The Prefix can contain custom text or the following vars: {{date}}, {{order_id}}, {{customer_id}}.', 'woocommerce-pdf-invoices' ),
                'default'  => __( 'I-{{date}}-', 'woocommerce-pdf-invoices'),
                'required' => array('invoiceNumberEnable','equals','1'),
            ),
            array(
                'id'       => 'invoiceNumberSuffix',
                'type'     => 'text',
                'title'    => __('Invoice Number Suffix', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'The Suffix can contain custom text or the following vars: {{date}}, {{order_id}}, {{customer_id}}.', 'woocommerce-pdf-invoices' ),
                'default'  => __( '', 'woocommerce-pdf-invoices'),
                'required' => array('invoiceNumberEnable','equals','1'),
            ),
            array(
                'id'       => 'invoiceNumberDateFormat',
                'type'     => 'text',
                'title'    => __('Invoice Number Date Format', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Use any date format constants from here: <a target="_blank" href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP Date Constants</a>', 'woocommerce-pdf-invoices' ),
                'default'  => __( 'Y-m', 'woocommerce-pdf-invoices'),
                'required' => array('invoiceNumberEnable','equals','1'),
            ),
            array(
                'id'     =>'invoiceNumberStart',
                'type'     => 'spinner', 
                'title'    => __('Start Number', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'The invoice start number. Default: 1', 'woocommerce-pdf-invoices' ),
                'default'  => '1',
                'min'      => '1',
                'step'     => '1',
                'max'      => '9999999',
                'required' => array('invoiceNumberEnable','equals','1'),
            ),
            array(
                'id'     =>'invoiceNumberPadLength',
                'type'     => 'spinner', 
                'title'    => __('Number pad length', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'E.g. 7 = 0000005', 'woocommerce-pdf-invoices' ),
                'default'  => '3',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
                'required' => array('invoiceNumberEnable','equals','1'),
            ),
            array(
                'id'   => 'invoiceNumberRegenerate',
                'type' => 'info',
                'desc' => '<div style="text-align:center;">' . __('This will (re)generate all invoice numbers.', 'wordpress-gdpr') . '<br>
                    <a href="' . get_admin_url() . 'admin.php?page=woocommerce_pdf_invoices_options_options&regenerate-invoice-numbers=true" class="button button-success">' . __('Regenerate Invoice Numbers', 'woocommerce-bought-together') . '</a>
                    </div>',
            ),
        )
    ) );

 $framework::setSection( $opt_name, array(
        'title'      => __( 'Email Attachments', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'email-attachments',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'emailAttachmentsPerProduct',
                'type'     => 'switch',
                'title'    => __( 'Enable Email Attachments per Product', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'When enabled you find a new tab when you edit a product called "pdf invoices". There you can add custom documents, that will be added to Emails when a user has purchased this product.', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),
            array(
                'id'       => 'generalAttachToMailAdditionalPDF1',
                'title'    => __( 'Global Email PDF Attachment 1', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'This attachment will be added to the mail and also shows in customers thank you and order details if enabled.', 'woocommerce-pdf-invoices' ),
                'type' => 'media',
                'mode' => false,
                'url'      => true,
            ),
            array(
                'id'       => 'generalAttachToMailAdditionalPDF2',
                'title'    => __( 'Global Email PDF Attachment 2', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'This attachment will be added to the mail and also shows in customers thank you and order details if enabled.', 'woocommerce-pdf-invoices' ),
                'type' => 'media',
                'mode' => false,
                'url'      => true,
            ),
            array(
                'id'       => 'generalAttachToMailAdditionalPDF3',
                'title'    => __( 'Global Email PDF Attachment 3', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'This attachment will be added to the mail and also shows in customers thank you and order details if enabled.', 'woocommerce-pdf-invoices' ),
                'type' => 'media',
                'mode' => false,
                'url'      => true,
            ),
        ) 
    ) ) ;

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Layout', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'layout',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'format',
                'type'     => 'select',
                'title'    => __( 'Format', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Choose a pre-defined page size. A4 is recommended!', 'woocommerce-pdf-invoices' ),
                'options'  => array(
                    'A4' => __('A4', 'woocommerce-pdf-invoices'),
                    'A4-L' => __('A4 Landscape', 'woocommerce-pdf-invoices'),
                    'A0' => __('A0', 'woocommerce-pdf-invoices'),
                    'A0-L' => __('A0 Landscape', 'woocommerce-pdf-invoices'),
                    'A1' => __('A1', 'woocommerce-pdf-invoices'),
                    'A1-L' => __('A1 Landscape', 'woocommerce-pdf-invoices'),
                    'A3' => __('A3', 'woocommerce-pdf-invoices'),
                    'A3-L' => __('A3 Landscape', 'woocommerce-pdf-invoices'),
                    'A5' => __('A5', 'woocommerce-pdf-invoices'),
                    'A5-L' => __('A5 Landscape', 'woocommerce-pdf-invoices'),
                    'A6' => __('A6', 'woocommerce-pdf-invoices'),
                    'A6-L' => __('A6 Landscape', 'woocommerce-pdf-invoices'),
                    'A7' => __('A7', 'woocommerce-pdf-invoices'),
                    'A7-L' => __('A7 Landscape', 'woocommerce-pdf-invoices'),
                    'A8' => __('A8', 'woocommerce-pdf-invoices'),
                    'A8-L' => __('A8 Landscape', 'woocommerce-pdf-invoices'),
                    'A9' => __('A9', 'woocommerce-pdf-invoices'),
                    'A9-L' => __('A9 Landscape', 'woocommerce-pdf-invoices'),
                    'A10' => __('A10', 'woocommerce-pdf-invoices'),
                    'A10-L' => __('A10 Landscape', 'woocommerce-pdf-invoices'),
                    'Letter' => __('Letter', 'woocommerce-pdf-invoices'),
                    'Legal' => __('Legal', 'woocommerce-pdf-invoices'),
                    'Executive' => __('Executive', 'woocommerce-pdf-invoices'),
                    'Folio' => __('Folio', 'woocommerce-pdf-invoices'),
                ),
                'default' => 'A4',
            ),
            array(
                'id'             => 'layoutPadding',
                'type'           => 'spacing',
                // 'output'         => array('.site-header'),
                'mode'           => 'padding',
                'units'          => array('px'),
                'units_extended' => 'false',
                'title'          => __('Padding', 'woocommerce-pdf-invoices'),
                'default'            => array(
                    'padding-top'     => '50px', 
                    'padding-right'   => '60px', 
                    'padding-bottom'  => '10px', 
                    'padding-left'    => '60px',
                    'units'          => 'px', 
                ),
            ),
            array(
                'id'     =>'layoutTextColor',
                'type'  => 'color',
                'title' => __('Text Color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default'   => '#333333',
            ),
            array(
                'id'     =>'layoutFontFamily',
                'type'  => 'select',
                'title' => __('Default Font', 'woocommerce-pdf-invoices'), 
                'options'  => array(
                    'dejavusans' => __('Sans', 'woocommerce-pdf-invoices' ),
                    'dejavuserif' => __('Serif', 'woocommerce-pdf-invoices' ),
                    'dejavusansmono' => __('Mono', 'woocommerce-pdf-invoices' ),
                    'droidsans' => __('Droid Sans', 'woocommerce-pdf-invoices'),
                    'droidserif' => __('Droid Serif', 'woocommerce-pdf-invoices'),
                    'lato' => __('Lato', 'woocommerce-pdf-invoices'),
                    'lora' => __('Lora', 'woocommerce-pdf-invoices'),
                    'merriweather' => __('Merriweather', 'woocommerce-pdf-invoices'),
                    'montserrat' => __('Montserrat', 'woocommerce-pdf-invoices'),
                    'opensans' => __('Open sans', 'woocommerce-pdf-invoices'),
                    'opensanscondensed' => __('Open Sans Condensed', 'woocommerce-pdf-invoices'),
                    'oswald' => __('Oswald', 'woocommerce-pdf-invoices'),
                    'ptsans' => __('PT Sans', 'woocommerce-pdf-invoices'),
                    'sourcesanspro' => __('Source Sans Pro', 'woocommerce-pdf-invoices'),
                    'slabo' => __('Slabo', 'woocommerce-pdf-invoices'),
                    'raleway' => __('Raleway', 'woocommerce-pdf-invoices'),
                ),
                'default'   => 'dejavusans',
            ),
            array(
                'id'     =>'layoutFontSize',
                'type'     => 'spinner', 
                'title'    => __('Default font size', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),
            array(
                'id'     =>'layoutFontLineHeight',
                'type'     => 'spinner', 
                'title'    => __('Default line height', 'woocommerce-pdf-invoices'),
                'default'  => '12',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),

            array(
                'id'     =>'contentItemsEvenBackgroundColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Even Items Background Color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#FFFFFF',
            ),
            array(
                'id'     =>'contentItemsEvenTextColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Even Items Text Color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#333333',
            ),
            array(
                'id'     =>'contentItemsOddBackgroundColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Odd Items Background Color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#ebebeb',
            ),
            array(
                'id'     =>'contentItemsOddTextColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Odd Items Text Color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#333333',
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Header', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'header',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enableHeader',
                'type'     => 'switch',
                'title'    => __( 'Enable', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Enable header', 'woocommerce-pdf-invoices' ),
                'default' => '1',
            ),

            array(
                'id'     =>'headerBackgroundColor',
                'type' => 'color',
                'title' => __('Header background color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#333333',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerTextColor',
                'type'  => 'color',
                'title' => __('Header text color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#FFFFFF',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerFontSize',
                'type'     => 'spinner', 
                'title'    => __('Header font size', 'woocommerce-pdf-invoices'),
                'default'  => '8',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),
            array(
                'id'     =>'headerLayout',
                'type'  => 'select',
                'title' => __('Header Layout', 'woocommerce-pdf-invoices'), 
                'required' => array('enableHeader','equals','1'),
                'options'  => array(
                    'oneCol' => __('1/1', 'woocommerce-pdf-invoices' ),
                    'twoCols' => __('1/2 + 1/2', 'woocommerce-pdf-invoices' ),
                    'threeCols' => __('1/3 + 1/3 + 1/3', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'twoCols',
            ),
            array(
                'id'     =>'headerMargin',
                'type'     => 'spinner', 
                'title'    => __('Header Margin', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerHeight',
                'type'     => 'spinner', 
                'title'    => __('Header Height', 'woocommerce-pdf-invoices'),
                'default'  => '30',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerTopLeft',
                'type'  => 'select',
                'title' => __('Top Left Header', 'woocommerce-pdf-invoices'), 
                'required' => array('enableHeader','equals','1'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'bloginfo',
            ),
            array(
                'id'     =>'headerTopLeftText',
                'type'  => 'editor',
                'title' => __('Top Left Header Text', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopLeft','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopLeftImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Left Header Image', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopLeft','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopMiddle',
                'type'  => 'select',
                'title' => __('Top Middle Header', 'woocommerce-pdf-invoices'), 
                'required' => array('headerLayout','equals','threeCols'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
            ),
            array(
                'id'     =>'headerTopMiddleText',
                'type'  => 'editor',
                'title' => __('Top Middle Header Text', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopMiddle','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopMiddleImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Middle Header Image', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopMiddle','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopRight',
                'type'  => 'select',
                'title' => __('Top Right Header', 'woocommerce-pdf-invoices'), 
                'required' => array('headerLayout','equals',array('threeCols','twoCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'pagenumber',
            ),
            array(
                'id'     =>'headerTopRightText',
                'type'  => 'editor',
                'title' => __('Top Right Header Text', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopRight','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopRightImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Right Header Image', 'woocommerce-pdf-invoices'), 
                'required' => array('headerTopRight','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Address', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'address',
        'subsection' => true,
        'fields'     => array(
            // array(
            //     'id'       => 'addressLayout',
            //     'type'     => 'image_select',
            //     'title'    => __( 'Select Layout', 'woocommerce-pdf-invoices' ),
            //     'options'  => array(
            //         '1'      => array('img'   => plugin_dir_url( __FILE__ ) . 'img/1.png'),
            //         '2'      => array('img'   => plugin_dir_url( __FILE__ ). 'img/2.png'),
            //         '3'      => array('img'   => plugin_dir_url( __FILE__ ). 'img/3.png'),
            //     ),
            //     'default' => '1'
            // ),
            array(
                'id'     =>'addressTextLeft',
                'type'  => 'editor',
                'title' => __('Address Text Left', 'woocommerce-pdf-invoices'),
                'subtitle' => 'See what <a href="https://www.welaunch.io/en/knowledge-base/faq/invoice-data-fields/" target="_blank">Data fields you can use</a>.',
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => '
                <span style="font-size: 9px;">WeLaunch - In den Sandbergen - 49808 Lingen (Ems)</span><br>
                <br>
                {{billing_company}}<br>
                {{billing_first_name}} {{billing_last_name}}<br>
                {{billing_address_1}} {{billing_address_2}}<br>
                {{billing_postcode}} {{billing_city}}<br>
                {{billing_state}} {{billing_country}}'
            ),
            array(
                'id'     =>'addressTextRight',
                'type'  => 'editor',
                'title' => __('Address Text Right', 'woocommerce-pdf-invoices'),
                'subtitle' => 'See what <a href="https://www.welaunch.io/en/knowledge-base/faq/invoice-data-fields/" target="_blank">Data fields you can use</a>.',
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'Invoice No. {{id}}<br>
                    Invoice Date {{order_created}}<br>
                    <br>
                    Your Customer No. {{customer_id}}'
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Content', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'content',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'     =>'contentTextIntro',
                'type'  => 'editor',
                'title' => __('Content Intro Text', 'woocommerce-pdf-invoices'),
                'subtitle' => 'See what <a href="https://www.welaunch.io/en/knowledge-base/faq/invoice-data-fields/" target="_blank">Data fields you can use</a>.',
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    '<h4>Invoice No {{id}}</h4>
                    Dear {{billing_first_name}} {{billing_last_name}},<br>
                    <br>
                    thank you very much for your order and the trust you have placed in!<br>
                    I hereby invoice you for the following:'
            ),
            array(
                'id'     =>'contentTextOutro',
                'type'  => 'editor',
                'title' => __('Content Outro Text', 'woocommerce-pdf-invoices'),
                'subtitle' => 'See what <a href="https://www.welaunch.io/en/knowledge-base/faq/invoice-data-fields/" target="_blank">Data fields you can use</a>.',
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'Please transfer the invoice amount with invoice number to the account stated below.<br>
                    The invoice amount is due immediately.<br>
                    <br>
                    Payment Method: {{payment_method_title}}<br>
                    Shipping Method: {{shipping_method_title}}<br>
                    Your Note: {{customer_note}}<br>
                    <br>
                    Yours sincerely<br>
                    WeLaunch'
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Item Data To Show', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'itemDataToShow',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'      => 'itemData',
                'type'    => 'sorter',
                'title'   => 'Modules',
                'subtitle'    => 'Data to Show.',
                'options' => array(
                    'enabled'  => array(
                        'showPos' => __('Pos', 'woocommerce-pdf-invoices'),
                        'showImage' => __('Image', 'woocommerce-pdf-invoices'),
                        'showProduct' => __('Product', 'woocommerce-pdf-invoices'),
                        'showQty' => __('Qty', 'woocommerce-pdf-invoices'),
                        'showPriceWithoutTaxes' => __('Price excl. Taxes', 'woocommerce-pdf-invoices'),
                        'showProductTaxes' => __('Product Taxes', 'woocommerce-pdf-invoices'),
                        'showTotal' => __('Total', 'woocommerce-pdf-invoices'),
                    ),
                    'disabled' => array(
                        'showSKU' => __('SKU', 'woocommerce-pdf-invoices'),
                        'showWeight' => __('Weight', 'woocommerce-pdf-invoices'),
                        'showDimensions' => __('Dimensions', 'woocommerce-pdf-invoices'),
                        'showShortDescription' => __('ShortDescription', 'woocommerce-pdf-invoices'),
                        'showDescription' => __('Description', 'woocommerce-pdf-invoices'),
                        'showPrice' => __('Price incl. Tax', 'woocommerce-pdf-invoices'),
                        'showTotalWithoutVAT' => __('TotalWithoutVAT', 'woocommerce-pdf-invoices'),
                        'showVAT' => __('Taxes Total', 'woocommerce-pdf-invoices'),
                        'showCouponTotal' => __('CouponTotal', 'woocommerce-pdf-invoices'),
                        'showVATInPercent' => __('VATInPercent', 'woocommerce-pdf-invoices'),
                        'showWPOvernightBarcode' => __('WP Overnight Barcode', 'woocommerce-pdf-invoices'),
                    )
                ),
            ),

            array(
                'id'       => 'itemsRemoveDiscount',
                'type'     => 'checkbox',
                'title'    => __( 'Remove Coupon Discount from Items', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'When a product has a discount applied, substract the single item lines.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
            ),

            array(
                'id'       => 'contentItemsShowPosName',
                'type'     => 'text',
                'title'    => __( 'Pos Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Pos.', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowPosWidth',
                'type'     => 'spinner', 
                'title'    => __('Pos Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '6',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),

            array(
                'id'       => 'contentItemsShowImageName',
                'type'     => 'text',
                'title'    => __( 'Image Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Image', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowImageWidth',
                'type'     => 'spinner', 
                'title'    => __('Image Column Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
            array(
                'id'     =>'contentItemsShowImageSize',
                'type'     => 'spinner', 
                'title'    => __('Image size (px)', 'woocommerce-pdf-invoices'),
                'default'  => '80',
                'min'      => '1',
                'step'     => '10',
                'max'      => '150',
            ),

            // Product
            array(
                'id'       => 'contentItemsShowProductName',
                'type'     => 'text',
                'title'    => __( 'Product Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Product', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowProductWidth',
                'type'     => 'spinner', 
                'title'    => __('Product Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '17',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
            array(
                'id'       => 'contentItemsShowLinks',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Link', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),

            // Attributes
            array(
                'id'   => 'contentItemsShowAttributes',
                'type' => 'select',
                'options' => $attribute_taxonomy_names,
                'multi' => true,
                'title' => __('Show Attributes (show Product must be enabled)', 'woocommerce-single-variations'), 
                'subtitle' => __('Product attributes & values will display below product name. ', 'woocommerce-single-variations'),
            ),

            // SKU
            array(
                'id'       => 'contentItemsShowSKUName',
                'type'     => 'text',
                'title'    => __( 'SKU Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('SKU', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowSKUWidth',
                'type'     => 'spinner', 
                'title'    => __('SKU Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),

            // Weigth
            array(
                'id'       => 'contentItemsShowWeightName',
                'type'     => 'text',
                'title'    => __( 'Weight Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Weight', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowWeightWidth',
                'type'     => 'spinner', 
                'title'    => __('Weight Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '13',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // Dimensions
            array(
                'id'       => 'contentItemsShowDimensionsName',
                'type'     => 'text',
                'title'    => __( 'Dimensions Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Dimensions', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowDimensionsWidth',
                'type'     => 'spinner', 
                'title'    => __('Dimensions Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),

            // Short Description
           array(
                'id'       => 'contentItemsShowShortDescriptionName',
                'type'     => 'text',
                'title'    => __( 'Short Description Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Short Description', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowShortDescriptionWidth',
                'type'     => 'spinner', 
                'title'    => __('Short Description Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '17',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
 

            // Description
            array(
                'id'       => 'contentItemsShowDescriptionName',
                'type'     => 'text',
                'title'    => __( 'Description Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Description', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowDescriptionWidth',
                'type'     => 'spinner', 
                'title'    => __('Description Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '17',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // Qty
            array(
                'id'       => 'contentItemsShowQtyName',
                'type'     => 'text',
                'title'    => __( 'Qty Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Qty', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowQtyWidth',
                'type'     => 'spinner', 
                'title'    => __('Qty Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // single price without taxes
            array(
                'id'       => 'contentItemsShowPriceWithoutTaxesName',
                'type'     => 'text',
                'title'    => __( 'Single Product Price without Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Price', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowPriceWithoutTaxesWidth',
                'type'     => 'spinner', 
                'title'    => __('Single Product Price witout Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // Price with taxes
            array(
                'id'       => 'contentItemsShowPriceName',
                'type'     => 'text',
                'title'    => __( 'Price with Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Price', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowPriceWidth',
                'type'     => 'spinner', 
                'title'    => __('Price with Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),

            // Price with taxes
            array(
                'id'       => 'contentItemsShowProductTaxesName',
                'type'     => 'text',
                'title'    => __( 'Single Product Taxes', 'woocommerce-pdf-invoices' ),
                'default'  => __('Taxes', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowProductTaxesWidth',
                'type'     => 'spinner', 
                'title'    => __('Single Product Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),



            // Total without VAR
            array(
                'id'       => 'contentItemsShowTotalWithoutVATName',
                'type'     => 'text',
                'title'    => __( 'Total Without Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Total excl. Taxes', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowTotalWithoutVATWidth',
                'type'     => 'spinner', 
                'title'    => __('Total Without Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // Total Taxes (%)
            array(
                'id'       => 'contentItemsShowVATInPercentName',
                'type'     => 'text',
                'title'    => __( 'Total Without Taxes (%) Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Taxes (in %)', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowVATInPercentWidth',
                'type'     => 'spinner', 
                'title'    => __('Total Without Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
            array(
                'id'       => 'contentItemsShowVATInPercentDisableAutomatic',
                'type'     => 'checkbox',
                'title'    => __( 'Total Without Taxes - Disable Automatic Calculation', 'woocommerce-pdf-invoices' ),
                'subtitle'    => __( 'Use tax rates on product level instead of automatic calculation.', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),


            // Taxes per Product
            array(
                'id'       => 'contentItemsShowVATName',
                'type'     => 'text',
                'title'    => __( 'Total Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('VAT', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowVATWidth',
                'type'     => 'spinner', 
                'title'    => __('Total Taxes Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),

            // Coupon tota
            array(
                'id'       => 'contentItemsShowCouponTotalName',
                'type'     => 'text',
                'title'    => __( 'Coupon Total Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Discount', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowCouponTotalWidth',
                'type'     => 'spinner', 
                'title'    => __('Coupon Total Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),


            // Total
            array(
                'id'       => 'contentItemsShowTotalName',
                'type'     => 'text',
                'title'    => __( 'Product Total Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Total', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowTotalWidth',
                'type'     => 'spinner', 
                'title'    => __('Product Total Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
            array(
                'id'       => 'contentItemsShowTotalSaved',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Total Saved Price', 'woocommerce-pdf-invoices' ),
                'subtitle'    => __( 'When a product is on sale or a coupon is applied it will show the difference amount that is saved.', 'woocommerce-pdf-invoices' ),
                'default' => 0,
            ),
                array(
                    'id'       => 'contentItemsShowTotalSavedText',
                    'type'     => 'text',
                    'title'    => __( 'Product Total Saved Price Text', 'woocommerce-pdf-invoices' ),
                    'default'  => __('You saved: %s', 'woocommerce-pdf-invoices'),
                    'required' => array('contentItemsShowTotalSaved','equals','1'),
                ),

            array(
                'id'       => 'contentItemsShowWPOvernightBarcodeName',
                'type'     => 'text',
                'title'    => __( 'WP Overnight Barcode Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Barcode', 'woocommerce-pdf-invoices'),
            ),
            array(
                'id'     =>'contentItemsShowWPOvernightBarcodeWidth',
                'type'     => 'spinner', 
                'title'    => __('WP Overnight Barcode Size (%)', 'woocommerce-pdf-invoices'),
                'default'  => '9',
                'min'      => '1',
                'step'     => '10',
                'max'      => '100',
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Totals Data To Show', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'totalsDataToShow',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'totalsDataEnable',
                'type'     => 'switch',
                'title'    => __( 'Enable Totals Data Reordering', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Disable totals data reordering when you have custom totals fields like paypal fees or similar. When disabled the default Woo totals ordering will be used.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
            ),
            array(
                'id'      => 'totalsData',
                'type'    => 'sorter',
                'title'   => 'Modules',
                'subtitle'    => 'Totals Data to Show.<br>* Tax total only works when you set Settings > Tax > Display tax totals > As single Total.',
                'options' => array(
                    'enabled'  => array(
                        'subtotal_without_taxes' => __('Subtotal excl. Taxes', 'woocommerce-pdf-invoices'),
                        'subtotal_with_taxes' => __('Subtotal incl. Taxes', 'woocommerce-pdf-invoices'),
                        'shipping' => __('Shipping Method', 'woocommerce-pdf-invoices'),
                        'payment_method' => __('Payment Method', 'woocommerce-pdf-invoices'),
                        'tax_rates' => __('Tax Rates', 'woocommerce-pdf-invoices'),
                        'discount_total' =>  __('Total Discount', 'woocommerce-pdf-invoices'),
                        'fees' =>  __('Fees', 'woocommerce-pdf-invoices'),
                        'refunded' =>  __('Refunded', 'woocommerce-pdf-invoices'),
                        'order_total' => __('Order Total', 'woocommerce-pdf-invoices'),
                    ),
                    'disabled' => array(
                        'discount' =>  __('Discounts (Single)', 'woocommerce-pdf-invoices'),
                        'shipping_with_taxes' => __('Shipping incl. Taxes', 'woocommerce-pdf-invoices'),
                        'shipping_without_taxes' => __('Shipping excl. Taxes', 'woocommerce-pdf-invoices'),
                        'shipping_taxes' => __('Shipping Taxes', 'woocommerce-pdf-invoices'),
                        'cart_subtotal' => __('Cart Subtotal', 'woocommerce-pdf-invoices'),
                        'total_without_taxes' => __('Total excl. Taxes', 'woocommerce-pdf-invoices'),
                        'total_with_taxes' => __('Total incl. Taxes', 'woocommerce-pdf-invoices'),
                        'tax' => __('Tax Total *', 'woocommerce-pdf-invoices'),
                    )
                ),
                'required' => array('totalsDataEnable','equals','1'),
            ),

            // TFOOTER
            // Subtotal 
            array(
                'id'       => 'contentItemsTotalsShowSubtotalWithTaxesName',
                'type'     => 'text',
                'title'    => __( 'Subtotal with Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Subtotal incl. VAT', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),

            array(
                'id'       => 'contentItemsTotalsShowSubtotalWithoutTaxesName',
                'type'     => 'text',
                'title'    => __( 'Subtotal without Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Subtotal excl. VAT', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),

            array(
                'id'       => 'generalShowTaxRate',
                'type'     => 'checkbox',
                'title'    => __( 'Show Tax Rates', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Instead of showing the tax amount also show the tax rate.', 'woocommerce-pdf-invoices' ),
                'default' => 1,
                'required' => array('totalsDataEnable','equals','1'),
            ),

            array(
                'id'       => 'generalShowTaxesBeforeTotalText',
                'type'     => 'text',
                'title'    => __('Tax rate text', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'You can use 3 variables here: first is the tax rate, then tax name and tax label. Use: {{taxRate}} or {{taxName}} or {{taxLabel}}', 'woocommerce-pdf-invoices' ),
                'default'  => __( '{{taxLabel}} ({{taxRate}}):', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),


            array(
                'id'       => 'contentItemsTotalsShowRefundedTotalName',
                'type'     => 'text',
                'title'    => __( 'Refunded', 'woocommerce-pdf-invoices' ),
                'default'  => __('Refunded', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
            array(
                'id'       => 'contentItemsTotalsShowTotalWithTaxesName',
                'type'     => 'text',
                'title'    => __( 'Total incl. VAT', 'woocommerce-pdf-invoices' ),
                'default'  => __('Total incl. VAT', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
            array(
                'id'       => 'contentItemsTotalsShowTotalWithoutTaxesName',
                'type'     => 'text',
                'title'    => __( 'Total excl. Taxes Name', 'woocommerce-pdf-invoices' ),
                'default'  => __('Total excl. VAT', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),

            array(
                'id'       => 'contentItemsTotalsShowShippingWithoutTaxesName',
                'type'     => 'text',
                'title'    => __( 'Shipping excl. Taxes', 'woocommerce-pdf-invoices' ),
                'default'  => __('Shipping excl. Taxes', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
            array(
                'id'       => 'contentItemsTotalsShowShippingWithTaxesName',
                'type'     => 'text',
                'title'    => __( 'Shipping incl. Taxes', 'woocommerce-pdf-invoices' ),
                'default'  => __('Shipping incl. Taxes', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
            array(
                'id'       => 'contentItemsTotalsShowShippingTaxesName',
                'type'     => 'text',
                'title'    => __( 'Shipping Taxes', 'woocommerce-pdf-invoices' ),
                'default'  => __('Shipping Taxes', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
            array(
                'id'       => 'contentItemsTotalsShowDiscountsTotal',
                'type'     => 'text',
                'title'    => __( 'Total Discount', 'woocommerce-pdf-invoices' ),
                'default'  => __('Total Discount', 'woocommerce-pdf-invoices'),
                'required' => array('totalsDataEnable','equals','1'),
            ),
        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Footer', 'woocommerce-pdf-invoices' ),
        // 'desc'       => __( '', 'woocommerce-pdf-invoices' ),
        'id'         => 'footer',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enableFooter',
                'type'     => 'switch',
                'title'    => __( 'Enable', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Enable footer', 'woocommerce-pdf-invoices' ),
                'default' => '1',
            ),
            array(
                'id'     =>'footerBackgroundColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Footer background color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#F7F7F7',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerTextColor',
                'type'  => 'color',
                'url'      => true,
                'title' => __('Footer text color', 'woocommerce-pdf-invoices'), 
                'validate' => 'color',
                'default' => '#333333',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerFontSize',
                'type'     => 'spinner', 
                'title'    => __('Footer font size', 'woocommerce-pdf-invoices'),
                'default'  => '8',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),
            array(
                'id'     =>'footerLayout',
                'type'  => 'select',
                'title' => __('Footer Layout', 'woocommerce-pdf-invoices'), 
                'required' => array('enableFooter','equals','1'),
                'options'  => array(
                    'oneCol' => __('1/1', 'woocommerce-pdf-invoices' ),
                    'twoCols' => __('1/2 + 1/2', 'woocommerce-pdf-invoices' ),
                    'threeCols' => __('1/3 + 1/3 + 1/3', 'woocommerce-pdf-invoices' ),
                    'fourCols' => __('1/4 + 1/4 + 1/4 + 1/4', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'fourCols',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerMargin',
                'type'     => 'spinner', 
                'title'    => __('Footer Margin', 'woocommerce-pdf-invoices'),
                'default'  => '10',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerHeight',
                'type'     => 'spinner', 
                'title'    => __('Footer Height', 'woocommerce-pdf-invoices'),
                'default'  => '30',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerTopLeft',
                'type'  => 'select',
                'title' => __('Top Left Footer', 'woocommerce-pdf-invoices'), 
                'required' => array('enableFooter','equals','1'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'text',
            ),
            array(
                'id'     =>'footerTopLeftText',
                'type'  => 'editor',
                'title' => __('Top Left Footer Text', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopLeft','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'Company<br>
                    Address 123<br>
                    1234 City<br>
                    Country'
            ),
            array(
                'id'     =>'footerTopLeftImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Left Footer Image', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopLeft','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopMiddleLeft',
                'type'  => 'select',
                'title' => __('Top Middle Left Footer', 'woocommerce-pdf-invoices'), 
                'required' => array('footerLayout','equals', array('fourCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'text',
            ),
            array(
                'id'     =>'footerTopMiddleLeftText',
                'type'  => 'editor',
                'title' => __('Top Middle Left Footer Text', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopMiddleLeft','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'Tel.: 0160 123 1534<br>
                    E-Mail: info@yourdomain.com<br>
                    Web: https://yourdomain.com'
            ),
            array(
                'id'     =>'footerTopMiddleLeftImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Middle Left Footer Image', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopMiddleLeft','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopMiddleRight',
                'type'  => 'select',
                'title' => __('Top Middle Right Footer', 'woocommerce-pdf-invoices'), 
                'required' => array('footerLayout','equals', array('fourCols','threeCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'text',
            ),
            array(
                'id'     =>'footerTopMiddleRightText',
                'type'  => 'editor',
                'title' => __('Top Middle Right Footer Text', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopMiddleRight','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'VAT-ID: 123 435 456<br>
                    Managing Director: Your Name'
            ),
            array(
                'id'     =>'footerTopMiddleRightImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Middle Right Footer Image', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopMiddleRight','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopRight',
                'type'  => 'select',
                'title' => __('Top Right Footer', 'woocommerce-pdf-invoices'), 
                'required' => array('footerLayout','equals', array('fourCols','threeCols','twoCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-pdf-invoices' ),
                    'bloginfo' => __('Blog information', 'woocommerce-pdf-invoices' ),
                    'text' => __('Custom text', 'woocommerce-pdf-invoices' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-pdf-invoices' ),
                    'image' => __('Image', 'woocommerce-pdf-invoices' ),
                    'exportinfo' => __('Export Information', 'woocommerce-pdf-invoices' ),
                ),
                'default' => 'text',
            ),
            array(
                'id'     =>'footerTopRightText',
                'type'  => 'editor',
                'title' => __('Top Right Footer Text', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopRight','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                ),
                'default' => 
                    'Bank: Deutsche Bank<br>
                    IBAN: DE 123345 3 456<br>
                    BIC: GEN0123'
            ),
            array(
                'id'     =>'footerTopRightImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Right Footer Image', 'woocommerce-pdf-invoices'), 
                'required' => array('footerTopRight','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
        )
    ) );


    $framework::setSection( $opt_name, array(
        'title'      => __( 'Integrations', 'woocommerce-pdf-invoices' ),
        'desc'       => __( 'Integrations.', 'woocommerce-pdf-invoices' ),
        'id'         => 'integrations',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'integrationsWooCommerceDelivery',
                'type'     => 'switch',
                'title'    => __( 'WooCommerce Delivery Plugin', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Enable support for our WooCommerce Delivery plugin.', 'woocommerce-pdf-invoices' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'integrationsMame',
                'type'     => 'switch',
                'title'    => __( 'Enable Mame Barcode Support', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'You can use the variable {{mame_barcode}} or {{mame_webstamp}}. https://docs.mamedev.ch/integration-in-lieferscheine-rechnungen/', 'woocommerce-pdf-invoices' ),
                'default'   => 0,
            ),
                array(
                    'id'       => 'integrationsMameBarcodeWidth',
                    'type'     => 'spinner', 
                    'title'    => __('Barcode Width (px)', 'woocommerce-pdf-invoices'),
                    'default'  => '200',
                    'min'      => '1',
                    'step'     => '1',
                    'max'      => '9999',
                    'required' => array('integrationsMame','equals','1'),
                ),
                array(
                    'id'       => 'integrationsMameWebstampWidth',
                    'type'     => 'spinner', 
                    'title'    => __('Webstamp Width (px)', 'woocommerce-pdf-invoices'),
                    'default'  => '200',
                    'min'      => '1',
                    'step'     => '1',
                    'max'      => '9999',
                    'required' => array('integrationsMame','equals','1'),
                ),

        )
    ) );

    $framework::setSection( $opt_name, array(
        'title'      => __( 'Advanced settings', 'woocommerce-pdf-invoices' ),
        'desc'       => __( 'Custom stylesheet / javascript.', 'woocommerce-pdf-invoices' ),
        'id'         => 'advanced',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'debugMode',
                'type'     => 'switch',
                'title'    => __( 'Enable Debug Mode', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'This stops creating the PDF and shows the plain HTML.', 'woocommerce-pdf-invoices' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'debugMPDF',
                'type'     => 'switch',
                'title'    => __( 'Enable MPDF Debug Mode', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Show image , font or other errors in the PDF Rendering engine.', 'woocommerce-pdf-invoices' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'showOldInvoices',
                'type'     => 'switch',
                'title'    => __( 'Show & Keep old Invoices', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Shows old invoices on order pages in backend only.', 'woocommerce-pdf-invoices' ),
                'default'   => 1,
            ),
            array(
                'id'     => 'rolesExclude',
                'type'   => 'select',
                'data'   => 'roles',
                'title'  => __('Exclude User Roles', 'woocommerce-pdf-invoices'),
                'subtitle' => __('Select user roles, where the plugin should NOT generate invoices.', 'woocommerce-pdf-invoices'),
                'multi'    => true,
                'default'  => '',
            ),
            array(
                'id'     => 'rolesInclude',
                'type'   => 'select',
                'data'   => 'roles',
                'title'  => __('Include User Roles', 'woocommerce-pdf-invoices'),
                'subtitle' => __('Select user roles, where the plugin should ONLY generate invoices.', 'woocommerce-pdf-invoices'),
                'multi'    => true,
                'default'  => '',
            ),
            array(
                'id'     => 'excludeWCB2BGroups',
                'type'   => 'select',
                'data' => 'posts',
                'args' => array('post_type' => array('wcb2b_group'), 'posts_per_page' => -1),
                'multi' => true,
                'ajax'  => true,
                'title'  => __('Exclude WCB2B Groups', 'woocommerce-pdf-invoices'),
                'subtitle' => __('Requires the WooCommerce B2B Plugin', 'woocommerce-pdf-invoices'),
                'default'  => '',
            ),
            array(
                'id'       => 'qrCodeSize',
                'type'     => 'text',
                'title'    => __('QR Code Size', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Default: 0.8', 'woocommerce-pdf-invoices' ),
                'default'  => '0.8',
            ),
            array(
                'id'       => 'barCodeSize',
                'type'     => 'text',
                'title'    => __('Barcode Size', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Default: 0.8', 'woocommerce-pdf-invoices' ),
                'default'  => '0.8',
            ),
            array(
                'id'       => 'barCodeType',
                'type'     => 'text',
                'title'    => __('Barcode Type', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Default: C128A, other possibilities: https://mpdf.github.io/reference/html-control-tags/barcode.html', 'woocommerce-pdf-invoices' ),
                'default'  => 'C128A',
            ),
            array(
                'id'       => 'barCodeValue',
                'type'     => 'select',
                'title'    => __( 'Barcode Value', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Choose the value of that the barcode gets generated.', 'woocommerce-pdf-invoices' ),
                'options'  => array(
                    'invoice_number' => __('Invoice Number', 'woocommerce-pdf-invoices'),
                    'id' => __('Order ID', 'woocommerce-pdf-invoices'),
                ),
                'default' => 'id',
            ),
            array(
                'id'       => 'dateFormat',
                'type'     => 'text',
                'title'    => __('Date Format', 'woocommerce-pdf-invoices'),
                'subtitle' => __( 'Specify the date format. Default is ' . get_option('date_format'), 'woocommerce-pdf-invoices' ),
                'default'  => get_option('date_format'),
            ),
            array(
                'id'       => 'customCSS',
                'type'     => 'ace_editor',
                'mode'     => 'css',
                'title'    => __( 'Custom CSS', 'woocommerce-pdf-invoices' ),
                'subtitle' => __( 'Add some stylesheet if you want.', 'woocommerce-pdf-invoices' ),
            ),
        )
    ));


    $framework::setSection( $opt_name, array(
        'title'  => __( 'Preview', 'woocommerce-pdf-invoices' ),
        'id'     => 'preview',
        'desc'   => __( 'Need support? Please use the comment function on codecanyon.', 'woocommerce-pdf-invoices' ),
        'icon'   => 'el el-eye-open',
    ) );

    /*
     * <--- END SECTIONS
     */
