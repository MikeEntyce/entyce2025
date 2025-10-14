<?php
/**
 *
 */
namespace Search_Filter_Elementor_Extension\Version_3;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Element_Integration {

	private $data = array();
	private $variants = array();
	private $parent = null;

	public function __construct( $data, $parent = null ) {
		$this->data = $data;
		$this->parent = $parent;

		if ( isset( $data['variants'] ) ) {
			foreach( $data['variants'] as $config ) {
				$this->variants[] = new Element_Integration( $config, $this );
			}
		}
	}

	public function get_variants() {
		return $this->variants;
	}

	public function get_namespace() {
		if ( ! isset( $this->data['namespace'] ) ) {
			return null;
		}
		return $this->data['namespace'];
	}

	public function get_type() {
		if ( ! isset( $this->data['type'] ) ) {
			return null;
		}
		return $this->data['type'];
	}
	public function get_support( $feature ) {
		
		if ( ! isset( $this->data['supports'] ) ) {
			if ( $this->parent ) {
				return $this->parent->get_support( $feature );
			}
			return null;
		}
		if ( ! isset( $this->data['supports'][ $feature ] ) ) {
			if ( $this->parent ) {
				return $this->parent->get_support( $feature );
			}
			return null;
		}
		return $this->data['supports'][ $feature ];
	}

	public function get_conditions() {
		if ( ! isset( $this->data['conditions'] ) ) {
			return null;
		}
		return $this->data['conditions'];
	}

	public function has_variants() {
		return ! empty( $this->variants );
	}
}
