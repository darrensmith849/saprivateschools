<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class PopularDocs extends Block {
    public $is_pro = true;

    protected $editor_scripts = [
        'betterdocs-pro-blocks-editor'
    ];

    protected $editor_styles = [
        'betterdocs-pro-blocks-editor',
        'betterdocs-popular-articles'
    ];

    protected $frontend_styles = [
        'betterdocs-popular-articles',
    ];

    public function get_name() {
        return 'popular-docs';
    }

    public function get_default_attributes() {
        return [
            'blockId'         => '',
            'numberOfDocs'    => 8,
            'popularDocsText' => __( 'Popular Docs', 'betterdocs' ),
            'titleTag'        => 'h2',
            'sortDocs'        => 'DESC'
        ];
    }

    public function view_params() {
        $attributes = &$this->attributes;

        $class   = ["betterdocs-popular-articles-wrapper {$attributes['blockId']}"];

        return [
            'wrapper_attr'       => [
                'class' => $class
            ],
            'nested_subcategory' => false,
            'list_icon_name'     => 'list',
            'title_tag'          => $attributes['titleTag'],
            'title'              => $attributes['popularDocsText'],
            'query_args'         => $this->betterdocs( 'query' )->docs_query_args( [
                'post_type'      => 'docs',
                'posts_per_page' => $attributes['numberOfDocs'],
                'meta_key'       => '_betterdocs_meta_views',
                'orderby'        => 'meta_value_num',
                'order'          => $attributes['sortDocs']
            ], ['tax_query'] )
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'layouts/popular-articles/default' );
    }
}
