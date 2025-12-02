<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\CSSGenerator;
use WPDeveloper\BetterDocs\Utils\Helper;


class Glossaries extends Base
{
    public $settings;

    public function __construct()
    {

        $enable_glossaries = betterdocs()->settings->get('enable_glossaries', false);

        if ($enable_glossaries && isset($_GET['page']) && $_GET['page'] === 'betterdocs-glossaries') {
            $this->store_glossary_count();
        }
    }


    public function store_glossary_count()
    {
        global $wpdb;
    
        $query = "
            SELECT t.term_id, t.name, t.slug
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'glossaries'
        ";
    
        $glossaries = $wpdb->get_results($query, ARRAY_A);
    
        if (!is_array($glossaries)) {
            update_option('store_glossary_count', false);
        } else {
            $glossaryCounts = array();
    
            $query = "
                SELECT ID, post_title, post_content
                FROM {$wpdb->posts}
                WHERE post_type = 'docs'
                AND post_status = 'publish'
            ";
            $posts = $wpdb->get_results($query, ARRAY_A);
            $glossaryCounts = array();
    
            foreach ($posts as $post) {
                
                $postId = $post['ID'];
                $postTitle = $post['post_title'];
                $permalink = get_the_permalink( $postId);
                $content = $post['post_content'];
    
                foreach ($glossaries as $glossary) {
                    $name = !empty($glossary['name']) ? $glossary['name'] : '';
                    $count = preg_match_all('/\b' . preg_quote($name, '/') . '\b/i', $content, $matches);
    
                    if ($count > 0) {
                        if (!isset($glossaryCounts[$name])) {
                            $glossaryCounts[$name] = array();
                        }
    
                        $glossaryCounts[$name][] = array(
                            'post_title' => $postTitle,
                            'permalink' => $permalink,
                            'count' => $count
                        );
                    }
                }
            }
    
            update_option('store_glossary_count', $glossaryCounts);
        }
    }
    
}
