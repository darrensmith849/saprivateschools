
<?php 

$learnmore_text = 'Learn More';

if(!empty($dictionary_learn_more_text))
{
    $learnmore_text = $dictionary_learn_more_text;
}

?>
<div class="encyclopedia-item" data-first-letter="<?php echo $letter; ?>">

    <div class="tools-card">
        <a href="<?php echo esc_url($doc['permalink']); ?>">
            <h2 class="heading-small tools-card__title-text"><?php echo esc_html($doc['post_title']); ?> </h2>
        </a>
        <div class="top-tools-card">
            <p class="text-size-small tools-card__sample-text tooltip-content"><?php echo esc_html($excerpt); ?></p>
            <div class="tools-card_link-container">
                <a href="<?php echo esc_url($doc['permalink']); ?>" class="text-style-link"><?php echo esc_html($learnmore_text); ?><span class="text-style-link tools-card_arrow">→</span></a>
            </div>
        </div>
    </div>

</div>