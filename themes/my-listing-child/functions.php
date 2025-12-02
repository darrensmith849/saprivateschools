<?php

// Enqueue child theme style.css
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );

    if ( is_rtl() ) {
    	wp_enqueue_style( 'mylisting-rtl', get_template_directory_uri() . '/rtl.css', [], wp_get_theme()->get('Version') );
    }
}, 500 );

// Happy Coding :)


/**
 * Enqueue child styles.
 */
require_once( __DIR__ . '/vendor/autoload.php' );

use NGD_THEME\App;

new App( 1 );
