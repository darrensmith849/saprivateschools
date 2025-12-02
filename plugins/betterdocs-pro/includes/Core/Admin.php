<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocsPro\Admin\Analytics;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Admin extends Base {

    private $settings;
    private $container;

    public function __construct( Settings $settings, Container $container ) {
        $this->settings  = $settings;
        $this->container = $container;

        if ( ! is_admin() ) {
            return;
        }

        /**
         * Restrict Users To View This Column If They Do Not Have Analytics Capability
         */
        if ( current_user_can( 'read_docs_analytics' ) ) {
            add_filter( 'manage_docs_posts_columns', [$this, 'views_columns'] );
            add_filter( 'manage_docs_posts_custom_column', [$this, 'manage_views_columns'], 10, 2 );
        }

        // add_filter( 'plugin_action_links_betterdocs/betterdocs.php', [$this, 'insert_plugin_links'] );
        add_filter( 'admin_body_class', [$this, 'admin_body_classes'] );

        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'] );

        /**
         * doc_category extra meta fields
         */

        add_action( 'betterdocs_doc_category_add_form_after', [$this, 'handbook_layout_cover_image'] );
        add_action( 'betterdocs_doc_category_update_form_after', [$this, 'update_handbook_layout_cover_image'], 10, 1 );

        //save attachments
        add_action( 'save_post_docs', [$this, 'save_docs_attachments'], 10, 1 );

        add_action( 'save_post_docs', [$this, 'save_related_articles'], 11, 1 );

        if ( $this->settings->get( 'show_attachment' ) ) {
            //create meta-box for attachments
            add_action( 'add_meta_boxes', [$this, 'attachment_meta_box_'], 10 );
        }

        if ( $this->settings->get( 'show_related_docs' ) ) {
            //create meta-box for related articles
            add_action( 'add_meta_boxes', [$this, 'related_articles_meta_box_'], 10 );
        }

        if ( $this->settings->get( 'multiple_kb' ) ) {
            /**
             * Add Multiple KB Menu on Dashboad Sidebar
             */
            add_filter( 'betterdocs_admin_menu', [$this, 'menu'] );

            if ( ! isset( $_GET['mode'] ) || trim( $_GET['mode'] ) != 'list' ) {
                add_action( 'betterdocs_admin_header_before_end', [$this, 'add_knowledge_base_filter'] );
            }
            if ( isset( $_GET['mode'] ) && trim( $_GET['mode'] ) == 'list' ) {
                add_action( 'betterdocs_admin_filter_after_category', [$this, 'filter_by_kb'] );
            }
        }

        if ( isset( $_GET['mode'] ) && trim( $_GET['mode'] ) == 'list' ) {
            add_action( 'betterdocs_admin_filter_before_submit', [$this, 'filter_by_view'] );
        }
    }

    public function attachment_meta_box_() {
        add_meta_box( 'betterdocs_attachment_metabox', __( 'Attachments', 'betterdocs' ), [$this, 'render_attchments_markup'], 'docs' );
    }
    public function render_attchments_markup() {
        betterdocs_pro()->views->get( 'admin/attachments/attachment' );
    }

    public function related_articles_meta_box_() {
        add_meta_box( 'betterdocs_related_articles_metabox', __( 'Related Docs', 'betterdocs-pro' ), [$this, 'render_related_articles_markup'], 'docs' );
    }

    public function render_related_articles_markup() {
        betterdocs_pro()->views->get( 'admin/related-articles/related-articles' );
    }

    public function save_docs_attachments( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        $attachments = ! empty( $_POST['betterdocs_article_attachments'] ) ? $_POST['betterdocs_article_attachments'] : [];
        update_post_meta( $post_id, '_betterdocs_attachments', $attachments );
    }

    public function save_related_articles( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        $related_articles = ! empty( $_POST['betterdocs_related_articles'] ) ? $_POST['betterdocs_related_articles'] : [];
        update_post_meta( $post_id, '_betterdocs_related_articles', $related_articles );
    }

    public function views_columns( $columns ) {
        $new_columns = [];

        foreach ( $columns as $key => $value ) {
            if ( $key == 'date' ) {
                $new_columns['betterdocs_views'] = __( 'Views', 'betterdocs-pro' );
            }
            $new_columns[$key] = $value;
        }

        return $new_columns;
    }

    public function manage_views_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'betterdocs_views':
                $views         = (int) $this->container->get( Analytics::class )->get_views( $post_id );
                $analytics_url = admin_url( 'admin.php?page=betterdocs-analytics&betterdocs=' . $post_id . '&comparison_factor=views,feelings' );
                echo ! empty( $views ) ? '<a href="' . $analytics_url . '">' . $views . '</a>' : 0;
                break;
        }
    }

    public function insert_plugin_links( $links ) {
        if ( isset( $links['deactivate'] ) ) {
            $links['deactivate'] = sprintf( __( 'Required by %s', 'betterdocs-pro' ), 'BetterDocs Pro' );
        }

        return $links;
    }

    public function admin_body_classes( $classes ) {
        return $classes . ' betterdocs-pro ';
    }

    public function handbook_layout_cover_image() {
        betterdocs()->views->get( 'admin/taxonomy/handbook-layout-image-add' );
    }

    public function update_handbook_layout_cover_image( $term ) {
        $cat_thumb_id = get_term_meta( $term->term_id, 'doc_category_thumb-id', true );
        betterdocs()->views->get( 'admin/taxonomy/handbook-layout-image-update', [
            'cat_thumb_id' => $cat_thumb_id
        ] );
    }

    public function menu( $menus ) {
        $menus['multiple_kb'] = Helper::normalize_menu(
            __( 'Multiple KB', 'betterdocs-pro' ),
            'edit-tags.php?taxonomy=knowledge_base&post_type=docs',
            'manage_knowledge_base_terms'
        );

        return $menus;
    }

    public function enqueue( $hook ) {
        if ( $hook == 'post-new.php' && get_post_type() == 'docs' || $hook == 'post.php' && get_post_type() == 'docs' ) {
            wp_enqueue_media();
            $parsed_array    = [];
            $attachment_meta = count( (array) get_post_meta( get_the_ID(), '_betterdocs_attachments', true ) ) > 0 ? get_post_meta( get_the_ID(), '_betterdocs_attachments', true ) : [];

            if( is_array( $attachment_meta ) ) {
                //Parse JSON Attachments
                foreach ( $attachment_meta as $attachment ) {
                    array_push( $parsed_array, json_decode( $attachment ) );
                }
            }

            //Icons Array
            $icon_obj = [
                'image'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="image" height="16" width="16" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_image_icon' )
                ],
                'pdf'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="pdf" height="16" width="16" viewBox="0 0 512 512"><path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 144-208 0c-35.3 0-64 28.7-64 64l0 144-48 0c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zM176 352l32 0c30.9 0 56 25.1 56 56s-25.1 56-56 56l-16 0 0 32c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-48 0-80c0-8.8 7.2-16 16-16zm32 80c13.3 0 24-10.7 24-24s-10.7-24-24-24l-16 0 0 48 16 0zm96-80l32 0c26.5 0 48 21.5 48 48l0 64c0 26.5-21.5 48-48 48l-32 0c-8.8 0-16-7.2-16-16l0-128c0-8.8 7.2-16 16-16zm32 128c8.8 0 16-7.2 16-16l0-64c0-8.8-7.2-16-16-16l-16 0 0 96 16 0zm80-112c0-8.8 7.2-16 16-16l48 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-32 0 0 32 32 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-32 0 0 48c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-64 0-64z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_pdf_icon' )
                ],
                'zip'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="zip" height="16" width="12" viewBox="0 0 384 512"><path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM96 48c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm-6.3 71.8c3.7-14 16.4-23.8 30.9-23.8h14.8c14.5 0 27.2 9.7 30.9 23.8l23.5 88.2c1.4 5.4 2.1 10.9 2.1 16.4c0 35.2-28.8 63.7-64 63.7s-64-28.5-64-63.7c0-5.5 .7-11.1 2.1-16.4l23.5-88.2zM112 336c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H112z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_zip_icon' )
                ],
                'external'    => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="external" height="16" width="16" viewBox="0 0 512 512"><path d="M432 320H400a16 16 0 0 0 -16 16V448H64V128H208a16 16 0 0 0 16-16V80a16 16 0 0 0 -16-16H48A48 48 0 0 0 0 112V464a48 48 0 0 0 48 48H400a48 48 0 0 0 48-48V336A16 16 0 0 0 432 320zM488 0h-128c-21.4 0-32.1 25.9-17 41l35.7 35.7L135 320.4a24 24 0 0 0 0 34L157.7 377a24 24 0 0 0 34 0L435.3 133.3 471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"/></svg>',
                    'payload'  => []
                ],
                'video'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="video" height="16" width="18" viewBox="0 0 576 512"><path d="M336.2 64H47.8C21.4 64 0 85.4 0 111.8v288.4C0 426.6 21.4 448 47.8 448h288.4c26.4 0 47.8-21.4 47.8-47.8V111.8c0-26.4-21.4-47.8-47.8-47.8zm189.4 37.7L416 177.3v157.4l109.6 75.5c21.2 14.6 50.4-.3 50.4-25.8V127.5c0-25.4-29.1-40.4-50.4-25.8z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_video_icon' )
                ],
                'audio'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="audio" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm-64 268c0 10.7-12.9 16-20.5 8.5L104 376H76c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h28l35.5-36.5c7.6-7.6 20.5-2.2 20.5 8.5v136zm33.2-47.6c9.1-9.3 9.1-24.1 0-33.4-22.1-22.8 12.2-56.2 34.4-33.5 27.2 27.9 27.2 72.4 0 100.4-21.8 22.3-56.9-10.4-34.4-33.5zm86-117.1c54.4 55.9 54.4 144.8 0 200.8-21.8 22.4-57-10.3-34.4-33.5 36.2-37.2 36.3-96.5 0-133.8-22.1-22.8 12.3-56.3 34.4-33.5zM384 121.9v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_audio_icon' )
                ],
                'msword'      => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="msword" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm57.1 120H305c7.7 0 13.4 7.1 11.7 14.7l-38 168c-1.2 5.5-6.1 9.3-11.7 9.3h-38c-5.5 0-10.3-3.8-11.6-9.1-25.8-103.5-20.8-81.2-25.6-110.5h-.5c-1.1 14.3-2.4 17.4-25.6 110.5-1.3 5.3-6.1 9.1-11.6 9.1H117c-5.6 0-10.5-3.9-11.7-9.4l-37.8-168c-1.7-7.5 4-14.6 11.7-14.6h24.5c5.7 0 10.7 4 11.8 9.7 15.6 78 20.1 109.5 21 122.2 1.6-10.2 7.3-32.7 29.4-122.7 1.3-5.4 6.1-9.1 11.7-9.1h29.1c5.6 0 10.4 3.8 11.7 9.2 24 100.4 28.8 124 29.6 129.4-.2-11.2-2.6-17.8 21.6-129.2 1-5.6 5.9-9.5 11.5-9.5zM384 121.9v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_wordfile_icon' )
                ],
                'application' => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="default" height="16" width="12" viewBox="0 0 384 512"><path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_default_icon' )
                ],
                'csv'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="csv" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm-96 144c0 4.4-3.6 8-8 8h-8c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h8c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8h-8c-26.5 0-48-21.5-48-48v-32c0-26.5 21.5-48 48-48h8c4.4 0 8 3.6 8 8v16zm44.3 104H160c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h12.3c6 0 10.4-3.5 10.4-6.6 0-1.3-.8-2.7-2.1-3.8l-21.9-18.8c-8.5-7.2-13.3-17.5-13.3-28.1 0-21.3 19-38.6 42.4-38.6H200c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8h-12.3c-6 0-10.4 3.5-10.4 6.6 0 1.3 .8 2.7 2.1 3.8l21.9 18.8c8.5 7.2 13.3 17.5 13.3 28.1 0 21.3-19 38.6-42.4 38.6zM256 264v20.8c0 20.3 5.7 40.2 16 56.9 10.3-16.7 16-36.6 16-56.9V264c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8v20.8c0 35.5-12.9 68.9-36.3 94.1-3 3.3-7.3 5.1-11.7 5.1s-8.7-1.9-11.7-5.1c-23.4-25.2-36.3-58.6-36.3-94.1V264c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8zm121-159L279.1 7c-4.5-4.5-10.6-7-17-7H256v128h128v-6.1c0-6.3-2.5-12.4-7-16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_csv_icon' )
                ],
                'text'        => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="default" height="16" width="12" viewBox="0 0 384 512"><path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_default_icon' )
                ]
            ];

            betterdocs_pro()->assets->enqueue( 'betterdocs-attachments', 'admin/css/attachment.css', ['betterdocs-fontawesome'], 'all' );
            betterdocs_pro()->assets->enqueue( 'betterdocs-attachments', 'admin/js/attachment.js', ['jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'] );
            betterdocs_pro()->assets->localize( 'betterdocs-attachments', 'betterdocsAttachments', [
                'attachment_data'     => $parsed_array,
                'attachment_icons'    => $icon_obj,
                'attachment_settings' => [
                    'show_attachment_size'                => betterdocs()->settings->get( 'show_attachment_size' ),
                    'show_attachment_icon'                => betterdocs()->settings->get( 'show_attachment_icon' ),
                    'attachment_default_file_format_name' => betterdocs()->settings->get( 'attachment_file_name_format' )
                ]
            ] );

            //Register related articles related assets
            $parsed_related_articles = [];
            $related_articles_meta   = count( (array) get_post_meta( get_the_ID(), '_betterdocs_related_articles', true ) ) > 0 ? get_post_meta( get_the_ID(), '_betterdocs_related_articles', true ) : [];

            if( is_array( $related_articles_meta ) ) {
                //Parse JSON Related Articles
                foreach ( $related_articles_meta as $related_article ) {
                    array_push( $parsed_related_articles, json_decode( $related_article ) );
                }
            }

            betterdocs_pro()->assets->enqueue( 'betterdocs-related-articles', 'admin/css/related-articles.css', ['betterdocs-fontawesome'], 'all' );
            betterdocs_pro()->assets->enqueue( 'betterdocs-related-articles', 'admin/js/related-articles.js', [] );
            betterdocs_pro()->assets->localize(
                'betterdocs-related-articles',
                'betterdocsRelatedArticles',
                [
                    'related_articles_data' => $parsed_related_articles
                ]
            );


        }

        // @todo: check the hook condition.
        if ( $hook !== 'toplevel_page_betterdocs-admin' ) {
            return;
        }

        global $current_screen;

        $_params = [
            'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
            'doc_cat_order_nonce'        => wp_create_nonce( 'doc_cat_order_nonce' ),
            'knowledge_base_order_nonce' => wp_create_nonce( 'knowledge_base_order_nonce' ),
            'paged'                      => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0,
            'menu_title'                 => __( 'Switch to BetterDocs UI', 'betterdocs-pro' )
        ];

        if ( isset( $current_screen->taxonomy ) ) {
            $_params['per_page_id'] = "edit_{$current_screen->taxonomy}_per_page";
        }

        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-admin', 'admin/css/betterdocs-admin.css' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-admin', 'admin/js/betterdocs.js', ['jquery'] );
        betterdocs_pro()->assets->localize( 'betterdocs-pro-admin', 'betterdocs_pro_admin', $_params );


    }

    public function add_knowledge_base_filter() {
        betterdocs()->views->get( 'admin/header-parts/kb' );
    }

    public function filter_by_kb() {
        betterdocs()->views->get( 'admin/header-parts/filter-by-kb' );
    }

    public function filter_by_view() {
        betterdocs()->views->get( 'admin/header-parts/filter-by-view' );
    }
}
