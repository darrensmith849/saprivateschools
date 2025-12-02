<?php

global $wp_query;

if (is_page()) {
    $slug = isset($wp_query->query['pagename']) ? $wp_query->query['pagename'] : '';

    update_option('encyclopedia_current_page_slug', $slug);
} elseif (is_single() && empty($wp_query->query['post_type'])) {
    $slug = isset($wp_query->query['name']) ? $wp_query->query['name'] : '';
    update_option('encyclopedia_current_page_slug', $slug);
}

if (empty(get_option('encyclopedia_current_page_slug'))) {
    $slug  = betterdocs()->settings->get('encyclopedia_root_slug', 'encyclopedia');
    update_option('encyclopedia_current_page_slug', $slug);
}

$slug = get_option('encyclopedia_current_page_slug');

$url = home_url() . '/' . $slug . '/';

?>

<div class="encyclopedia-alphabets alphabets-style-<?php echo esc_attr($alphabet_list_style); ?>">
    <ul class="encyclopedia-alphabet-list">
        <?php
        $shown_sections = 0;

        echo '<li class="alphabet-list-item" data-letter="all"><a href="' . esc_url($url) . '">All</a></li>';

        foreach (range('A', 'Z') as $letter) {
            $has_docs = 'class="letter-has-no-docs" href="#"';
            if (!empty($docs_by_letter[$letter])) {
                $has_docs = 'href="' . esc_url($url . '?encyclopedia_prefix=' . $letter) . '"';
            }
            $class = ($current_letter === $letter) ? 'alphabet-list-item active' : 'alphabet-list-item';
            echo '<li class="' . esc_attr($class) . '" data-letter="' . esc_attr($letter) . '"><a ' . $has_docs . '>' . esc_html($letter) . '</a></li>';
            $shown_sections++;
        }
        ?>
    </ul>
</div>