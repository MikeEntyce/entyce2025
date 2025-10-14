<?php

namespace Search_Filter_Elementor_Extension\Version_3\Widgets;

use Search_Filter_Elementor_Extension\Version_3\Plugin;

/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class Field extends \Elementor\Widget_Base {

	private $search_filter_field = null;

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		wp_register_script( 'search-filter-elementor', Plugin::get_frontend_assets_url() . 'v3/js/frontend.js' , array( 'search-filter' ), \Search_Filter_Elementor_Extension::VERSION );

	}

	public function get_script_depends() {
		return array( 'search-filter-elementor' );
	}
	/**
	 * Get widget name.
	 *
	 * Retrieve oEmbed widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'search-filter-field';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve oEmbed widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Search & Filter Field', 'search-filter-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve oEmbed widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'search-filter-field-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the oEmbed widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Register oEmbed widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'search-filter-elementor' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$default_options = array(
			0 => __( 'Select a field', 'search-filter-elementor' ),
		);

		$options = array_merge(
			$default_options,
			\Search_Filter_Elementor_Extension\Version_3\Plugin::get_fields(),
		);

		// Now add the dropdown to choose a S&F query
		$this->add_control(
			'search_filter_field',
			array(
				'label'              => 'Search & Filter Field',
				'type'               => \Elementor\Controls_Manager::SELECT,
				'default'            => '',
				'options'            => $options,
				'frontend_available' => true,
				'description'        => sprintf(
					wp_kses_post( __( 'Select a field to embed. <a href="%s" target="_blank">Manage fields</a>', 'search-filter-elementor' ) ),
					admin_url( 'admin.php?page=search-filter&section=fields' )
				),
			)
		);

		$this->end_controls_section();

	}

	private function get_search_filter_field() {
		if ( $this->search_filter_field !== null ) {
			return $this->search_filter_field;
		}

		$field_id = $this->get_settings( 'search_filter_field' );
		$field_id = absint( str_replace( 'field_', '', $field_id ) );

		// If no ID has been selected, return null.
		if ( $field_id === 0 ) {
			return null;
		}

		// Try to look the field up by ID.
		$field = \Search_Filter\Fields\Field::find( array( 'id' => $field_id ) );
		if ( is_wp_error( $field ) ) {
			return null;
		}

		$this->search_filter_field = $field;

		return $field;
	}

	public function filter_search_filter_html_field_attributes( $attributes ) {

		$field = $this->get_search_filter_field();

		if ( $field === null ) {
			return $attributes;
		}
		
		// Remove the config ID so the field is not automatically initialised.
		if ( isset( $attributes['data-search-filter-id'] ) ) {
			unset( $attributes['data-search-filter-id'] );
		}
		
		$render_data = $field->get_render_data();
		if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
			$render_data['isInteractive'] = false;
		}
		
		$render_data['elementorId'] = $this->get_id();
		// Now add the render data to the data attribute so we can manually
		// re-init
		$attributes['data-search-filter-settings'] = json_encode( $render_data );
		return $attributes;
	}
	/**
	 * Render oEmbed widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$field = $this->get_search_filter_field();
		if ( $field === null ) {
			if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
				echo __( 'Choose a field', 'search-filter-elementor' );
			}
			return;
		}
		
		add_filter( 'search-filter/fields/field/render/html_attributes', array( $this, 'filter_search_filter_html_field_attributes' ) );
		$field->render();
		remove_filter( 'search-filter/fields/field/render/html_attributes', array( $this, 'filter_search_filter_html_field_attributes' ) );
	}
}
