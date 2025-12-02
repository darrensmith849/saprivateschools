<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class RelatedDocs extends Block {

    public $is_pro = true;

    protected $editor_styles = [
        'betterdocs-pro-blocks-editor'
    ];
    protected $editor_scripts = ['betterdocs-pro-blocks-editor'];

    protected $frontend_styles = ['betterdocs-related-articles'];

    public function get_name() {
        return 'related-docs';
    }

    public function get_default_attributes() {
        return [
            'blockId'            => '',
            'relatedDocsHeading' => __( 'Related Docs', 'betterdocs-pro' )
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'blocks/related-docs' );
    }

    public function view_params() {
        return [
            'title'      => $this->attributes['relatedDocsHeading'],
            'show_title' => true,
            'blockId'    => $this->attributes['blockId']
        ];
    }
}
