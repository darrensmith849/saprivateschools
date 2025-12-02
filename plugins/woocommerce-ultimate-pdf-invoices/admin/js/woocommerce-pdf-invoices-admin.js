(function( $ ) {
	'use strict';

	var openPreviewBtn = $('.woocommerce_page_woocommerce_pdf_invoices_options_options #14_section_group_li_a, .-d9-88-d9-88-da-a9-d8-a7-d9-85-d8-b1-d8-b3_page_woocommerce_pdf_invoices_options_options #14_section_group_li_a');
	var previewFrameContainer = $('#pdf-invoices-preview-frame-container');
	var previewFrame = $('#pdf-invoices-preview-frame');
	var previewFrameSpinner = $('#pdf-invoices-preview-spinner');
	var previewOrderID= $('#pdf-invoices-preview-order-id');
	var overlay = $('.pdf-invoices-preview-frame-overlay');
	var url = window.location.href.split('?')[0];

	previewFrame.on('load', function(){
        $(this).show();
        previewFrameSpinner.hide();
        previewFrame.show();
    });

	openPreviewBtn.on('click', function(e) {
		e.preventDefault();

		var order_id = $(previewOrderID).val();

		overlay.fadeIn();
		previewFrameContainer.fadeIn();
		previewFrameSpinner.show();

		previewFrame.attr("src", url + '?create_pdf_invoice=' + order_id);

	});

	previewOrderID.on('change', function(e) {

		var order_id = $(this).val();

		previewFrame.hide();
		previewFrameSpinner.show();
		previewFrame.attr("src", url + '?create_pdf_invoice=' + order_id);
	})

	overlay.on('click', function(e) {
		e.preventDefault();
		previewFrameContainer.fadeOut();
		overlay.fadeOut();
	});

	// on upload button click
	$('body').on( 'click', '.woocommerce-pdf-invoices-upload-image', function(e){

		e.preventDefault();

		var button = $(this),
		custom_uploader = wp.media({
			title: 'Insert image',
			library : {
				// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
				type : [ 'image', 'application/pdf' ]
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false
		}).on('select', function() { // it also has "open" and "close" events
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			console.log(attachment);
			button.text( attachment.title );
			button.next().val(attachment.id).trigger('change');
		}).open();
	
	});

	// on remove button click
	$('body').on('click', '.woocommerce-pdf-invoices-remove-image', function(e){

		e.preventDefault();

		var button = $(this);
		button.prev().val('');
		button.prev().prev().text('Choose a file');
	});

})( jQuery );