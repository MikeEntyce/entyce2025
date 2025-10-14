<?php

namespace Search_Filter_Elementor_Extension\Version_2\Widgets;

/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class Form extends \Elementor\Widget_Base {
	
	private $search_form_options = array();
	
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
		return 'search-filter-form';
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
		return __( 'Search & Filter Form', 'search-filter-elementor' );
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
		return 'search-filter-form-icon';
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
		return [ 'general' ];
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
			[
				'label' => __( 'Content', 'search-filter-elementor' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		
		
		//now add the dropdown to choose a S&F query
		$this->add_control( 'search_filter_query',
			array(
				'label' => 'Search & Filter Form' ,
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_search_form_options()
			)
		);
		

		$this->end_controls_section();

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
		
		//if S&F frontend is not loaded, load it (we're in wp-admin technically and we want to display our shortcode)
		if ( ! class_exists( 'Search_Filter' ) ){
			
			if ( defined( 'SEARCH_FILTER_PRO_BASE_PATH' ) ){
				require_once( plugin_dir_path( SEARCH_FILTER_PRO_BASE_PATH ) . 'public/class-search-filter.php' );
				\Search_Filter::get_instance();
			}
			else{
				return;
			}
		}
		
		$sfid = absint( $this->get_settings( 'search_filter_query' ) );
		if ( $sfid !== 0 ) {
			$shortcode = '[searchandfilter id="'.$sfid.'"]';
			echo do_shortcode( shortcode_unautop( $shortcode ) );
		} else {
			echo "Choose a search form";
		}
		
	}
	
	private function get_search_form_options()
	{
		if(empty($this->search_form_options)){
			
			$posts_query = 'post_type=search-filter-widget&post_status=publish&posts_per_page=-1';
			
			if ( class_exists( 'Search_Filter_Helper' )) {
				if( \Search_Filter_Helper::has_polylang() ){
					$posts_query .= '&lang=all';
				}
			}
			
			$custom_posts = new \WP_Query( $posts_query );
			
			if( $custom_posts->post_count > 0 ){
				foreach ($custom_posts->posts as $post){
					$this->search_form_options[$post->ID] = html_entity_decode(esc_html($post->post_title) );
				}
			}
		}
		
		return $this->search_form_options;
	}

}