<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\CSSGenerator;
use WPDeveloper\BetterDocs\Utils\Helper;


class Encyclopedia extends Base
{
    public $settings;

    public function __construct()
    {

        add_action('wp_ajax_load_more_docs_section', [$this, 'load_more_docs_section']);
        add_action('wp_ajax_nopriv_load_more_docs_section', [$this, 'load_more_docs_section']);

        add_action('wp_ajax_load_more_docs', [$this, 'load_more_docs']);
        add_action('wp_ajax_nopriv_load_more_docs', [$this, 'load_more_docs']);

        // functions.php or your custom plugin file
        add_action('wp_ajax_get_current_letter_docs', [$this, 'get_current_letter_docs_callback']);
        add_action('wp_ajax_nopriv_get_current_letter_docs', [$this, 'get_current_letter_docs_callback']);

        // functions.php or your custom plugin file
        add_action('wp_ajax_docs_sort_by_letter', [$this, 'docs_sort_by_letter_callback']);
        add_action('wp_ajax_nopriv_docs_sort_by_letter', [$this, 'docs_sort_by_letter_callback']);

        // add_filter('template_include', [$this, 'custom_template_include']);

        $enable_encyclopedia = betterdocs()->settings->get('enable_encyclopedia', false);

        if ($enable_encyclopedia) {
            $this->create_encyclopedia_page();
        }

        add_action('save_post', [$this, 'update_title_slug'], 10, 3);
    }


    // Function to create an 'encyclopedia' page
    private function create_encyclopedia_page()
    {

        $title  = betterdocs()->settings->get('encyclopedia_page_title', 'Encyclopedia');
        $slug  = betterdocs()->settings->get('encyclopedia_root_slug', 'encyclopedia');

        $post = get_post(get_option('encyclopedia_page_id'));
        if (empty($post) || $post && $post->post_type !== 'page') {
            delete_option('encyclopedia_page_id');
        }

        if (empty(get_option('encyclopedia_page_id'))) {

            $page_args = array(
                'post_title'   => $title,
                'post_content' => '[betterdocs_encyclopedia]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $slug,
            );

            $encyclopedia_page = get_page_by_title($title);

            if (!$encyclopedia_page) {

                $page_id = wp_insert_post($page_args);

                update_option('encyclopedia_page_id', $page_id);

                return $page_id;
            } else {
                return $encyclopedia_page->ID;
            }
        } else {
            $page_data = array(
                'ID'           => get_option('encyclopedia_page_id'),
                'post_title'   => $title,
                'post_name'    => $slug
            );

            wp_update_post($page_data);
        }
    }


    public function update_title_slug($post_id, $post, $update)
    {
        $en_post_id = get_option('encyclopedia_page_id');
        $post = get_post($en_post_id);

        if ($post) {
            $post_title = $post->post_title;
            $post_slug = $post->post_name;
        }

        if ($post_id == $en_post_id) {
            $bd_settings = get_option('betterdocs_settings');
            $bd_settings['encyclopedia_page_title'] = $post_title;
            $bd_settings['encyclopedia_root_slug'] = $post_slug;

            update_option('betterdocs_settings', $bd_settings);
        }
    }


