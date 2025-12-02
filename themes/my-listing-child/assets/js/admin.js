(function ($) {
    console.log('Admin Area from Theme updated');
    $( document ).on( 'ready', function() {
        jQuery( '.general_options.show_if_advertisement_product' ).show();

        jQuery('.product_data_tabs .general_tab').addClass('show_if_advertisement_product').show();
        jQuery('#general_product_data .pricing').addClass('show_if_advertisement_product').show();
        //for Inventory tab
        jQuery('.inventory_options').addClass('show_if_advertisement_product').show();
        jQuery('#inventory_product_data ._manage_stock_field').addClass('show_if_advertisement_product').show();
        jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_advertisement_product').show();
        jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_advertisement_product').show();
    });
}(jQuery));