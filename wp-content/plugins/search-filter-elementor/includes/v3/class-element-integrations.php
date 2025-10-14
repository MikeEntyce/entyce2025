<?php
/**
 *
 */
namespace Search_Filter_Elementor_Extension\Version_3;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Element_Integrations {
	private static $supported_elements_config = array(
		'posts'         => array(
			'namespace' => 'posts',	
			'type' => 'post',
			'supports' => array(
				'setting_query_control' => true,
				'setting_pagination_control' => true,
				'no_results_message' => true,
				'elementor/query' => true,
				'elementor/query_conditions' => array(
					'posts_post_type' => 'search_filter_query',
				),
				'posts_container' => '.elementor-posts-container',
				'hide_controls' => array(
					'query_args',
					'query_include',
					'query_exclude',
					'posts_ids',
					'include',
					'include_term_ids',
					'related_taxonomies',
					'include_authors',
					'exclude',
					'exclude_ids',
					'exclude_term_ids',
					'exclude_authors',
					'avoid_duplicates',
					'offset',
					'related_fallback',
					'fallback_ids',
					'select_date',
					'date_before',
					'date_after',
					'orderby',
					// 'order',
					'ignore_sticky_posts',
					'query_id',
				),
				'hide_controls_conditions' => array(
					'post_type' => 'search_filter_query',
				),
			),
		),
		'archive-posts'         => array(
			'namespace' => 'posts',	
			'type' => 'post',
			'supports' => array(
				'posts_container' => '.elementor-posts-container',
			),
		),
		'woocommerce-products' => array(
			'namespace' => 'query',
			'type' => 'post',
			'supports' => array(
				'woocommerce/query' => true,
				'no_results_message' => true,
				'setting_query_control' => true,
				'posts_container' => '.products',
				'hide_controls' => array(
					'query_args',
					'query_include',
					'query_exclude',
					'posts_ids',
					'include',
					'include_term_ids',
					'related_taxonomies',
					'include_authors',
					'exclude',
					'exclude_ids',
					'exclude_term_ids',
					'exclude_authors',
					'avoid_duplicates',
					'offset',
					'related_fallback',
					'fallback_ids',
					'select_date',
					'date_before',
					'date_after',
					'orderby',
					// 'order', // There is a bug when using WC, unsetting this
					// throws an error - need to think of a better way around it.
					'ignore_sticky_posts',
					'query_id',
				),
			),
		),
		'wc-archive-products'         => array(
			'namespace' => 'posts',	
			'type' => 'post',
			'supports' => array(
				'posts_container' => '.products',
			),
		),
		'portfolio'     => array(
			'namespace' => 'posts',
			'type' => 'post',
			'supports' => array(
				'setting_query_control' => true,
				'no_results_message' => true,
				'no_results_message_container_class' => 'elementor-portfolio',
				'elementor/query' => true,
				'elementor/query_conditions' => array(
					'posts_post_type' => 'search_filter_query',
				),
				'posts_container' => '.elementor-portfolio',
				'add_pagination' => 'paged',
				'hide_controls' => array(
					'query_args',
					'query_include',
					'query_exclude',
					'posts_ids',
					'include',
					'include_term_ids',
					'related_taxonomies',
					'include_authors',
					'exclude',
					'exclude_ids',
					'exclude_term_ids',
					'exclude_authors',
					'avoid_duplicates',
					'offset',
					'related_fallback',
					'fallback_ids',
					'select_date',
					'date_before',
					'date_after',
					'orderby',
					'order',
					'ignore_sticky_posts',
					'query_id',
				),
				'hide_controls_conditions' => array(
					'post_type' => 'search_filter_query',
				),
			),
		),
		'loop-grid'     => array(
			'supports' => array(
				'posts_container' => '.elementor-loop-container',
				// Cannot be used on variants yet as we don't prefix the added setting name (search_filter_query) with the variant namespace.
				'setting_query_control' => true, 
				'setting_pagination_control' => true,
			),
			'variants' => array(
				array(
					'conditions' => array(
						'_skin' => 'post',
					),
					'namespace' => 'post_query',
					'type' => 'post',
					'supports' => array(
						'elementor/query' => true,
						'elementor/query_conditions' => array(
							'_skin' => 'post',
						),
						'hide_controls' => array(
							'query_args',
							'query_include',
							'query_exclude',
							'posts_ids',
							'include',
							'include_term_ids',
							'related_taxonomies',
							'include_authors',
							'exclude',
							'exclude_ids',
							'exclude_term_ids',
							'exclude_authors',
							'avoid_duplicates',
							'offset',
							'related_fallback',
							'fallback_ids',
							'select_date',
							'date_before',
							'date_after',
							'orderby',
							'order',
							'ignore_sticky_posts',
							'query_id',
						),
						'hide_controls_conditions' => array(
							'post_type' => 'search_filter_query',
						),
					),
				),
				array(
					'conditions' => array(
						'_skin' => 'product',
					),
					'namespace' => 'product_query',
					'type' => 'post',
					'supports' => array(
						'woocommerce/query' => true,
						'woocommerce/query_conditions' => array(
							'_skin' => 'product',
						),
						'elementor/pagination' => array(
							'numbers' => true,
							'prev_next' => true,
							'numbers_and_prev_next' => true,
							'load_more_on_click' => true,
							'load_more_infinite_scroll' => true,
						),
						'hide_controls' => array(
							'query_args',
							'query_include',
							'query_exclude',
							'posts_ids',
							'include',
							'include_term_ids',
							'related_taxonomies',
							'include_authors',
							'exclude',
							'exclude_ids',
							'exclude_term_ids',
							'exclude_authors',
							'avoid_duplicates',
							'offset',
							'related_fallback',
							'fallback_ids',
							'select_date',
							'date_before',
							'date_after',
							'orderby',
							'order',
							'ignore_sticky_posts',
							'query_id',
						),
						'hide_controls_conditions' => array(
							'post_type' => 'search_filter_query',
						),
					),
				),
			)
			
		),
		'loop-carousel'     => array(
			'supports' => array(
				'posts_container' => '.swiper-wrapper',
				// Cannot be used on variants yet as we don't prefix the added setting name (search_filter_query) with the variant namespace.
				'setting_query_control' => true,
				'no_results_message' => true,
			),
			'variants' => array(
				array(
					'conditions' => array(
						'_skin' => 'post',
					),
					'namespace' => 'post_query',
					'type' => 'post',
					'supports' => array(
						'elementor/query' => true,
						'elementor/query_conditions' => array(
							'_skin' => 'post',
						),
						'hide_controls' => array(
							'query_args',
							'query_include',
							'query_exclude',
							'posts_ids',
							'include',
							'include_term_ids',
							'related_taxonomies',
							'include_authors',
							'exclude',
							'exclude_ids',
							'exclude_term_ids',
							'exclude_authors',
							'avoid_duplicates',
							'offset',
							'related_fallback',
							'fallback_ids',
							'select_date',
							'date_before',
							'date_after',
							'orderby',
							'order',
							'ignore_sticky_posts',
							'query_id',
						),
						'hide_controls_conditions' => array(
							'post_type' => 'search_filter_query',
						),
					),
				),
				array(
					'conditions' => array(
						'_skin' => 'product',
					),
					'namespace' => 'product_query',
					'type' => 'post',
					'supports' => array(
						'woocommerce/query' => true,
						'woocommerce/query_conditions' => array(
							'_skin' => 'product',
						),
						'hide_controls' => array(
							'query_args',
							'query_include',
							'query_exclude',
							'posts_ids',
							'include',
							'include_term_ids',
							'related_taxonomies',
							'include_authors',
							'exclude',
							'exclude_ids',
							'exclude_term_ids',
							'exclude_authors',
							'avoid_duplicates',
							'offset',
							'related_fallback',
							'fallback_ids',
							'select_date',
							'date_before',
							'date_after',
							'orderby',
							'order',
							'ignore_sticky_posts',
							'query_id',
						),
						'hide_controls_conditions' => array(
							'post_type' => 'search_filter_query',
						),
					),
				),
			)
			
		),
		
		'ae-post-blocks' => array(
			'namespace' => 'ae_post_type',
			'type' => 'post',
			'supports' => array(
				'setting_query_control' => true,
				'hide_controls' => array(
					'author_ae_ids',
					'tax_relation',
					'current_post',
					'advanced',
					'orderby',
					'order',
					'posts_per_page',
					'offset',
				),
				'hide_controls_conditions' => array(
					'post_type' => 'search_filter_query',
				),
			),
		),
		'ae-post-blocks-adv' => array(
			'namespace' => 'source',
			'type' => 'post',
			'supports' => array(
				'setting_query_control' => true,
				'hide_controls' => array(
					'author_query_heading',
					'author_query_tabs',
					'include_author_ids',
					'exclude_author_ids',

					'taxonomy_heading',
					'taxonomy_divider',
					'include_taxonomy_ids',
					'exclude_taxonomy_ids',

					'date_divider',
					'date_query_heading',
					'select_date',
					'post_status',
					'date_before',
					'date_after',
					'orderby',
					'order',
					'current_post',
					'offset',
					'posts_per_page',

					'tabs_include_exclude',
					'query_tax_ids',
				),
			),
		)
	);

