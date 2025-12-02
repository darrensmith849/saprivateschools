<?php echo betterdocs()->settings->get('enable_estimated_reading_time') ? do_shortcode('[betterdocs_reading_time]') : ''; ?>
<div class="betterdocs-entry-content" data-postid="<?php echo esc_attr(get_the_id()); ?>">
    <?php

    // Get the current term object
    $term = get_queried_object();

    // Display the term description
    if (!empty($term->description)) {
        echo '<div class="term-description">' . $term->description . '</div>';
    }
    ?>
</div>