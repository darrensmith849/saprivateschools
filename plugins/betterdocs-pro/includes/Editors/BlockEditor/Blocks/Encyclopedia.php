<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class Encyclopedia extends Block
{

    public $is_pro = true;

    protected $editor_scripts = [
        'betterdocs-pro-blocks-editor',
        'betterdocs-encyclopedia',
        'betterdocs-glossary-suggestion'
    ];

    protected $editor_styles = [
        'betterdocs-fontawesome',
        'betterdocs-pro-blocks-editor',
        'betterdocs-encyclopedia'
    ];

    protected $frontend_styles = [
        'betterdocs-fontawesome',
        'betterdocs-encyclopedia'
    ];

    protected $frontend_scripts = [
        'betterdocs-encyclopedia'
    ];

    /**
     * unique name of block
     * @return string
     */
    public function get_name()
    {
        return 'betterdocs-encyclopedia';
    }

    public function get_default_attributes()
    {
        return [
            'blockId'           => '',
            'docStyle'              => 'doc-grid',
            'startLetterStyle'      => 'alphabet-big-round-view',
            'startLetterStyle_'     => 'alphabet-list-view',
            'alphabetListStyle'     => 'box',
            'dictionaryLoadMore'    => true,
            'dictionaryPerPage'     => '5',
            'dictionaryDocsLoadMore' => true,
            'dictionaryDocsPerPage' => '10',
        ];
    }


    public function render($attributes, $content)
    {
        $attributes = &$this->attributes;

        $default_controls = [
            'doc_style' => isset($attributes['docStyle']) ? $attributes['docStyle'] : '',
            'start_letter_style' => isset($attributes['startLetterStyle']) ? $attributes['startLetterStyle'] : '',
            'start_letter_style_' => isset($attributes['startLetterStyle_']) ? $attributes['startLetterStyle_'] : '',
            'alphabet_list_style' => isset($attributes['alphabetListStyle']) ? $attributes['alphabetListStyle'] : '',
            'dictionary_loadmore' => isset($attributes['dictionaryLoadMore']) ? $attributes['dictionaryLoadMore'] : '',
            'dictionary_per_page' => isset($attributes['dictionaryPerPage']) ? $attributes['dictionaryPerPage'] : '',
            'dictionary_loadmore_button_text'  => isset($attributes['dictionaryLoadMoreText']) ? $attributes['dictionaryLoadMoreText'] : '',
            'dictionary_learn_more_text'       => isset($attributes['dictionaryLearnMoreText']) ? $attributes['dictionaryLearnMoreText'] : '',
            'dictionary_docs_loadmore' => isset($attributes['dictionaryDocsLoadMore']) ? $attributes['dictionaryDocsLoadMore'] : '',
            'dictionary_docs_per_page' => isset($attributes['dictionaryDocsPerPage']) ? $attributes['dictionaryDocsPerPage'] : '',
            'explore_more_text_color' => isset($attributes['exploreMoreTextColor']) ? $attributes['exploreMoreTextColor'] : '',

            'is_customizer' => false

        ];

        // echo do_shortcode("[betterdocs_encyclopedia $convertedString  /]");
        echo '<div class="betterdocs-blocks ' . esc_attr($attributes['blockId']) . ' betterdocs-pro">';
        betterdocs_pro()->views->get('layouts/encyclopedia/default', $default_controls);
        echo '</div>';
    }
}
