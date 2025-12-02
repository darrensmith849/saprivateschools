<?php

/**
 * Breadcrumbs
 */

$encyclopdeia_page_title  = betterdocs()->settings->get('encyclopedia_page_title', 'Encyclopedia');

$slug  = get_option('encyclopedia_current_page_slug');

$current_letter = strtoupper(substr(get_the_title(), 0, 1));
$doc_title = get_the_title();


if (is_tax('glossaries')) {
    $term = get_queried_object();
    $doc_title = $term->name;
    $current_letter = strtoupper(substr($doc_title, 0, 1));
}

$encyclopdeia_title = home_url() . '/' . $slug . '/';
$encyclopdeia_url = home_url() . '/' . $slug . '/';
$current_letter_url = home_url() . '/' . $slug . '/?encyclopedia_prefix=' . $current_letter;

?>
<?php if (betterdocs()->settings->get('enable_breadcrumb')) : ?>
    <nav class="betterdocs-breadcrumb" id="betterdocs-breadcrumb">
        <ul class="betterdocs-breadcrumb-list">
            <li class="betterdocs-breadcrumb-item item-home">
                <a href="<?php echo esc_url(home_url()); ?>" class="bread-link"><?php echo esc_html__('Home', 'bettedocs'); ?></a> </li>
            <li class="betterdocs-breadcrumb-item breadcrumb-delimiter">
                <span class="icon-container">
                    <svg class="breadcrumb-delimiter-icon svg-inline--fa fa-angle-right fa-w-8" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                        <path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                    </svg>
                </span>
            </li>
            <li class="betterdocs-breadcrumb-item item-cat item-custom-post-type-docs">
                <a href="<?php echo esc_url($encyclopdeia_url); ?>" class="bread-link"><?php echo esc_html($encyclopdeia_page_title); ?></a> </li>
            <li class="betterdocs-breadcrumb-item breadcrumb-delimiter">
                <span class="icon-container">
                    <svg class="breadcrumb-delimiter-icon svg-inline--fa fa-angle-right fa-w-8" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                        <path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                    </svg>
                </span>
            </li>
            <li class="betterdocs-breadcrumb-item">
                <a href="<?php echo esc_url($current_letter_url); ?>" class="bread-link"><?php echo esc_html($current_letter); ?></a> </li>
            <li class="betterdocs-breadcrumb-item breadcrumb-delimiter">
                <span class="icon-container">
                    <svg class="breadcrumb-delimiter-icon svg-inline--fa fa-angle-right fa-w-8" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                        <path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                    </svg>
                </span>
            </li>
            <li class="betterdocs-breadcrumb-item item-current item-506 current">
                <span><?php echo esc_html($doc_title); ?></span> </li>
        </ul>
    </nav>

<?php endif; ?>


<header class="betterdocs-entry-header">
    <div class="docs-single-title">
        <?php
        // Display the term title
        if (is_tax('glossaries')) {
            echo  '<h1 class="betterdocs-entry-title" id="betterdocs-entry-title">'.esc_html($term->name).'</h1>';
        } else {
            /**
             * Title
             */
            $view_object->get(
                'templates/parts/title',
                [
                    'tag' => betterdocs()->customizer->defaults->get('betterdocs_post_title_tag')
                ]
            );
        }

        ?>
    </div>
</header> <!-- .entry-header -->