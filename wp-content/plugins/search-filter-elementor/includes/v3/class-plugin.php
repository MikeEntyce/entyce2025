<?php
/**
 *
 */

namespace Search_Filter_Elementor_Extension\Version_3;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Elementor Extension Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class Plugin {


	/**
	 * The extension slug, used in the Extensions API.
	 *
	 * @var string
	 */
	const SLUG = 'elementor';

	/**
	 * The extension instance
	 *
	 * @var \Search_Filter_Pro\Core\Extensions\Extension|false
	 */
	private $extension = null;


	/**
	 * List of all S&F fields.
	 */
	private static $field_options = array();
	/**
	 * List of all S&F queries.
	 *
	 * @var array
	 */
	private static $query_options = array();

	/**
	 * Suppported widget types.
	 *
	 * @var array
	 */
	private static $widget_types = array();


	public $current_query_id           = 0;
	public $current_no_results_message = '';
	public $display_methods            = array();

	private $frontend;

	private $query_objects = array();
	
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {

		// Check for existence of the S&F plugin first, this should already be handled by our version check for v2 or v3,
		// but when we remove it, we should check for it here.
		if ( ! defined( 'SEARCH_FILTER_PRO_VERSION' ) || ! defined( 'SEARCH_FILTER_VERSION' ) ) {
			// Display notice that S&F is required.
			add_action( 'admin_notices', array( \Search_Filter_Elementor_Extension::class, 'admin_notice_missing_search_filter_plugin' ) );
			return;
		}

		// V3 uses its own extensions API for mananging updates & versions.
		// Only added in 3.0.5, so for now, check it exists before using it.
		if ( class_exists( '\Search_Filter_Pro\Core\Extensions' ) ) {
			\Search_Filter_Pro\Core\Extensions::add(
				self::SLUG,
				array(
					'file'    => SEARCH_FILTER_ELEMENTOR_BASE_FILE,
					'id'      => \Search_Filter_Elementor_Extension::PLUGIN_UPDATE_ID,
					'version' => \Search_Filter_Elementor_Extension::VERSION,
				)
			);
			$this->check_for_upgrades();
		}

		// Check if Elementor installed and activated.
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( \Search_Filter_Elementor_Extension::class, 'admin_notice_missing_main_plugin' ) );
			$this->create_search_filter_admin_notice( 'get_missing_main_plugin_message', 'warning', 'search-filter-elementor-missing-main-plugin' );
			return;
		}

		// Check for required Elementor version.
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, \Search_Filter_Elementor_Extension::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( \Search_Filter_Elementor_Extension::class, 'admin_notice_minimum_elementor_version' ) );
			$this->create_search_filter_admin_notice( 'get_minimum_elementor_version_message', 'warning', 'search-filter-elementor-minimum-elementor-version' );
			return;
		}

		// Check for required PHP version.
		if ( version_compare( PHP_VERSION, \Search_Filter_Elementor_Extension::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( \Search_Filter_Elementor_Extension::class, 'admin_notice_minimum_php_version' ) );
			$this->create_search_filter_admin_notice( 'get_minimum_php_version_message', 'warning', 'search-filter-elementor-minimum-php-version' );
			return;
		}

		if ( version_compare( SEARCH_FILTER_PRO_VERSION, \Search_Filter_Elementor_Extension::SEARCH_FILTER_PRO_REQUIRED_VERSION, '<' ) ) {
			// Display notice that S&F is required.
			add_action( 'admin_notices', array( \Search_Filter_Elementor_Extension::class, 'admin_notice_search_filter_plugin_outdated' ) );
			$this->create_search_filter_admin_notice( 'get_search_filter_outdated_message', 'warning', 'search-filter-elementor-missing-required-version' );
			return;
		}
		/*
		 if ( version_compare( SEARCH_FILTER_PRO_VERSION, self::SEARCH_FILTER_PRO_REQUIRED_VERSION, '>' ) ) {
			// Display notice that S&F is required.
			add_action( 'admin_notices', array( $this, 'admin_notice_search_filter_is_next' ) );
			\Search_Filter\Core\Notices::add_notice( $this->get_search_filter_is_next_message(), 'warning', 'search-filter-elementor-missing-required-version' );
			return;
		} */

		add_action( 'init', array( $this, 'i18n' ), 1 );
		add_action( 'init', array( $this, 'setup_widget_types' ), 1 );

		$this->init();

	}

	/**
	 * Setup the widget types.
	 */
	public function setup_widget_types() {
		self::$widget_types = array(
			'elementor/widget'            => __( 'Elementor Widget', 'search-filter-elementor' ),
		);
	}
	/**
	 * Create a search filter admin notice.
	 *
	 * @param string $message The message.
	 * @param string $type The type.
	 * @param string $id The ID.
	 */
	private function create_search_filter_admin_notice( $message_callback, $type, $id ) {
		add_action(
			'search-filter/core/notices/get_notices',
			function () use ( $message_callback, $type, $id ) {
				$message = call_user_func( array( \Search_Filter_Elementor_Extension::class, $message_callback ) );
				\Search_Filter\Core\Notices::add_notice( $message, $type, $id );
			}
		);
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

		// Add Elementor options to S&F admin UI.
		add_action( 'search-filter/settings/init', array( $this, 'add_integration_option' ), 10 );

		// Hide CSS selector options from query editor.
		add_action( 'search-filter/settings/init', array( $this, 'hide_css_selector_options' ), 10 );

		add_action( 'search-filter/frontend/data/start', array( $this, 'attach_update_query_attributes' ), 10 );

		// Force the WC post types when choosing the WC integration type.
		add_filter( 'search-filter/rest-api/get_query_post_types', array( $this, 'get_wc_query_post_types' ), 10, 2 );

		// Elementor Widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Frontend queries.
		add_filter( 'elementor/query/query_args', array( $this, 'attach_query' ), 10, 2 ); // attach S&F to the post loop grid query

		// Setup frontend class reference.
		$this->frontend = new \Search_Filter\Frontend( SEARCH_FILTER_SLUG, SEARCH_FILTER_VERSION );

		// Init setup our element settings.
		add_action( 'elementor/init', array( $this, 'init_element_settings' ) );
		add_action( 'elementor/editor/init', array( $this, 'init_editor' ) );

		// Scripts.
		add_action( 'elementor/editor/before_enqueue_styles', array( $this, 'enqueue_editor_styles' ), 10 );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ), 10 );
		add_action( 'wp_footer', array( $this, 'preload_all_svgs' ), 9 );// Run before the SVGs are output in the footer (which uses priotity 10).

		// Modify controls.
		add_action( 'elementor/element/loop-grid/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/loop-carousel/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/woocommerce-products/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/posts/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/portfolio/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/ae-post-blocks/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );
		add_action( 'elementor/element/ae-post-blocks-adv/section_query/before_section_end', array( $this, 'modify_widget_query_section_controls' ), 10, 2 );

		// TODO - this is a temporary workaround to access some pagination controls which are not properly added to `section_pagination`.
		// So we need to hook into the next section `section_additional_options` so they'll be registered.
		// add_action( 'elementor/element/loop-grid/section_pagination/before_section_end', array( $this, 'modify_widget_pagination_section_controls' ), 10, 2 );
		add_action( 'elementor/element/loop-grid/section_additional_options/before_section_end', array( $this, 'modify_widget_pagination_section_controls' ), 10, 2 );

		// Add filters before, during and after rending to filter queries.
		add_action( 'elementor/widget/before_render_content', array( $this, 'native_widget_before_render_content' ), 10 ); // add classes, attach to query
		add_filter( 'elementor/widget/render_content', array( $this, 'filter_native_widget_render' ), 10, 2 ); // filter the rendered content to fix some things (hacky)
		add_filter( 'elementor/widget/after_render', array( $this, 'native_widget_after_render' ), 10 ); // filter the rendered content to fix some things (hacky)

		// AE
		// posts + advanced posts.
		// add_action( 'elementor/widget/before_render_content', array( $this, 'filter_ae_before_render' ), -10 ); // add classes, attach to query

		// Check if any Elementor queries are loaded in the footer.
		add_action( 'wp_footer', array( $this, 'check_for_elementor_queries' ), 10 );
	}

	/**
	 * Get a query object by its ID, store a reference to it so we 
	 * we can re-use it when needed.
	 *
	 * @param int $query_id The query ID.
	 * @return \Search_Filter\Queries\Query|null The query object or null if not found.
	 */
	public function get_query_object_by_id( $query_id ) {	
		if ( isset( $this->query_objects[ $query_id ] ) ) {
			return $this->query_objects[ $query_id ];
		}
		$query = \Search_Filter\Queries\Query::find( array( 'id' => $query_id ) );
		if ( ! is_wp_error( $query ) ) {
			$this->query_objects[ $query_id ] = $query;
			return $query;
		}
		return null;
	}

	public function attach_update_query_attributes() {
		// Auto set the CSS selector options.
		add_filter( 'search-filter/queries/query/get_attributes', array( $this, 'update_query_attributes' ), 10, 2 );
	}

	public function update_query_attributes( $attributes, $query ) {
		$id = 0;
		// Legacy.
		if ( is_scalar( $query ) ) {
			$id = $query;
		} else {
			$id = $query->get_id();
		}

		// We want `queryContainer` and `paginationSelector` to be set automatically.
		$connected_element_name = $query->get_render_config_value( 'elementName' );

		if ( empty( $connected_element_name ) ) {
			return $attributes;
		}

		$supported_element_names = Element_Integrations::get_supported_names();
		if ( ! in_array( $connected_element_name, $supported_element_names, true ) ) {
			return $attributes;
		}

		$attributes['queryContainer']          = '.search-filter-query--id-' . $id;
		$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . $id . ' a.page-numbers';

		$element_setting = Element_Integrations::get_by_name( $connected_element_name );

		if ( ! $element_setting ) {
			return $attributes;
		}

		if ( $element_setting->get_support( 'posts_container' ) ) {
			$attributes['queryPostsContainer'] = '.search-filter-query--id-' . $id . ' ' . $element_setting->get_support( 'posts_container' );
		}
		
		return $attributes;
	}


	public function hide_css_selector_options() {
		$depends_conditions = array(
			'relation' => 'AND',
			'action'   => 'hide',
			'rules'    => array(
				array(
					'option'  => 'queryIntegration',
					'compare' => '!=',
					'value'   => 'elementor/widget',
				),
			),
		);

		$query_container = \Search_Filter\Queries\Settings::get_setting( 'queryContainer' );
		if ( $query_container ) {
			$query_container->add_depends_condition( $depends_conditions );
		}

		$query_posts_container = \Search_Filter\Queries\Settings::get_setting( 'queryPostsContainer' );
		if ( $query_posts_container ) {
			$query_posts_container->add_depends_condition( $depends_conditions );
		}

		$pagination_selector = \Search_Filter\Queries\Settings::get_setting( 'queryPaginationSelector' );
		if ( $pagination_selector ) {
			$pagination_selector->add_depends_condition( $depends_conditions );
		}
	}

	public function add_integration_option() {
		// Get the setting.
		$integration_type_setting = \Search_Filter\Queries\Settings::get_setting( 'queryIntegration' );
		if ( ! $integration_type_setting ) {
			return;
		}

		foreach ( self::$widget_types as $widget_type => $label ) {
			$new_integration_type_option = array(
				'label' => $label,
				'value' => $widget_type,
			);
			$integration_type_setting->add_option( $new_integration_type_option );
		}
	}

	/**
	 * Force the WC post types when choosing the WC integration type.
	 *
	 * @param array $query_post_types The query post types.
	 * @param array $params The params.
	 * @return array The query post types.
	 */
	public function get_wc_query_post_types( $query_post_types, $params ) {

		$query_integration = $this->get_query_integration( $params );

		if ( $query_integration === 'elementor/woocommerce-products' ) {
			$query_post_types = array(
				'disabled' => true,
				'value'    => array( 'product' ),
			);
		}

		return $query_post_types;
	}

	private function get_used_queries() {
		$queries = array();
		if ( method_exists( '\Search_Filter\Queries', 'get_used_queries' ) ) {
			$queries = \Search_Filter\Queries::get_used_queries();
		} else if ( method_exists( '\Search_Filter\Queries', 'get_active_queries' ) ) {
			$queries = \Search_Filter\Queries::get_active_queries();
		}
		return $queries;
	}
	/**
	 * If S&F Elementor queries are loaded ensure we're loading our plugin scripts.
	 *
	 * Our plugin JS is only added dynamically when our field widget as added to the page,
	 * which means:
	 *
	 * - if a user is using shortcodes they won't get the Elementor JS, so lets
	 *   load it if needed accordingly. *Note: shortcodes are cached in Elementor so they
	 *   often won't trigger `register_field()` which can cause all kinds of issues.
	 *
	 * - We still want to load our Elementor JS if queries are loaded but not fields, so
	 *   we need to check if any Elementor queries are loaded and then enqueue our JS.
	 */
	public function check_for_elementor_queries() {
		
		$queries = $this->get_used_queries();
		if ( empty( $queries ) ) {
			return;
		}
		
		if ( empty( $queries ) ) {
			return;
		}

		$has_elementor_query       = false;
		$support_integration_types = array_keys( self::$widget_types );

		foreach ( $queries as $query_data ) {
			if ( ! isset( $query_data['attributes']['queryIntegration'] ) ) {
				continue;
			}

			if ( in_array( $query_data['attributes']['queryIntegration'], $support_integration_types, true ) ) {
				$has_elementor_query = true;
				break;
			}
		}

		if ( ! $has_elementor_query ) {
			return;
		}

		if ( ! wp_script_is( 'search-filter-elementor', 'registered' ) ) {
			wp_register_script( 'search-filter-elementor', self::get_frontend_assets_url() . 'v3/js/frontend.js', array( 'search-filter' ), \Search_Filter_Elementor_Extension::VERSION );
		}
		wp_enqueue_script( 'search-filter-elementor' );
	}
	/**
	 * Load Scripts
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function enqueue_editor_styles() {
		wp_enqueue_style( 'search-filter-elementor-admin', plugins_url( 'assets/v3/css/admin-styles.css', SEARCH_FILTER_ELEMENTOR_BASE_FILE ), array(), \Search_Filter_Elementor_Extension::VERSION );
		$this->frontend->enqueue_styles();

		// TODO - when we render the field, we shoudl output a json object, and then the createField JS to init it...
		// Once we have the prerender working again, we won't need the JS in the admin area...
		// Maybe we can pass `isInteractive` as false  for now and override that attribute.
	}
	public function enqueue_editor_scripts() {
		// Outputs the main searchAndFilter JS Object in a script.
		\Search_Filter\Core\Scripts::output_init_js();

		// Now enqueue the scripts.
		$this->frontend->enqueue_scripts();
		$data = $this->frontend->get_js_data();
		$js   = \Search_Filter\Core\Scripts::get_globals( 'frontend', $data );

		echo '<script type="text/javascript">' . $js . '</script>';

		// add_action( 'wp_footer', array( $plugin_frontend, 'data' ), 10 );
		// add_action( 'admin_footer', array( $plugin_frontend, 'data' ), 10 );
	}
	public function preload_all_svgs() {
		if ( \Elementor\Plugin::instance()->preview->is_preview_mode() ) {
			// Enqueue all the SVGs when in preview mode.
			\Search_Filter\Core\Icons::load();
		}
	}
	public static function get_fields() {
		if ( empty( self::$field_options ) ) {
			$fields = \Search_Filter\Fields::find(
				array(
					'status' => 'enabled', // Only get enabled queries.
					'number' => 0, // Get all.
				),
				'records'
			);

			foreach ( $fields as $field_record ) {
				self::$field_options[ 'field_' . $field_record->get_id() ] = $field_record->get_name();
			}
		}

		return self::$field_options;
	}

	public static function get_queries() {

		if ( ! empty( self::$query_options ) ) {
			return self::$query_options;
		}

		$queries = \Search_Filter\Queries::find(
			array(
				'status'     => 'enabled', // Only get enabled queries.
				'number'     => 0, // Get all.
				'meta_query' => array(
					array(
						'key'     => 'query_integration',
						'value'   => array_keys( self::$widget_types ),
						'compare' => 'IN',
					),
				),
			),
			'records'
		);

		foreach ( $queries as $query_record ) {
			self::$query_options[ 'query_' . $query_record->get_id() ] = $query_record->get_name();
		}

		return self::$query_options;
	}

	/**
	 * Get the query integration.
	 *
	 * Includes a fallback for older versions in case they haven't been updated yet.
	 *
	 * @param array $attributes The attributes.
	 * @return string The query integration.
	 */
	private function get_query_integration( $attributes ) {
		if ( isset( $attributes['queryIntegration'] ) ) {
			return $attributes['queryIntegration'];
		} elseif ( isset( $attributes['singleIntegration'] ) ) {
			// Fallback for older versions.
			return $attributes['singleIntegration'];
		}
		return '';
	}

	/**
	 * Init Search & Filter Elementor widgets
	 *
	 * Include widgets files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function register_widgets( $widgets_manager ) {
		// Include Widget files
		include_once __DIR__ . '/widgets/field.php';
		// Register widget
		$widgets_manager->register( new \Search_Filter_Elementor_Extension\Version_3\Widgets\Field() );
	}

	/**
	 * Initialize our element settings.
	 *
	 * @return void
	 */
	public function init_element_settings() {
		Element_Integrations::init();
	}

	/**
	 * Initialize editor behaviors.
	 *
	 * @return void
	 */
	public function init_editor() {

		// Init, attach query hooks etc for previews on page load.
		$this->frontend->init();
		 
		// Ensure that our frontend queries run while in the editor preview.
		add_filter( 'search-filter/query/is_query_context', array( $this, 'enable_queries_in_editor' ) );
	}
	/**
	 * Initialize our element settings.
	 *
	 * @return void
	 */
	public function enable_queries_in_editor( $is_query_context ) {
		if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
			return true;
		}
		return $is_query_context;
	}

	/**
	 * Add S&F to Products widgets query args
	 *
	 * Filter the query_args before a WC shortcode query is run, attaches S&F to the args
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	public function attach_sf_to_wc_shortcode_args( $query_args, $attributes, $type ) {
		$query_args = $this->attach_sf_to_args( $query_args, $this->current_query_id );
		return $query_args;
	}

	/**
	 * Add S&F to loop grid query args
	 *
	 * Filter for query args, fired before a query is run in Elementor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function attach_query( $query_args, $element ) {

		$element_setting = Element_Integrations::get( $element );

		if ( ! $element_setting ) {
			return $query_args;
		}
		if ( ! $element_setting->get_support( 'elementor/query' ) ) {
			return $query_args;
		}
		
		$conditions = $element_setting->get_support( 'elementor/query_conditions' );

		if ( $conditions ) {
			$settings = $element->get_settings();
			foreach ( $conditions as $setting => $required_value ) {
				if ( ! isset( $settings[ $setting ] ) ) {
					return $query_args;
				}
				if ( $settings[ $setting ] !== $required_value ) {
					return $query_args;
				}
			}
		}

		// Handle pagination.
		if ( $element_setting->get_support( 'add_pagination' ) ) {
			$pagination_key = $element_setting->get_support( 'add_pagination' );
			$query_args['paged'] = get_query_var( $pagination_key );
		}

		$setting_namespace = $element_setting->get_namespace( $element );

		return $this->attach_sf_to_widget_args( $query_args, $element, $setting_namespace );
	}

	/**
	 * Check if S&F can be attached
	 *
	 * Checks a widgets settings are actually set to use S&F, before attaching
	 * S&F to the query_args
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	public function attach_sf_to_widget_args( $query_args, $element, $setting_namespace ) {
		$settings = $element->get_settings();

		if ( ! isset( $settings [ $setting_namespace . '_post_type' ] ) ) {
			return $query_args;
		}

		if ( 'search_filter_query' !== $settings[ $setting_namespace . '_post_type' ] ) {
			return $query_args;
		}

		if ( ! isset( $settings['search_filter_query'] ) ) {
			return $query_args;
		}
		
		$query_args = $this->attach_sf_to_args( $query_args, $this->current_query_id );

		return $query_args;
	}
/**
	 * Attach S&F to query args
	 *
	 * Takes a $args that will be passed to a WP_Query, and attaches S&F to it
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */

	 private function attach_sf_to_args( $query_args, $sfid ) {
		$query_args['search_filter_query_id'] = intval( $sfid );
		return $query_args;
	}

	public function modify_widget_query_section_controls( $element, $args ) {
		
		$element_setting = Element_Integrations::get( $element, false );
		
		if ( ! $element_setting ) {
			return;
		}

		if ( $element_setting->get_support( 'setting_query_control' ) ) {

			$combined_element_settings = array( $element_setting );
			if ( $element_setting->has_variants() ) {
				$combined_element_settings = $element_setting->get_variants();
			}

			$conditions = array(
				'relation' => 'or',
				'terms'    => array(),
			);

			foreach( $combined_element_settings as $element_setting_instance ) {
				// Check if the element supports search_filter_query, if so, add the option
				// the query list.
				$element_namespace = $element_setting_instance->get_namespace();
				
				// now add S&F query option to the query source control:
				$control = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $element_namespace . '_post_type' );

				if ( ! is_wp_error( $control ) ) {
					$conditions['terms'][] = array(
						'name'     => $element_namespace . '_post_type',
						'value'    => 'search_filter_query',
						'operator' => '===',
					);
					
					$hide_controls = $element_setting_instance->get_support( 'hide_controls' );
	
					if ( $hide_controls ) {
						// all these controls need to be hidden when S&F Query is selected, so add the condition
						$this->update_control_dependencies( $element, $element_setting_instance, $hide_controls );
					}

					// add extra condition.
					$control['options']['search_filter_query'] = 'Search & Filter Query';

					// Update the control.
					$element->update_control( $element_namespace . '_post_type', $control );
				}

			}

			// Note: this is preventing variants from using the `setting_query_control` support
			// because we don't prefix the name - we're keeping it like this for backwards compatibility.

			// Now add the dropdown to choose a S&F query.
			$element->add_control(
				'search_filter_query',
				array(
					'label'      => 'Search & Filter Query',
					'type'       => \Elementor\Controls_Manager::SELECT,
					'default'    => '',
					'options'    => self::get_queries(),
					'conditions' => $conditions,
				)
			);

		}

		// Note: this is preventing variants from using the `no_results_message` support
		// because we don't prefix the name - we're keeping it like this for backwards compatibility.
		// Currently our variants (for the loop-grid) don't need this feature anyway as it comes
		// built in from Elementor.
		if ( $element_setting->get_support( 'no_results_message' ) ) {
			// Add no results dropdown
			$element->add_control(
				'search_filter_no_results',
				array(
					'label'      => 'Nothing Found Message',
					'type'       => \Elementor\Controls_Manager::TEXTAREA,
					'default'    => '',
					'value'      => '',
					'conditions' => $conditions,
				)
			);
		}
	}

	public function modify_widget_pagination_section_controls( $element, $args ) {
	
		$element_setting = Element_Integrations::get( $element, false );
		
		if ( ! $element_setting ) {
			return;
		}

		if ( ! $element_setting->get_support( 'setting_pagination_control' ) ) {
			return;
		}


		$combined_element_settings = array( $element_setting );
		if ( $element_setting->has_variants() ) {
			$combined_element_settings = $element_setting->get_variants();
		}

		$pagination_settings_to_hide = array( 'auto_scroll', 'auto_scroll_offset', 'pagination_load_type' );
		
		foreach( $combined_element_settings as $element_setting_instance ) {
		
			// Check if the element supports search_filter_query, if so, add the option
			// the query list.
			$element_namespace = $element_setting_instance->get_namespace();
			
			foreach( $pagination_settings_to_hide as $control_name ) {
				$control = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $control_name );
				if ( ! is_wp_error( $control ) ) {
					// make sure we preserve existing conditions when we create / add our own to the list of controls
					if ( ! isset( $control['condition'] ) ) {
						$control['condition'] = array();
					}
					if ( ! is_array( $control['condition'] ) ) {
						$control['condition'] = array();
					}
					if ( ! isset( $control['condition'][ $element_namespace . '_post_type!' ] ) ) {
						$control['condition'][ $element_namespace . '_post_type!' ] = array();
					}
					if ( ! is_array( $control['condition'][ $element_namespace . '_post_type!' ] ) ) {
						$control['condition'][ $element_namespace . '_post_type!' ] = array( $control['condition'][ $element_namespace . '_post_type!' ] );
					}

					array_push( $control['condition'][ $element_namespace . '_post_type!' ], 'search_filter_query' );
					// Update the control
					$element->update_control( $control_name, $control );
				}
			}
		}
	}

	/**
	 * Update control dependencies
	 *
	 * Takes a control stack, and adds dependencies to known fields so that
	 * they are hidden when S&F is set as the query source for elements
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function update_control_dependencies( $element, $element_setting, $hide_controls ) {

		$element_namespace = $element_setting->get_namespace();

		foreach ( $hide_controls as $control_name ) {

			$widget_control_name = $element_namespace . '_' . $control_name;
			$control             = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $widget_control_name );

			if ( ! is_wp_error( $control ) ) {

				// make sure we preserve existing conditions when we create / add our own to the list of controls
				if ( ! isset( $control['condition'] ) ) {
					$control['condition'] = array();
				}
				if ( ! is_array( $control['condition'] ) ) {
					$control['condition'] = array();
				}
				if ( ! isset( $control['condition'][ $element_namespace . '_post_type!' ] ) ) {
					$control['condition'][ $element_namespace . '_post_type!' ] = array();
				}
				if ( ! is_array( $control['condition'][ $element_namespace . '_post_type!' ] ) ) {
					$control['condition'][ $element_namespace . '_post_type!' ] = array( $control['condition'][ $element_namespace . '_post_type!' ] );
				}

				array_push( $control['condition'][ $element_namespace . '_post_type!' ], 'search_filter_query' );

				// Update the control
				$element->update_control( $widget_control_name, $control );
			}
		}
	}

	public function is_post_type_archive() {
		if ( is_post_type_archive() || is_home() || is_tag() || is_tax() || is_category() ) {
			return true;
		}
		return false;
	}
	/**
	 * Add S&F results class to supported native widgets
	 * 
	 * Includes posts, portfolio, product, loop grid, loop carousel.
	 *
	 * Add a class to the widget on frontend, as well as jumping in and attaching hook
	 * to modify `found_posts` (for the hack around the "no results message")
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function native_widget_before_render_content( $element ) {

		$element_setting = Element_Integrations::get( $element, false );
		
		if ( ! $element_setting ) {
			return array();
		}

		if ( ! $element_setting->has_variants() ) {
			$this->setup_before_render_element_settings( $element, $element_setting );
			return;
		}

		// Deal with the variants.
		foreach ( $element_setting->get_variants() as $variant ) {
			$this->setup_before_render_element_settings( $element, $variant );
		}
	}

	private function setup_before_render_element_settings( $element, $element_setting ) {

		if ( ! $element_setting ) {
			return;
		}
		
		$query_ids = $this->get_connected_query_ids_for_element_setting( $element, $element_setting );
		
		if ( empty( $query_ids ) ) {
			return;
		}

		// Attach classes to the elements we are connected to, includes
		// widgets set to use the current query - also record which which
		// a query is associated with to automatically setup the CSS
		// selectors for ajax later.
		$element_classes = array();
		foreach( $query_ids as $query_id ) {
			$element_classes[] = 'search-filter-query--id-' . $query_id;

			$query = $this->get_query_object_by_id( $query_id );
			if ( $query ) {
				$query->set_render_config_value( 'elementName', $element->get_name() );
			}
		}
		$args          = array(
			'class' => $element_classes,
		);
		$element->add_render_attribute( '_wrapper', $args );

		$settings = $element->get_settings();

		if ( ! isset( $settings['search_filter_query'] ) ) {
			return;
		}

		$namespace = $element_setting->get_namespace();

		if ( ! $namespace ) {
			return;
		}

		if ( $element->get_settings( 'pagination_individual_handle' ) === 'yes' ) {
			// That means we're using autogenerate pagination argument, and we need to set this in the query.
			// TODO - thi whole function is not working well with variants...
			// adding this code here just as a test.
			if ( $element_setting->get_support( 'setting_pagination_control' ) ) {
				
				// Adds support for our own load more button.
				// We can only set ID in this type of widget so just choose the first one.
				$query_id = $query_ids[0];
				$query = $this->get_query_object_by_id( $query_id );
				if ( $query ) {
					$pagination_key = $this->get_element_pagination_key( $element );
					$query->set_render_config_value( 'paginationKey', $pagination_key );
				}
			}
		}

		if ( 'search_filter_query' !== $settings[ $namespace . '_post_type' ] ) {
			return;
		}

		// TODO - we might need to get the ID if for global queries?
		$this->current_query_id = $this->get_query_id_from_option( $settings['search_filter_query'] );

		if ( $element_setting->get_support( 'no_results_message' ) ) {
			$this->current_no_results_message = $settings['search_filter_no_results'];
		}

		if ( $element_setting->get_support( 'elementor/query' ) ) {

			// Catch the query thats generated before its used - so we can adjust `found_posts` and make elementor think
			// it needs to be rendered in the event there are no results.
			add_action( 'elementor/query/query_results', array( $this, 'adjust_elementor_query_found_posts' ), 100 );

			
		}

		if ( $element_setting->get_support( 'woocommerce/query' ) ) {
			// Add filter to modify the WC shortcode query.
			add_filter( 'woocommerce_shortcode_products_query', array( $this, 'attach_sf_to_wc_shortcode_args' ), 1000, 3 );
	
			// Adjust total results to ensure Elementor generates the container markup for the element.
			add_filter( 'woocommerce_shortcode_products_query_results', array( $this, 'adjust_wc_query_total' ), 10 );
	
			// Add the no results message, never fired without the above total being set.
			add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_wc_no_results' ), 10 );
		}
	}

	public function native_widget_after_render( $element ) {

		$element_setting = Element_Integrations::get( $element );

		if ( ! $element_setting ) {
			return;
		}

		if ( $element_setting->get_support( 'elementor/query' ) ) {
			remove_filter( 'elementor/query/query_results', array( $this, 'adjust_elementor_query_found_posts' ), 100 );
			remove_filter( 'elementor/query/query_args', array( $this, 'attach_query' ), 10, 2 ); // attach S&F to the post loop grid query
		}

		if ( $element_setting->get_support( 'woocommerce/query' ) ) {
			remove_filter( 'woocommerce_shortcode_products_query', array( $this, 'attach_sf_to_wc_shortcode_args' ), 100, 3 );
			remove_filter( 'woocommerce_shortcode_products_query_results', array( $this, 'adjust_wc_query_total' ), 10 );
			remove_filter( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_wc_no_results' ), 10 );
		}

		// Reset any tracking.
		$this->current_query_id = 0;
		$this->current_no_results_message = '';
	}

	private function get_query_id_from_option( $query_value ) {
		$query_id = str_replace( 'query_', '', $query_value );
		return absint( $query_id );
	}

	/**
	 * Add S&F results class to AE widgets
	 *
	 * Add a class to the widget on frontend
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function filter_ae_before_render( $element ) {
		$sf_element_types = array( 'ae-post-blocks', 'ae-post-blocks-adv' );
		$element_name     = $element->get_name();

		if ( in_array( $element->get_name(), $sf_element_types ) ) {
			$element_data     = $element->get_data();
			$element_settings = $element_data['settings'];

			if ( ! isset( $element_settings['search_filter_query'] ) ) {
				return;
			}

			if ( $element_name === 'ae-post-blocks' ) {
				$source_name = 'ae_post_type';
			} elseif ( $element_name === 'ae-post-blocks-adv' ) {
				$source_name = 'source';
			}

			// Attach S&F to query
			if ( $element_name === 'ae-post-blocks' ) {
				add_filter( 'aepro/post-blocks/custom-source-query', array( $this, 'filter_ae_query' ), -1, 2 );
			} elseif ( $element_name === 'ae-post-blocks-adv' ) {
				add_filter( 'aepro/post-blocks-adv/custom-source-query', array( $this, 'filter_ae_query_adv' ), -1, 2 );
			}
		}
	}

	public function filter_ae_query( $query_args, $settings ) {
		if ( ! isset( $settings['search_filter_query'] ) ) {
			return $query_args;
		}
		if ( ! isset( $settings['ae_post_type'] ) ) {
			return $query_args;
		}
		if ( 'search_filter_query' !== $settings['ae_post_type'] ) {
			return $query_args;
		}

		$sfid = $this->get_query_id_from_option( $settings['search_filter_query'] );
		// $query_args['post_type'] = 'post'; //set the post type to something that actually exists so the query doesn't abort early
		$query_args = array(
			'post_type'              => 'post',
			'search_filter_query_id' => $sfid,
		);

		return $query_args;
	}
	public function filter_ae_query_adv( $query_args, $settings ) {
		if ( ! isset( $settings['search_filter_query'] ) ) {
			return $query_args;
		}
		if ( ! isset( $settings['source'] ) ) {
			return $query_args;
		}
		if ( 'search_filter_query' !== $settings['source'] ) {
			return $query_args;
		}
		$sfid = $this->get_query_id_from_option( $settings['search_filter_query'] );
		// $query_args['post_type'] = 'post'; //set the post type to something that actually exists so the query doesn't abort early
		$query_args = array(
			'post_type'              => 'post',
			'search_filter_query_id' => $sfid,
		);

		return $query_args;
	}

	/**
	 * Hack `found_posts` for posts + portfolio widgets
	 *
	 * Takes a WP Query, and changes the `found_posts` value - this is a hack to get around some
	 * of Elementors issues with displaying "no results" messages for posts + products elements
	 *
	 * if found_posts === 0, the element will not get rendered - see -
	 * elementor-pro/modules/posts/skins/skin-base.php - render()
	 * which returns no output from render function - causing the whole widget not to be rendered
	 * at all - see -
	 * elementor/includes/base/widget-base.php - render_content()
	 * which also returns early if `$widget_content` is empty, and happens before the wrapper divs/containers
	 * are created... we want to keep the wrappers to put our no results message and let Elementor
	 * load its layout scripts
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function adjust_elementor_query_found_posts( $query ) {
		if ( 0 === $query->found_posts ) {

			$query->set( 'found_posts', 1 );
			$query->found_posts = 1;
		}
	}

	private function get_connected_query_ids_for_element_setting( $element, $element_setting ) {

		$element_namespace = $element_setting->get_namespace();
		$settings = $element->get_settings();

		if ( ! $element_namespace ) {
			return array();
		}
		
		$supports_setting_query_control = $element_setting->get_support( 'setting_query_control' );
		if ( $supports_setting_query_control ) {
			if ( ! isset( $settings[ $element_namespace . '_post_type' ] ) ) {
				return array();
			}
			// If not set to current query return early.
			$valid_query_types = array( 'current_query', 'search_filter_query' );
			if ( ! in_array( $settings[ $element_namespace . '_post_type' ], $valid_query_types, true ) ) {
				return array();
			}
			// If its set to S&F query, return the query ID.
			if ( 'search_filter_query' === $settings[ $element_namespace . '_post_type' ] ) {
				return array( $this->get_query_id_from_option( $settings['search_filter_query'] ) );
			}
		}

		// At this point we know we either have an element that has no query controls, or 
		// its set to current query - so lets check for global queries.
		return $this->get_connected_global_queries();
		
	}

	/**
	 * Wrapper for accessing the used query attributes.
	 * 
	 * The used queries are not query objects, but render data.
	 */ 
	private function get_query_data_attribute( $query_data, $attribute_name ) {
		if ( ! isset( $query_data['attributes'] ) ) {
			return null;
		}
		if ( ! isset( $query_data['attributes'][ $attribute_name ] ) ) {
			return null;
		}
		return $query_data['attributes'][ $attribute_name ];
	}
	private function get_connected_global_queries() {

		$queries = $this->get_used_queries();
		$global_integration_types = array( 'archive', 'search', 'woocommerce/shop' );
		
		$connected_ids = array();
		foreach ( $queries as $query_data ) {
			if ( in_array( $this->get_query_data_attribute( $query_data, 'integrationType' ), $global_integration_types, true ) ) {
				$connected_ids[] = $query_data['id'];
			}
		}
		return $connected_ids;
	}

	private function get_paged_url_args( $pagination_key = 'paged' ) {
		$request = remove_query_arg( $pagination_key );
		
		$parsed_url = wp_parse_url( $request );
		if ( ! $parsed_url ) {
			return array();
		}

		if ( ! isset( $parsed_url['query'] ) ) {
			return array();
		}

		// Parse the query string into an array.
		$url_args = array();
		wp_parse_str( $parsed_url['query'], $url_args );
		$url_args = urlencode_deep( $url_args );
		
		// Remove out api endpoint arg from the args.
		if ( isset( $url_args[ 'search-filter-api' ] ) ) {
			unset( $url_args[ 'search-filter-api' ] );
		}

		return $url_args;
	}

	private function get_element_pagination_key( $element ) {
		if ( $element->get_settings( 'pagination_individual_handle' ) === 'yes' ) {
			return 'e-page-' . $element->get_id();
		} else {
			return 'paged';
		}
	}
	/**
	 * Display no results message + fix pagination
	 *
	 * First check to make sure the element is one that should be affected by S&F
	 * Then check to see if we REALLY had no results (check `$query->posts` not `$query->found_posts`)
	 * and attach the no results message.
	 * Also fix pagination issues caused by the prev/next buttons in Elementors pagination
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function filter_native_widget_render( $element_html, $element ) {

		$element_setting = Element_Integrations::get( $element );
		
		if ( ! $element_setting ) {
			return $element_html;
		}

		$settings = $element->get_settings();
		
		$control_prefix = $element_setting->get_namespace();

		if ( ! $control_prefix ) {
			return $element_html;
		}

		if ( $element_setting->get_support( 'setting_pagination_control' ) ) {

			// Check and modify URL links to add the search params.
			// Technically only the prev/next, load more and infinite scroll pagination links are
			// affected when using the loop grid or posts widget embedded on a single page.
			// The widgets seem to work fine and generate the links correctly when using archives
			// and "current query".

			$pagination_key = $this->get_element_pagination_key( $element );
			// Get the URL args as done in `get_page_num_link`
			$url_args = $this->get_paged_url_args( $pagination_key );			
			/*
			 * Use the html tag processor to parse `$element_html` looking for the class `e-load-more-anchor`
			 * and copy over any url arguments that should be present from our search.
			*/
			$load_more_pagination_types = array( 'load_more_on_click', 'load_more_on_scroll' );
			if ( in_array( $element->get_settings( 'pagination_type' ), $load_more_pagination_types, true ) ) {
				// Process load more anchor if it exists
				$processor = new \WP_HTML_Tag_Processor( $element_html );
				if ( $processor->next_tag( array( 'class_name' => 'e-load-more-anchor' ) ) ) {
					$url = $processor->get_attribute( 'data-next-page' );
					$url = add_query_arg( $url_args, $url );
					$processor->set_attribute( 'data-next-page', $url );
					$element_html = $processor->get_updated_html();
				}
			}
			
			/*
			 * Use the html tag processor to parse `$element_html` looking for the the prev/next pagination link
			 * as they also don't carry over URL args (the numbered pagination does though).  Copy over any URL args
			 * that should be present from our search.
			*/
			$prev_next_pagination_types = array( 'prev_next', 'numbers_and_prev_next' );
			if ( in_array( $element->get_settings( 'pagination_type' ), $prev_next_pagination_types, true ) ) {
				
				// Process load more anchor if it exists
				$processor = new \WP_HTML_Tag_Processor( $element_html );
				// Find the next anchor with class .page-numbers
				while ( $processor->next_tag( array( 'tag_name' => 'a', 'class_name' => 'page-numbers' ) ) ) {
					if( $processor->has_class( 'next' ) || $processor->has_class( 'prev' ) ) {

						$url = $processor->get_attribute( 'href' );
						$url = add_query_arg( $url_args, $url );
						$processor->set_attribute( 'href', $url );
	
						$element_html = $processor->get_updated_html();
					}
					
				
				}
			}
		}


		if ( ! isset( $settings[ $control_prefix . '_post_type' ] ) ) {
			return $element_html;
		}

		if ( 'search_filter_query' !== $settings[ $control_prefix . '_post_type' ] ) {
			return $element_html;
		}

		if ( $element_setting->get_support( 'elementor/query' ) ) {
				
			$query = $element->get_query();

			// if $query->found_posts is 1, but the $query->posts array is empty (so found posts should be 0)
			// then its our hack from earlier, to get elementor to display the element markup with without content
			// when there are no results

			// add no results message
			if ( ( $query->found_posts == 1 ) && ( 0 === count( $query->posts ) ) ) {
				if ( $element_setting->get_support( 'no_results_message' ) ) {
					// then there were no results, show the no results message
					$element_html = '';
					if ( ! empty( $this->current_no_results_message ) ) {
						$class_name = '';
						if ( $element_setting->get_support( 'no_results_message_container_class' ) ) {
							$class_name = $element_setting->get_support( 'no_results_message_container_class' );
						}
						
						$element_html = wp_kses_post( '<span class="search-filter-query--no-results ' . esc_attr( $class_name ) . '">' . esc_html( $this->current_no_results_message ) . '</span>' );
					}
				}
			}
		}

		return $element_html;
	}


	/*
	* Hack Elementor to bypass checks and still display the element
	*
	* Trick Elementor in to thinking there is at least 1 result so it continues normal output of the element - which means, we can insert a "no results" message
	* Very similar to  - adjust_elementor_query_found_posts
	*
	* @since 1.0.0
	*
	* @access public
	*/
	public function adjust_wc_query_total( $results ) {
		if ( empty( $results->total ) ) {
			$results->total = 1;
		}
		return $results;
	}

	/*
	* Add "no results" message
	*
	* Now Elementor has been tricked into thinking there are results this hook is actually fired by WC
	*
	* @since 1.0.0
	*
	* @access public
	*/
	public function add_wc_no_results() {
		// then there were no results, show the no results message

		/* NOTICE - do not use the filter `search_filter_elementor_no_results_text` - it will be deprecated */
		$no_results_message = apply_filters( 'search_filter_elementor_no_results_text', esc_html( $this->current_no_results_message ), $this->current_query_id );

		echo '<span data-search-filter-action="infinite-scroll-end">' . $no_results_message . '</span>';
		$this->current_no_results_message = '';

		remove_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_wc_no_results' ), 10 );
	}
	

	public function is_elementor_archive() {
		$location = 'archive';
		if ( class_exists( '\ElementorPro\Plugin' ) ) {
			$conditions_manager    = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_conditions_manager();
			$documents_for_archive = $conditions_manager->get_documents_for_location( $location );

			if ( ! empty( $documents_for_archive ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add a Elementor Display Method to search form admin
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function search_filter_admin_option_display_results( $display_results_options ) {
		$display_results_options['elementor_posts_element'] = array(
			'label'       => __( 'Elementor Posts / Portfolio / Products Widget', 'search-filter-elementor' ),
			'description' =>
			'<p>' . __( 'Search Results will displayed using one of Elementors <strong>Posts</strong>, <strong>Portfolio</strong>, <strong>Products</strong> or <strong>Loop Grid<strong> Elements.', 'search-filter-elementor' ) . '</p>' .
			'<p>' . __( 'Remember to set the <strong>Query Source</strong> in your Elementor widget to use this Search Form.', 'search-filter-elementor' ) . '</p>' .
			'<p>' . __( 'If you want to filter a <strong>Post Type Archive</strong> or the <strong>WooCommerce Shop / Products Archive</strong>, use those display methods instead.', 'search-filter-elementor' ) . '</p>',
			'base'        => 'shortcode',
		);
		$display_results_options['elementor_loop_widget']   = array(
			'label'       => __( 'Elementor Loop Grid Widget', 'search-filter-elementor' ),
			'description' =>
			'<p>' . __( 'Search Results will displayed using Elementors <strong>Loop Grid<strong> Widget.', 'search-filter-elementor' ) . '</p>' .
			'<p>' . __( 'Remember to set the <strong>Query Source</strong> in your Elementor widget to use this Search Form.', 'search-filter-elementor' ) . '</p>' .
			'<p>' . __( 'If you want to filter a <strong>Post Type Archive</strong> or the <strong>WooCommerce Shop / Products Archive</strong>, use those display methods instead.', 'search-filter-elementor' ) . '</p>',
			'base'        => 'shortcode',
		);

		return $display_results_options;
	}

	/**
	 * Get array of pagination URLs
	 *
	 * Modified version of `paginate_links` function, which will only return
	 * an array of prev/next urls
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	private function get_prev_next_pagination( $args = '' ) {
		// customised version of `paginate_links` which only returns prev/next urls
		global $wp_query, $wp_rewrite;

		// Setting up default values based on the current URL.
		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		$defaults = array(
			'base'               => '', // must supply
			'format'             => '', // must supply
			'total'              => '', // must supply
			'current'            => '', // must supply
			'aria_current'       => 'page',
			'show_all'           => false,
			'prev_next'          => true,
			'prev_text'          => __( '&laquo; Previous' ),
			'next_text'          => __( 'Next &raquo;' ),
			'end_size'           => 1,
			'mid_size'           => 2,
			// 'type'               => 'plain', //not needed
			'add_args'           => array(), // array of query args to add
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! is_array( $args['add_args'] ) ) {
			$args['add_args'] = array();
		}

		// Merge additional query vars found in the original URL into 'add_args' array.
		if ( isset( $url_parts[1] ) ) {
			// Find the format argument.
			$format       = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
			$format_query = isset( $format[1] ) ? $format[1] : '';
			wp_parse_str( $format_query, $format_args );

			// Find the query args of the requested URL.
			wp_parse_str( $url_parts[1], $url_query_args );

			// Remove the format argument from the array of query arguments, to avoid overwriting custom format.
			foreach ( $format_args as $format_arg => $format_arg_value ) {
				unset( $url_query_args[ $format_arg ] );
			}

			$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
		}

		// Who knows what else people pass in $args
		$total = (int) $args['total'];
		if ( $total < 2 ) {
			return;
		}

		$current    = (int) $args['current'];
		$add_args   = $args['add_args'];
		$page_links = array(
			'prev' => '',
			'next' => '',
		);

		if ( $args['prev_next'] && $current && 1 < $current ) :
			$link = str_replace( '%_%', 2 == $current ? '' : $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current - 1, $link );
			if ( $add_args ) {
				$link = add_query_arg( $add_args, $link );
			}
			$link              .= $args['add_fragment'];
			$page_links['prev'] = apply_filters( 'paginate_links', $link );

			else :
				$page_links['prev'] = '';
			endif;

			if ( $args['prev_next'] && $current && $current < $total ) :
				$link = str_replace( '%_%', $args['format'], $args['base'] );
				$link = str_replace( '%#%', $current + 1, $link );
				if ( $add_args ) {
					$link = add_query_arg( $add_args, $link );
				}
				$link              .= $args['add_fragment'];
				$page_links['next'] = apply_filters( 'paginate_links', $link );

			else :
				$page_links['next'] = '';
			endif;

			return $page_links;

	}

	public static function get_frontend_assets_url() {
		$assets_url = SEARCH_FILTER_ELEMENTOR_URL . 'assets/';
		if ( defined( 'SEARCH_FILTER_ELEMENTOR_FRONTEND_ASSETS_URL' ) ) {
			$assets_url = SEARCH_FILTER_ELEMENTOR_FRONTEND_ASSETS_URL;
		}
		return $assets_url;
	}

	/**
	 * Checks to see whether we need to run any upgrades.
	 *
	 * If not, update the registered version.
	 *
	 * V3 only.
	 *
	 * @return void
	 */
	private function check_for_upgrades() {
		$this->extension = \Search_Filter_Pro\Core\Extensions::get( self::SLUG );
		if ( ! $this->extension ) {
			return;
		}

		if ( $this->extension->has_upgraded() ) {
			$upgrades_complete = $this->run_upgrades();
			if ( $upgrades_complete ) {
				$this->extension->upgrade();
			}
		}
	}

	private function run_upgrades() {
		$upgrades           = array(
			'1.3.3' => 'upgrade_to_1_3_3',
			'1.4.0' => 'upgrade_to_1_4_0',
		);
		$upgrades_completed = 0;
		$total_upgrades     = 0;

		$registered_version = $this->extension->get_registered_version();
		if ( ! $registered_version ) {
			$registered_version = '0.0.0';
		}

		foreach ( $upgrades as $version => $method ) {
			if ( version_compare( $registered_version, $version, '<' ) ) {
				$total_upgrades++;
				$upgraded = $this->$method();
				if ( $upgraded ) {
					$upgrades_completed++;
				}
			}
		}
		return $upgrades_completed === $total_upgrades;
	}
	private function upgrade_to_1_3_3() {
		// This is a one time clearing of updater caches, after this upgrade the main
		// plugin should be able to keep all this in sync.
		$plugins_slugs = array( 'search-filter-pro', 'search-filter', 'search-filter-bb', 'search-filter-elementor' );
		foreach ( $plugins_slugs as $plugin_slug ) {
			$cache_key             = 'edd_sl_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			$api_request_cache_key = 'edd_api_request_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			delete_option( $cache_key );
			delete_option( $api_request_cache_key );
		}

		return true;
	}
	private function upgrade_to_1_4_0() {
		// This is a one time clearing of updater caches, after this upgrade the main
		// plugin should be able to keep all this in sync.
		// Resave queries.
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}

			$previous_integration_types = array(
				'elementor/loop-grid',
				'elementor/loop-carousel',
				'elementor/portfolio',
				'elementor/posts',
				'elementor/woocommerce-products',
			);

			// Now update integration single + location dynamic, into just dynamic.
			$integration_type = $query->get_attribute( 'queryIntegration' );

			// Handle upgrade for removal of "dynamic" toggle from singe integrations.
			// Instead convert it to the new dynamic integration method.
			if ( in_array( $integration_type, $previous_integration_types, true ) ) {
				$query->set_attribute( 'queryIntegration', 'elementor/widget' );
			}

			$query->save();
		}

		return true;
	}
}