	private static $supported_elements = array();

	public static function init() {
		foreach( self::$supported_elements_config as $element_name => $config ) {
			self::$supported_elements[ $element_name ] = new Element_Integration( $config );
		}
	}

	/**
	 * Returns the element or relevant variant that matches the conditions.
	 *
	 * @param [type] $element
	 * @return null|Element_Integration
	 */
	public static function get( $element, $resolve_to_variant = true ) {
		if ( ! isset( self::$supported_elements[ $element->get_name() ] ) ) {
			return null;
		}
		$variants = self::$supported_elements[ $element->get_name() ]->get_variants();
		if ( empty( $variants ) || ! $resolve_to_variant ) {
			return self::$supported_elements[ $element->get_name() ];
		}

		// Otherwise figure out which variant we need to return.
		// Note: this cannot be used in Elementor admin as the element won't have
		// settings.
		foreach( $variants as $variant ) {
			// Check and return the variation when its conditions are met.
			if ( empty( $variant->get_conditions() ) ) {
				continue;
			}
			$conditions = $variant->get_conditions();
			$element_settings = $element->get_settings();

			foreach ( $conditions as $setting_name => $required_value ) {
				if ( ! isset( $element_settings[ $setting_name ] ) ) {
					continue;
				}
				if ( $element_settings[ $setting_name ] !== $required_value ) {
					continue;
				}
			}
			return $variant;
		}
		
		return null;
	}

	/**
	 * Gets an element by name.
	 *
	 * Note: as we're using the element name to get the element we don't
	 * support variant conditons.  This should only really be used when
	 * we are looking for info regarding the top level element.
	 * 
	 * @param string $name
	 * @return null|Element_Integration
	 */
	public static function get_by_name( $name ) {
		if ( ! isset( self::$supported_elements[ $name ] ) ) {
			return null;
		}
		return self::$supported_elements[ $name ];
	}

	/**
	 * Returns the names of all supported elements.
	 */
	public static function get_supported_names() {
		return array_keys( self::$supported_elements_config );
	}
}
