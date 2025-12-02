(function ($) {

    console.log('Front from theme');

    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 1000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    $(document).on('ready', function () {
        // Check if the browser width is more than 768px
        if ($(window).width() > 768) {
            $('.wc_schools_sidebar_wrapper').addClass('active');
            $('#c27-site-wrapper').addClass('wc_schools_sidebar_open');
        } else {
            $('.wc_schools_sidebar_wrapper').removeClass('active');
            $('#c27-site-wrapper').removeClass('wc_schools_sidebar_open');
        }

        $(document).on('click', '.advertisement-popup-opener', function () {
            // Get the content of the div
            const content = $('.advertisement-popup-content').html();

            // Use SweetAlert2 to display the content
            Swal.fire({
                html: content,  // Inject the content into the popup
                showCloseButton: true,
                showCancelButton: false,
                showConfirmButton: false,
                focusConfirm: false,
                cancelButtonText: 'Cancel',
                didOpen() {
                    console.log('Init Swal')
                    $('.advertisement-select select[name=select_age_group]').trigger('change');
                    $('.advertisement-select select').each(function () {
                        const current_select = $(this);
                        current_select.select2({
                            dropdownParent: $('.swal2-container'),
                            width: '100%',
                        })
                    });
                },
            });
        });
        $('.single-advertisement-product-options .advertisement-select select').each(function () {
            const current_select = $(this);
            current_select.select2({
                width: '100%',
            })
        });


        disableCartButton();
        $('.product-type-advertisement_product p.price').html('Select options to see price');
        $('.advertisement-banner-options select[name=select_age_group]').trigger('change');
        $('.single-advertisement-product-options select[name=select_slot_type]').trigger('change');

        const pushState = history.pushState;
        const replaceState = history.replaceState;

        // Override the pushState method
        history.pushState = function(state) {
            const result = pushState.apply(history, arguments);
            $(window).trigger('urlChanged', [window.location.href]);
            return result;
        };

        // Override the replaceState method
        history.replaceState = function(state) {
            const result = replaceState.apply(history, arguments);
            $(window).trigger('urlChanged', [window.location.href]);
            return result;
        };

        // Listen to the popstate event
        $(window).on('popstate', function() {
            $(window).trigger('urlChanged', [window.location.href]);
        });

        // Custom event listener for URL changes
        $(window).on('urlChanged', function(event, url) {
            console.log('URL changed:', url);
            updateYourComponent(url);
        });

        function updateYourComponent(url) {
            // Your custom logic to update the component based on the new URL
            console.log('Updating component for URL:', url);
            const params = new URLSearchParams(window.location.search);
            console.log(params.get('greater-region'))
            const wrapper = $('.regions_promoted_schools_wrapper');

            if (wrapper.length) {
                const form_data = {
                    nonce: ngd_ajax_object.nonce,
                    action: 'update_regions_slots',
                    region: params.get('greater-region') || '',
                }

                $.ajax({
                    type: 'POST',
                    url: ngd_ajax_object.url,
                    data: form_data,
                    success: function (response) {
                        const res = JSON.parse(response)
                        console.log(res)
                        if (res.status && res.status === 'OK') {
                            wrapper.html(res.html);
                        } else {
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(error);
                    }
                });
            }
        }
    })
    $(document).on('click', '.wc_schools_sidebar_toggle', function (e) {
        e.preventDefault();
        const toggle = $(this);
        const sidebar = toggle.closest('.wc_schools_sidebar_wrapper');
        sidebar.toggleClass('active');
        const site_wrapper = $('#c27-site-wrapper');
        if (sidebar.hasClass('active')) {
            site_wrapper.addClass('wc_schools_sidebar_open');
        } else {
            site_wrapper.removeClass('wc_schools_sidebar_open');
        }
    })
    $(document).on('click', '.advertisement-banner-button:not(.sold_out)', function (e) {
        e.preventDefault();
        const button = $(this);
        const wrapper = button.closest('.advertisement-banner-content');
        const loader = wrapper.find('.advertisement-banner-loader');
        const error_wrapper = wrapper.find('.advertisement-banner-errors');

        const month = button.attr('data-month');
        const year = button.attr('data-year');

        const form_data = {
            nonce: ngd_ajax_object.nonce,
            action: 'add_advertisement_product_to_cart',
            select_month: month + '_' + year,
            select_region: wrapper.find("select[name=select_region]").val() || '',
            select_section: wrapper.find("select[name=select_section]").val() || '',
            select_position: wrapper.find("select[name=select_position]").val() || '',
            select_slot_type: wrapper.find("select[name=select_slot_type]").val() || '',
            select_age_group: wrapper.find("select[name=select_age_group]").val() || '',
            select_school: wrapper.find("select[name=select_school]").val() || '',
        }

        loader.addClass('active');

        $.ajax({
            type: 'POST',
            url: ngd_ajax_object.url,
            data: form_data,
            success: function (response) {
                const res = JSON.parse(response)
                loader.removeClass('active');
                if (res.status && res.status === 'OK') {
                    Toast.fire({
                        icon: "success",
                        title: res.message
                    }).then(function () {
                        // redirect to cart page
                        window.location.href = res.redirect_url;
                    });
                } else {
                    error_wrapper.html(res.message);
                }
            },
            error: function (xhr, status, error) {
                console.log(error);
            }
        });

    });
    $(document).on('change', '[name="select_age_group"]', function () {
        const select = $(this);
        const wrapper = select.closest('.advertisement-banner-options');
        const school_selector = wrapper.find('.advertisement-banner-school-select');
        const value = select.val();
        school_selector.find('option').each(function () {
            const option = $(this);
            console.log(option.val());
            if (option.attr('data-school-type') === value
                || option.attr('data-school-type') === 'empty') {
                option.attr('disabled', false).show();
            } else {
                option.attr('disabled', true).hide();
            }
        })
        school_selector.val("");
        school_selector.trigger('change');
    });
    $(document).on('change', '[name="select_slot_type"]', function () {
        const select = $(this);
        const wrapper = select.closest('.advertisement-banner-options');
        const section_selector = wrapper.find('.advertisement-banner-homepage-section-selector');
        const position_selector = wrapper.find('.advertisement-banner-position-selector');
        const region_selector = wrapper.find('.advertisement-banner-region-selector');
        const value = select.val();
        console.log(value)
        if (value === 'homepage') {
            section_selector.slideDown();
            region_selector.slideUp();
            position_selector.slideUp();
        } else if (value === 'regions') {
            section_selector.slideUp();
            region_selector.slideDown();
            position_selector.slideDown();
        } else {
            section_selector.slideUp();
            region_selector.slideUp();
            position_selector.slideUp();
        }
    });
    $(document).on('change', '.single-advertisement-product-options .advertisement-select select', function () {
        const select = $(this);
        const wrapper = select.closest('.entry-summary');
        const price = wrapper.find('p.price');

        const form_data = {
            nonce: ngd_ajax_object.nonce,
            action: 'update_single_product_price',
            region: wrapper.find("select[name=select_region]").val() || '',
            section: wrapper.find("select[name=select_section]").val() || '',
            position: wrapper.find("select[name=select_position]").val() || '',
            month_year: wrapper.find("select[name=select_month]").val() || '',
            slot_type: wrapper.find("select[name=select_slot_type]").val() || '',
            age_group: wrapper.find("select[name=select_age_group]").val() || '',
            school_id: wrapper.find("select[name=select_school]").val() || '',
        }
        disableCartButton()

        $.ajax({
            type: 'POST',
            url: ngd_ajax_object.url,
            data: form_data,
            success: function (response) {
                const res = JSON.parse(response)
                console.log(res)
                if (res.status && res.status === 'OK') {
                    price.html(res.price_html);
                    enableCartButton();
                } else {
                    price.html(res.message);
                }
            },
            error: function (xhr, status, error) {
                console.log(error);
                Toast.fire({
                    icon: "error",
                    title: "Something went wrong 2!"
                });
            }
        });

    })
    $(document).on('change', '.advertisement-banner-wrapper .advertisement-select select', function () {

        const select = $(this);
        const wrapper = select.closest('.advertisement-banner-wrapper');
        const loader = wrapper.find('.advertisement-banner-loader');
        const range_wrapper = wrapper.find('.advertisement-banner-price-range');
        const buttons_wrapper = wrapper.find('.advertisement-banner-buttons');

        const form_data = {
            nonce: ngd_ajax_object.nonce,
            action: 'update_slot_prices',
            region: wrapper.find("select[name=select_region]").val() || '',
            section: wrapper.find("select[name=select_section]").val() || '',
            position: wrapper.find("select[name=select_position]").val() || '',
            slot_type: wrapper.find("select[name=select_slot_type]").val() || '',
            age_group: wrapper.find("select[name=select_age_group]").val() || '',
            select_school: wrapper.find("select[name=select_school]").val() || '',
        }

        loader.addClass('active');

        $.ajax({
            type: 'POST',
            url: ngd_ajax_object.url,
            data: form_data,
            success: function (response) {
                const res = JSON.parse(response)
                console.log(res)
                loader.removeClass('active');
                if (res.status && res.status === 'OK') {
                    range_wrapper.html(res.html.range_html);
                    buttons_wrapper.html(res.html.buttons_html);
                } else {
                }
            },
            error: function (xhr, status, error) {
                console.log(error);
            }
        });

    })


    function disableCartButton() {
        const addToCartButton = $('.product-type-advertisement_product .single_add_to_cart_button');
        addToCartButton.prop('disabled', true);
        addToCartButton.addClass('disabled');
    }

    function enableCartButton() {
        const addToCartButton = $('.product-type-advertisement_product .single_add_to_cart_button');
        addToCartButton.prop('disabled', false);
        addToCartButton.removeClass('disabled');
    }

}(jQuery));