<?php
/**
 * Plugin Name: Search & Filter - Elementor Extension
 * Description: Adds Search & Filter integration for Elementor -  filter your Loop Grid, Posts, Portfolio, Product and Archives widgets.
 * Plugin URI:  https://searchandfilter.com
 * Version:     1.4.1
 * Author:      Code Amp
 * Author URI:  https://codeamp.com
 * Update URI:  https://searchandfilter.com
 * Text Domain: search-filter-elementor
 *
 * Elementor tested up to: 3.27
 * Elementor Pro tested up to: 3.27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'constants.php';

// Define the base file.
define( 'SEARCH_FILTER_ELEMENTOR_BASE_FILE', __FILE__ );

/**
 * Main Elementor Extension Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class Search_Filter_Elementor_Extension {
	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin version.
	 */
	const VERSION = '1.4.1';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.20.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

	/**
	 * Url for receiving updates.
	 */
	const PLUGIN_UPDATE_URL = 'https://searchandfilter.com';

	/**
	 * The ID of this plugin.
	 */
	const PLUGIN_UPDATE_ID = 278073;

	/**
	 * Required version of Search & Filter
	 *
	 * @since 1.0.0
	 *
	 * @var int The plugin update ID.
	 */
	const SEARCH_FILTER_PRO_REQUIRED_VERSION = '3.0.5-beta';

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * @var Search_Filter_Elementor_Extension The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * The plugin instance
	 *
	 * It will contain either the v2 instance or v3.
	 *
	 * @var mixed
	 */
	private $plugin = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * @return Search_Filter_Elementor_Extension An instance of the class.
	 */

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'i18n' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 9 );
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'search-filter-elementor' );
	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init() {

		if ( self::is_search_filter_version( 2 ) ) {
			$this->plugin = new \Search_Filter_Elementor_Extension\Version_2\Plugin();

		} elseif ( self::is_search_filter_version( 3 ) ) {

			$this->plugin = new \Search_Filter_Elementor_Extension\Version_3\Plugin();
		} else {
			// S&F not loaded at all.
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice_missing_search_filter_plugin' ) );
		}

	}

	/**
	 * Detects whether to load the version 2 or 3 integration.
	 *
	 * @param int $version
	 * @return bool Whether the version is matched or not.
	 */
	private static function is_search_filter_version( $version ) {

		// Test for version 2.
		if ( $version === 2 ) {
			if ( ! defined( 'SEARCH_FILTER_VERSION' ) ) {
				return false;
			}
			if ( version_compare( SEARCH_FILTER_VERSION, '2.0.0', '>=' ) && version_compare( SEARCH_FILTER_VERSION, '3.0.0-beta-1', '<' ) ) {
				return true;
			}
		} elseif ( $version === 3 ) {
			if ( ! defined( 'SEARCH_FILTER_PRO_VERSION' ) ) {
				return false;
			}
			if ( version_compare( SEARCH_FILTER_PRO_VERSION, '3.0.0-beta-2', '>=' ) ) {
				return true;
			}
		}
		return false;
	}
	

	/**
	 * Get the message for the main plugin missing.
	 *
	 * @return string
	 */
	public static function get_missing_main_plugin_message() {
		return sprintf(
			// translators: 1: Plugin name 2: Elementor
			esc_html__( '%1$s requires %2$s to be installed and activated.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor Pro', 'search-filter-elementor' ) . '</strong>'
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', self::get_missing_main_plugin_message() );

	}

	/**
	 * Get the message for the minimum Elementor version.
	 *
	 * @return string
	 */
	public static function get_minimum_elementor_version_message() {
		return sprintf(
			// translators: 1: Plugin name 2: Elementor 3: Required Elementor version
			esc_html__( '%1$s requires %2$s version %3$s or greater.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'search-filter-elementor' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	
		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', self::get_minimum_elementor_version_message() );

	}

	/**
	 * Get the message for the minimum PHP version.
	 *
	 * @return string
	 */
	public static function get_minimum_php_version_message() {
		return sprintf(
			// translators: 1: Plugin name 2: PHP 3: Required PHP version
			esc_html__( 'The %1$s requires %2$s version %3$s or greater.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'search-filter-elementor' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', self::get_minimum_php_version_message() );

	}

	/**
	 * Get the message for the Search & Filter plugin missing.
	 *
	 * @return string
	 */
	public static function get_missing_search_filter_plugin_message() {
		return sprintf(
			// translators: 1: Search & Filter Plugin name 2: Extension name
			esc_html__( '%1$s needs to be installed and activated for the %2$s to work.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_missing_search_filter_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', self::get_missing_search_filter_plugin_message() );
	}

	/**
	 * Get the message for Search & Filter being outdated.
	 *
	 * @return string
	 */
	public static function get_search_filter_outdated_message() {
		return sprintf(
			// translators: 1: Search & Filter Plugin name 2: Required version 3: Extension name
			esc_html__( '%1$s needs to be be updated to version %2$s or higher for the %3$s to work.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-elementor' ) . '</strong>',
			self::SEARCH_FILTER_PRO_REQUIRED_VERSION,
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have BB installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_search_filter_plugin_outdated() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', self::get_search_filter_outdated_message() );
	}

	/**
	 * Get the message for when Search & Filter is next version.
	 *
	 * @return string
	 */
	private static function get_search_filter_is_next_message() {
		return sprintf(
			// translators: 1: Extension name 2: Search & Filter Plugin name
			esc_html__( 'The %1$s needs to be be updated work with %2$s version %3$s or higher.', 'search-filter-elementor' ),
			'<strong>' . esc_html__( 'Search & Filter - Elementor Extension', 'search-filter-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-elementor' ) . '</strong>',
			SEARCH_FILTER_PRO_VERSION,
		);
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have BB installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public static function admin_notice_search_filter_is_next() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', self::get_search_filter_is_next_message() );
	}


	public static function deactivate() {
		if ( ! self::is_search_filter_version( 3 ) ) {
			return;
		}

		// Enable was only introduced in 3.0.6 so check it exists.
		if ( ! method_exists( '\Search_Filter\Integrations', 'enable' ) ) {
			return;
		}

		\Search_Filter\Integrations::disable( 'elementor', true );
	}


	public static function activate() {
		if ( ! self::is_search_filter_version( 3 ) ) {
			return;
		}

		// Enable was only introduced in 3.0.6 so check it exists.
		if ( ! method_exists( '\Search_Filter\Integrations', 'enable' ) ) {
			return;
		}

		\Search_Filter\Integrations::enable( 'elementor', true );
	}
}

if ( ! class_exists( 'Search_Filter_Elementor_Extension\Version_2\Plugin' ) ) {
	// Load the v2 plugin file.
	include dirname( __FILE__ ) . '/includes/v2/class-plugin.php';
}

if ( ! class_exists( 'Search_Filter_Elementor_Extension\Version_3\Plugin' ) ) {
	// Load the v3 plugin file.
	include dirname( __FILE__ ) . '/includes/v3/class-plugin.php';
}

if ( ! class_exists( 'Search_Filter_Elementor_Extension\Version_3\Element_Integrations' ) ) {
	// Load the v3 element settings file.
	include dirname( __FILE__ ) . '/includes/v3/class-element-integrations.php';
}
if ( ! class_exists( 'Search_Filter_Elementor_Extension\Version_3\Element_Integration' ) ) {
	// Load the v3 element settings file.
	include dirname( __FILE__ ) . '/includes/v3/class-element-integration.php';
}

Search_Filter_Elementor_Extension::instance();

/**
 * The code that runs during plugin activation.
 */
function activate_search_filter_elementor() {
	Search_Filter_Elementor_Extension::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_search_filter_elementor() {
	Search_Filter_Elementor_Extension::deactivate();
}

register_activation_hook( __FILE__, 'activate_search_filter_elementor' );
register_deactivation_hook( __FILE__, 'deactivate_search_filter_elementor' );