    public function load_more_docs_section()
    {

        $nonce = isset($_POST['_nonce']) ? $_POST['_nonce'] : '';
        $limit = isset($_POST['section_per_page']) ? $_POST['section_per_page'] : 5;

        //     'doc_style' => $doc_style,
        // 'dictionary_docs_per_page' => $dictionary_docs_per_page,
        // 'dictionary_loadmore' => $dictionary_loadmore,
        // 'docs_loadmore' => $dictionary_docs_loadmore,
        // 'current_letter' => $current_letter,
        // 'shown_sections' => $shown_sections,
        // 'total_section_pages' => $total_section_pages,
        // 'total_doc_pages' => $total_doc_pages,

        if (!wp_verify_nonce($nonce, 'encyclopedia_nonce')) {
            die('Invalid nonce');
        }

        $doc_style = isset($_POST['doc_style']) ? $_POST['doc_style'] : 'doc-grid';
        $docs_per_page = isset($_POST['docs_per_page']) ? $_POST['docs_per_page'] : 10;

        // if ($doc_style === 'doc-grid') {
        //     $start_letter_style = isset($_POST['start_letter_style']) ? $_POST['start_letter_style'] : 'alphabet-big-view';
        // } else {
        //     $start_letter_style = isset($_POST['start_letter_style_']) ? $_POST['start_letter_style_'] : 'alphabet-list-view';
        // }


        $start_letter_style = isset($_POST['start_letter_style']) ? $_POST['start_letter_style'] : 'alphabet-big-round-view';



        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $docs_by_letter = Helper::docs_sort_by_letter($limit);

        ob_start();

        // Your code to generate additional content based on the page number goes here
        $max_sections = $limit;
        $shown_sections = 0;
        $available_sections = array_keys(array_filter($docs_by_letter));

        $alphabet_range = array_slice($available_sections, $page * $max_sections, $max_sections);

        foreach ($alphabet_range as $letter) {
            if ($shown_sections >= $max_sections) {
                break;
            }

            if (!empty($docs_by_letter[$letter])) {

                $explore_count = count(Helper::get_current_letter_docs($letter, "")) - $docs_per_page;


                if ($start_letter_style === 'alphabet-list-view') {
                    betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                        'letter' => $letter,
                        'start_letter_style' => $start_letter_style
                    ]);
                }
                echo "<div class='encyclopedia-section-{$letter} section-item'>";

                if ($start_letter_style !== 'alphabet-list-view') {
                    betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                        'letter' => $letter,
                        'start_letter_style' => $start_letter_style
                    ]);
                }

                foreach ($docs_by_letter[$letter] as $doc) {
                    $excerpt = $doc['post_excerpt'];

                    betterdocs_pro()->views->get("layouts/encyclopedia/$doc_style", [
                        'doc' => $doc,
                        'excerpt' => $excerpt
                    ]);
                }

                betterdocs_pro()->views->get("layouts/encyclopedia/explore-count", [
                    'explore_count' => $explore_count,
                    'explore_url' => '?encyclopedia_prefix=' . $letter . '',
                    'doc_style' => $doc_style,

                ]);

                echo '</div>';
                $shown_sections++;
            }
        }

        $output = ob_get_clean();

        wp_send_json_success($output);
    }


    public function load_more_docs()
    {

        $nonce = isset($_POST['_nonce']) ? $_POST['_nonce'] : '';

        if (!wp_verify_nonce($nonce, 'encyclopedia_nonce')) {
            die('Invalid nonce');
        }

        $page = isset($_POST['page']) ? $_POST['page'] : 0;

        $current_letter = isset($_POST['encyclopedia_prefix']) ? $_POST['encyclopedia_prefix'] : '';
        $doc_style = isset($_POST['doc_style']) ? $_POST['doc_style'] : 'doc-grid';
        $docs_per_page = isset($_POST['docs_per_page']) ? $_POST['docs_per_page'] : 10;
        $encyclopeia_suorce  = betterdocs()->settings->get('encyclopedia_source', 'docs');
        $enable_glossaries  = betterdocs()->settings->get('enable_glossaries', false);

        $max_docs = $docs_per_page;

        global $wpdb;

        if($enable_glossaries && $encyclopeia_suorce === 'glossaries'){
            $query = "
                SELECT t.term_id, t.name AS post_title, '' AS post_excerpt, CONCAT('" . get_home_url() . "/glossaries/', t.slug) AS guid, tt.description AS post_content
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'glossaries'
                AND LEFT(t.name, 1) = %s
            ";
        }
        else{
            $query = "
                SELECT ID, post_title, post_excerpt, guid, post_content
                FROM {$wpdb->posts}
                WHERE post_type = 'docs'
                AND post_status = 'publish'
                AND LEFT(post_title, 1) = %s
            ";
        }
        
        $current_letter_docs = $wpdb->get_results($wpdb->prepare($query, $current_letter), ARRAY_A);

        if (!empty($current_letter_docs)) {

            ob_start();

            // Assuming $page is zero-based
            $start_index = $page * $max_docs;

            $docs_range = array_slice($current_letter_docs, $start_index, $max_docs);

            foreach ($docs_range as $doc) {

                if ($start_index >= count($current_letter_docs)) {
                    break;
                }

                $excerpt = !empty($doc['post_excerpt']) ? $doc['post_excerpt'] : Helper::get_custom_excerpt($doc['post_content'], 15);

                if (empty($doc['permalink'])) {
                    if (isset($doc['ID'])) {
                        $doc['permalink'] = get_the_permalink($doc['ID']);
                    } else {
                        $doc['permalink'] = $doc['guid'];
                    }
                }

                betterdocs_pro()->views->get("layouts/encyclopedia/$doc_style", [
                    'doc' => $doc,
                    'excerpt' => $excerpt
                ]);
            }

            $page++;
        }

        $docs_output = ob_get_clean();

        wp_send_json_success($docs_output);
    }


    public function get_current_letter_docs_callback()
    {
        $currentLetter = isset($_POST['currentLetter']) ? sanitize_text_field($_POST['currentLetter']) : '';
        $limit = isset($_POST['limit']) ? sanitize_text_field($_POST['limit']) : '';

        $currentLetterDocs = Helper::get_current_letter_docs($currentLetter, $limit);

        wp_send_json($currentLetterDocs);
        wp_die();
    }


    public function docs_sort_by_letter_callback()
    {
        $limit = isset($_POST['docs_limit']) ? sanitize_text_field($_POST['docs_limit']) : '';

        $docsByLetter = Helper::docs_sort_by_letter($limit);
        wp_send_json($docsByLetter);
        wp_die();
    }

    public function custom_template_include($template) {
        
        if (is_tax('glossaries')) {
            // Use a custom taxonomy template for your custom taxonomy
            $new_template = plugin_dir_path(__FILE__) . 'templates/taxonomy-custom_taxonomy.php';
            if ($new_template != '') {
                return $new_template;
            }
        }
    
        // For other cases, return the original template
        return $template;
    }
    
}
