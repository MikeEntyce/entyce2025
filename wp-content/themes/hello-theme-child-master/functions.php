<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

if ( ! defined( 'ENV' ) ) {
	define('ENV', '');
}

require('functions-custom.php');

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// Get the current script's directory
$currentDir = __DIR__;

// Define the file path
$customFunctionsFile = $currentDir . '/functions_custom.php';

// Check if the file exists
if (file_exists($customFunctionsFile)) {
    include $customFunctionsFile; // or use require if it's mandatory for the file to exist
}

//add_image_size( 'news_page', 419, 227, true );

add_filter('big_image_size_threshold', function() {
    return 3840; 
});

function entyce_scripts() {
	// Enqueue WordPress's default jQuery
	wp_enqueue_script('jquery');

	// Enqueue the main stylesheet
	wp_enqueue_style('main-style', get_stylesheet_directory_uri() . '/css/style.css', array(), strval(filemtime(get_stylesheet_directory() . '/css/style.css')));

	// Enqueue the custom stylesheet
    $custom_css_file = get_stylesheet_directory() . '/css/custom.css';
    if (file_exists($custom_css_file)) {
	    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/css/custom.css', array(), strval(filemtime(get_stylesheet_directory() . '/css/custom.css')));
    }
	
    // Enqueue the main JS script, without autoHidingNavbar dependency
    wp_enqueue_script('autoHidingNavbar', get_stylesheet_directory_uri() . '/js/jquery.bootstrap-autohidingnavbar.js', array('jquery'), filemtime(get_stylesheet_directory() . '/js/jquery.bootstrap-autohidingnavbar.js'), true);
    wp_enqueue_script('main-include', get_stylesheet_directory_uri() . '/js/main_include.js', array('jquery'), strval(filemtime(get_stylesheet_directory() . '/js/main_include.js')), true);
	wp_enqueue_script('main-js', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), strval(filemtime(get_stylesheet_directory() . '/js/main.js')), true);
	wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), strval(filemtime(get_stylesheet_directory() . '/js/custom.js')), true);


    // Enqueue blaze-slider
    wp_enqueue_style('blaze-slider-style', '/node_modules/blaze-slider/dist/blaze.css');
    wp_enqueue_script('blaze-slider-script', '/node_modules/blaze-slider/dist/blaze-slider.min.js', array(), false, true);

}
add_action( 'wp_enqueue_scripts', 'entyce_scripts' );

// Load admin styles
function entyce_admin_styles()
{
    wp_enqueue_style('entyce_admin_styles', get_stylesheet_directory_uri() . '/css/admin.css', array(), strval(filemtime(get_stylesheet_directory() . '/css/admin.css')));
}
add_action('admin_enqueue_scripts', 'entyce_admin_styles');





function register_custom_button_widgets( $widgets_manager ) {
    // Include the Custom Button Widget
    require_once( __DIR__ . '/widgets/custom-button-widget.php' );
    $widgets_manager->register( new \Custom_Button_Widget() );

    // Include the Primary Button Widget
    require_once( __DIR__ . '/widgets/primary-button-widget.php' );
    $widgets_manager->register( new \Primary_Button_Widget() );

    // Include the Secondary Button Widget
    require_once( __DIR__ . '/widgets/secondary-button-widget.php' );
    $widgets_manager->register( new \Secondary_Button_Widget() );
}
add_action( 'elementor/widgets/register', 'register_custom_button_widgets' );


add_action( 'elementor/element/kit/section_settings-layout/after_section_end', function($element) {
    $element->start_injection([
        'at' => 'before',
        'of' => 'section_settings-layout',
    ]);

    $element->start_controls_section(
        'global_button_icons',
        [
            'tab' => 'settings-layout',
            'label' => __( 'Global Button Icons' )
        ]
    );

    // Add controls for 5 different button icons
    for ($i = 1; $i <= 5; $i++) {
        $element->add_control(
            'global_button_icon_' . $i,
            [
                'label' => __( 'Button Icon ' . $i, 'custom-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Enter a base64 encoded SVG for button icon ' . $i, 'custom-plugin' ),
                'default' => '',
            ]
        );
    }

    $element->end_controls_section();
    $element->end_injection();
});

// Function to get global button icons from Elementor settings
function get_global_button_icons() {
    if ( class_exists( '\Elementor\Plugin' ) ) {
        $active_kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
        if ( $active_kit ) {
            $icons = [];
            for ($i = 1; $i <= 5; $i++) {
                $icons[$i] = $active_kit->get_settings( 'global_button_icon_' . $i );
            }
            return $icons;
        }
    }
    return [];
}

// Hook to output global button icons in the header
function output_global_button_icons_in_header() {
    $global_button_icons = get_global_button_icons();
    if ( !empty( $global_button_icons ) ) {
        ?>
        <style>
        <?php foreach ($global_button_icons as $i => $icon): ?>
            <?php if (!empty($icon)): ?>
            body .elementor-button-wrapper .elementor-button.button-icon_<?php echo $i; ?> {
                background-image: url("data:image/svg+xml;base64,<?php echo esc_attr( $icon ); ?>");
            }
            <?php endif; ?>
        <?php endforeach; ?>
        </style>
        <?php
    }
}
add_action( 'wp_head', 'output_global_button_icons_in_header' );

function allow_svg_uploads_for_admin($mimes) {
    if (current_user_can('administrator')) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads_for_admin');

if ( (constant('ENV') == 'local') || (constant('ENV') == 'dev') ) {
    add_action('send_headers', function() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Expires: 0");
        header("Pragma: no-cache");
    });
}

