<?php

class Multisite_Content_Copier_Post_Copier extends Multisite_Content_Copier_Page_Copier {

	private $copy_terms;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id, $args );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->copy_terms = $copy_terms;
	}

	protected function get_defaults_args() {
		$defaults = parent::get_defaults_args();
		$defaults['copy_terms'] = false;
		return $defaults;
	}

	public function copy( $post_id ) {
		$results = parent::copy( $post_id );

		$new_post_id = $results['new_post_id'];
		$new_parent_post_id = $results['new_parent_post_id'];

		// Copy terms?
		if ( $this->copy_terms ) {
			
			if ( absint( $new_parent_post_id ) ) {
				// Copy parents terms
				$parent_post_id = $this->get_orig_post_parent( $post_id );
				if ( $parent_post_id )
					$this->copy_terms( $parent_post_id, $new_parent_post_id );	
			}

			// Copy child terms 
			$this->copy_terms( $post_id, $new_post_id );
			
		}

		return $results;
	}

	private function copy_terms( $post_id, $new_post_id ) {

		// Categories
		$terms = $this->get_orig_blog_post_terms( $post_id, 'category' );
		$term_ids = array();

		foreach ( $terms as $term ) {
			$term_name = $term->name;
			$term_description = $term->description;

			$term = get_term_by( 'name', $term_name, 'category', ARRAY_A );
			if ( ! $term )
				$term = wp_insert_term( $term_name, 'category', array( 'description' => $term_description ) );

			if ( ! is_wp_error( $term ) )
				$term_ids[] = absint( $term['term_id'] );
		}
		if ( ! empty( $term_ids ) )
			wp_set_object_terms( $new_post_id, $term_ids, 'category' );

		// Tags
		$terms = $this->get_orig_blog_post_terms( $post_id, 'post_tag' );
		$term_ids = array();
		foreach ( $terms as $term ) {
			$term_name = $term->name;
			$term_description = $term->description;

			$term = get_term_by( 'name', $term_name, 'post_tag', ARRAY_A );
			if ( ! $term )
				$term = wp_insert_term( $term_name, 'post_tag', array( 'description' => $term_description ) );

			if ( ! is_wp_error( $term ) )
				$term_ids[] = absint( $term['term_id'] );
		}
		if ( ! empty( $term_ids ) )
			wp_set_object_terms( $new_post_id, $term_ids, 'post_tag' );


	}

}