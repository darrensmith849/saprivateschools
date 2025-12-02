<?php

if ( ! empty( $related_articles ) ) {
    $list = '';

    foreach ( $related_articles as $article ) {
        $docId            = isset( $article->docId ) ? $article->docId : '';
        $selectedCategory = isset( $article->selectedCategory ) ? $article->selectedCategory : '';
        $svg_icon         = '<svg xmlns="http://www.w3.org/2000/svg" class="external" height="14" width="14" viewBox="0 0 512 512"><path d="M432 320H400a16 16 0 0 0 -16 16V448H64V128H208a16 16 0 0 0 16-16V80a16 16 0 0 0 -16-16H48A48 48 0 0 0 0 112V464a48 48 0 0 0 48 48H400a48 48 0 0 0 48-48V336A16 16 0 0 0 432 320zM488 0h-128c-21.4 0-32.1 25.9-17 41l35.7 35.7L135 320.4a24 24 0 0 0 0 34L157.7 377a24 24 0 0 0 34 0L435.3 133.3 471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"></path></svg>';

        if ( $docId == 'all-docs' ) {
            $args = [
                'post_type'   => 'docs',
                'numberposts' => -1,
                'tax_query'   => [
                    [
                        'taxonomy'         => 'doc_category',
                        'terms'            => $selectedCategory,
                        'include_children' => false
                    ]
                ]
            ];
            $posts = get_posts( $args );
            foreach ( $posts as $doc ) {
                $post_title     = isset( $doc->post_title ) ? $doc->post_title : '';
                $post_permalink = get_permalink( $doc->ID );
                $post_type      = get_post_type( $doc->ID  );
                $post_status    = get_post_status( $doc->ID  );
                if ( $post_type === 'docs' && $post_status === 'publish' ) {
                    $list .= '<li>
                                <a href="' . ( $post_permalink ) . '">' . ( $post_title ) . '</a>' .
                                '<a href="' . ( $post_permalink ) . '">' . $svg_icon . '</a>
                             </li>';
                }
            }
        } else {
            $post_object    = get_post( $docId );
            $post_permalink = get_permalink( $docId );
            $post_type      = get_post_type( $docId );
            $post_status    = get_post_status( $docId );
            $post_title     = isset( $post_object->post_title ) ? $post_object->post_title : '';
            if ( $post_type === 'docs' && $post_status === 'publish' ) {
                $list .= '<li>
                                <a href="' . ( $post_permalink ) . '">' . ( $post_title ) . '</a>' .
                                '<a href="' . ( $post_permalink ) . '">' . $svg_icon . '</a>
                        </li>';
            }
        }
    }

    echo '<div class="betterdocs-related-articles-container-front">' .
        ( $show_title ? '<p class="related-articles-title">' . $title . '</p>' : '' ) . '
        <ul class="related-articles-list">' . ( $list ) . '</ul>
        </div>';
}
