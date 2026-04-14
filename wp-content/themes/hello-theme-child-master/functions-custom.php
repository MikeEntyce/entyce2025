<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function enqueue_nice_select_assets() {
    // CSS
    wp_enqueue_style( 'nice-select-css', get_stylesheet_directory_uri() . '/css/nice-select.css' );
    wp_enqueue_style( 'splitting-css', get_stylesheet_directory_uri() . '/css/splitting.css' );

    // JS (depends on jQuery)
    wp_enqueue_script( 'nice-select-js', get_stylesheet_directory_uri() . '/js/jquery.nice-select.min.js', array('jquery'), null, true );

    // Init script
    wp_add_inline_script( 'nice-select-js', 'jQuery(document).ready(function($){ $("select").niceSelect(); });' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_nice_select_assets' );