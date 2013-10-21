<?php

class Multisite_Content_Copier_Page_Copier extends Multisite_Content_Copier_Copier {
	
	protected $post_ids;
	protected $copy_images;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->post_ids = is_array( $post_ids ) ? $post_ids : array( $post_ids );
		$this->copy_images = $copy_images;
		$this->copy_parents = $copy_parents;
		$this->update_date = $update_date;
		$this->copy_comments = $copy_comments;

	}

	protected function get_defaults_args() {
		return array(
			'copy_images' => false,
			'post_ids' => array(),
			'keep_user' => true,
			'update_date' => false,
			'copy_parents' => false,
			'copy_comments' => false
		);
	}

	public function execute() {
		foreach( $this->post_ids as $post_id ) {
			$this->copy( $post_id );
		}
	}

	public function copy( $post_id ) {
		$new_post_id = $this->copy_post( $post_id );
		$new_parent_post_id = false;

		if ( $this->copy_parents ) {
			$parent_post_id = $this->get_orig_post_parent( $post_id );

			if ( $parent_post_id ) {
				$new_parent_post_id = $this->copy_post( $parent_post_id );
				$this->update_dest_post_parent( $new_post_id, $new_parent_post_id );
			}
		}

		if ( $this->copy_images ) {
			$this->copy_media( $post_id, $new_post_id );

			if ( absint( $new_parent_post_id ) ) {
				$this->copy_media( $parent_post_id, $new_parent_post_id );
			}
		}

		if ( $this->update_date ) {
			$this->update_post_date( $new_post_id, current_time( 'mysql' ) );

			if ( absint( $new_parent_post_id ) ) {
				$this->update_post_date( $new_parent_post_id, current_time( 'mysql' ) );
			}
		}

		if ( $this->copy_comments ) {
			$this->copy_comments( $post_id, $new_post_id );

			if ( absint( $new_parent_post_id ) ) {
				$this->copy_comments( $parent_post_id, $new_parent_post_id );
			}
		}

		return array(
			'new_post_id' => $new_post_id,
			'new_parent_post_id' => $new_parent_post_id
		);
	}

	public function copy_post( $post_id ) {

		// Getting original post data
		$orig_post = $this->get_orig_blog_post( $post_id );
		$orig_post_meta = $this->get_orig_blog_post_meta( $post_id );

		// Insert post in the new blog ( we should be currently on it)
		$postarr = $this->get_postarr( $orig_post );
		$new_post_id = wp_insert_post( $postarr );

		if ( $new_post_id ) {
			// Insert post meta
			foreach ( $orig_post_meta as $post_meta ) {
				update_post_meta( $new_post_id, $post_meta->meta_key, $post_meta->meta_value );
			}			
		}

		return $new_post_id;
		
	}
}