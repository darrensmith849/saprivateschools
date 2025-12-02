<div class="<?php echo $blockId; ?> betterdocs-entry-body betterdocs-taxonomy-doc-category">
    <div class="layout-6 betterdocs-category-grid-list-inner-wrapper">
        <div class="betterdocs-single-category-inner">
            <?php
                $args = betterdocs()->query->docs_query_args( [
                    'term_id'        => isset( $term->term_id ) ? $term->term_id : '',
                    'term_slug'      => isset( $term->slug ) ? $term->slug : '',
                    'posts_per_page' => $posts_per_page
                ] );

                $post_query = new WP_Query( $args );

                if ( $post_query->have_posts() ):
            ?>
            <div class="betterdocs-body">
                <ul>
                    <?php
                        while ( $post_query->have_posts() ): $post_query->the_post();
                            echo wp_sprintf(
                                '<li><a href="%s"><p>%s</p> %s</a><p>%s</p></li>',
                                esc_attr( esc_url( get_the_permalink() ) ),
                                betterdocs()->template_helper->kses( get_the_title() ),
                                betterdocs()->template_helper->icon( 'arrow-right', false ),
                                wp_trim_words( get_the_content(), 20 )
                            );
                        endwhile;
                        wp_reset_query();
                    ?>
                </ul>
                <?php
                    endif;
                ?>
            </div>
        </div>
    </div>
</div>
