<?php


if ( ! defined( 'SEARCH_FILTER_ELEMENTOR_URL' ) ) {
	define( 'SEARCH_FILTER_ELEMENTOR_URL', plugin_dir_url( __FILE__ ) );
}


// Include the environment constants.
$env_path = plugin_dir_path( __FILE__ ) . 'env.php';
if ( file_exists( $env_path ) ) {
	require_once $env_path;
}
