<?php
$shortcode_atts = betterdocs()->template_helper->shortcode_atts( [
    'title'      => $title,
    'show_title' => $show_title
], '', '' );

echo '<div class="betterdocs-related-articles-root '.$blockId.'">'.do_shortcode( "[betterdocs_related_docs $shortcode_atts]" ).'</div>';
