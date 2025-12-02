<?php if ($explore_count > 0 && !isset($_GET['encyclopedia_prefix'])) : ?>
    <?php if (empty($explore_more_text_color)) : 
        $explore_more_text_color = '#667085';
     endif; ?>

    <div class="encyclopedia-item explore-more-docs">
        <a href="<?php echo esc_url($explore_url); ?>" target="_blank">
            <?php printf('<span>%s</span>', sprintf(esc_html__('Explore %d+ More Docs', 'your-text-domain'), $explore_count)); ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M7 17L17 7" stroke="<?php echo esc_attr($explore_more_text_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M8 7L17 7L17 16" stroke="<?php echo esc_attr($explore_more_text_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>

        <?php if ($doc_style === 'doc-grid') : ?>
            <a href="<?php echo esc_url($explore_url); ?>" target="_blank" class="tools-card__link-block w-inline-block"></a>
        <?php endif; ?>

    </div>

<?php endif; ?>