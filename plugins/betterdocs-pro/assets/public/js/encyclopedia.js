
jQuery(document).ready(function ($) {
    var page = 1; // Initial page number
    var loading = false;

    // let attributes = $('.loadMoreBtn').data('control-attributes');
    //  attributes = JSON.parse(atob(attributes));

    function loadMoreSection() {

        let attributes = $('.loadMoreBtn').data('control-attributes');

        attributes = JSON.parse(atob(attributes));

        console.log(attributes);

        if (!loading) {
            loading = true;
            $('.loadMoreBtn').text('Loading...');


            const totalDocSections = $('.loadMoreBtn').data('total-section-pages');


            $.ajax({
                url: betterdocsEncyclopedia.ajax_url, // Make sure to localize this variable in WordPress
                type: 'POST',
                data: {
                    action: 'load_more_docs_section',
                    page: page,
                    doc_style: attributes?.doc_style,
                    doc_per_page: attributes?.dictionary_docs_per_page,
                    section_per_page: attributes?.shown_sections,
                    current_letter: attributes?.current_letter,
                    start_letter_style: attributes?.start_letter_style,
                    _nonce: betterdocsEncyclopedia._nonce
                },
                success: function (response) {
                    console.log(response);

                    if (response.data) {

                        setTimeout(() => {
                            $('#encyclopedia-container').append(response.data);
                            loading = false;
                            $('.loadMoreBtn').text(attributes && attributes.dictionary_loadmore_button_text ? attributes.dictionary_loadmore_button_text : 'Load More');
                            page++;

                            if (page > totalDocSections) {
                                $('.loadMoreBtn').remove();
                            }
                        }, 300);
                    } else {
                        $('.loadMoreBtn').remove();
                    }
                },
                complete: function () {
                    loading = false;
                },
            });

        }
    }

    // Load more on button click
    $('.loadMoreBtn').on('click', function () {
        loadMoreSection();
    });
});


jQuery(document).ready(function ($) {
    var page = 0; // Initial page number for AJAX request
    var loading = false; // Flag to prevent multiple AJAX requests


    // Function to load more docs via AJAX
    function loadMoreDocs() {

        const currentUrl = new URL(window.location.href);
        const currentLetter = currentUrl.searchParams.get('encyclopedia_prefix');
        const totalDocPages = $('.loadMoreDocsBtn').data('total-doc-pages');
        let attributes = $('.loadMoreDocsBtn').data('control-attributes');
        attributes = JSON.parse(atob(attributes));



        if (!loading) {
            loading = true;
            $('.loadMoreDocsBtn').text('Loading more docs...');

            $.ajax({
                url: betterdocsEncyclopedia.ajax_url,
                type: 'post',
                data: {
                    action: 'load_more_docs',
                    page: page,
                    encyclopedia_prefix: currentLetter,
                    doc_style: attributes?.doc_style,
                    doc_per_page: attributes?.dictionary_docs_per_page,
                    current_letter: attributes?.current_letter,
                    _nonce: betterdocsEncyclopedia._nonce,
                },
                success: function (response) {
                    if (response.data) {
                        setTimeout(() => {
                            $('#encyclopedia-container .section-item').append(response.data);
                            loading = false;
                            page++;
                            $('.loadMoreBtn').text(attributes && attributes.dictionary_loadmore_button_text ? attributes.dictionary_loadmore_button_text : 'Load More');

                            if (page > totalDocPages) {
                                $('.loadMoreDocsBtn').remove();
                            }
                        }, 300);
                    } else {
                        $('.loadMoreDocsBtn').remove();
                    }
                }
            });
        }
    }

    // Click event for Load More button
    $('.loadMoreDocsBtn').on('click', function () {
        loadMoreDocs();
    });


});



