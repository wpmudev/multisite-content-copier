<?php

class Multisite_Content_Copier_Page_Copier extends Multisite_Content_Copier_Copier implements Multisite_Content_Copier_Post {
	
	private $pages_ids;
	private $copy_images;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->pages_ids = is_array( $pages_ids ) ? $pages_ids : array( $pages_ids );
		$this->copy_images = $copy_images;
		$this->copy_parents = $copy_parents;
		$this->update_date = $update_date;
		$this->copy_comments = $copy_comments;

	}

	protected function get_defaults_args() {
		return array(
			'copy_images' => false,
			'pages_ids' => array(),
			'keep_user' => true,
			'update_date' => false,
			'copy_parents' => false,
			'copy_comments' => false
		);
	}

	public function execute() {
		foreach( $this->pages_ids as $page_id ) {
			$this->copy( $page_id );
		}
	}

	public function copy( $page_id ) {
		$new_page_id = $this->copy_post( $page_id );
		$new_parent_page_id = false;

		if ( $this->copy_parents ) {
			$parent_page_id = $this->get_orig_post_parent( $page_id );

			if ( $parent_page_id ) {
				$new_parent_page_id = $this->copy_post( $parent_page_id );
				$this->update_dest_post_parent( $new_page_id, $new_parent_page_id );
			}
		}

		if ( $this->copy_images ) {
			$this->copy_media( $page_id, $new_page_id );

			if ( absint( $new_parent_page_id ) ) {
				$this->copy_media( $parent_page_id, $new_parent_page_id );
			}
		}

		if ( $this->update_date ) {
			$this->update_post_date( $new_page_id, current_time( 'mysql' ) );

			if ( absint( $new_parent_page_id ) ) {
				$this->update_post_date( $new_parent_page_id, current_time( 'mysql' ) );
			}
		}

		if ( $this->copy_comments ) {
			$this->copy_comments( $page_id, $new_page_id );

			if ( absint( $new_parent_page_id ) ) {
				$this->copy_comments( $parent_page_id, $new_parent_page_id );
			}
		}

		return array(
			'new_page_id' => $new_page_id,
			'new_parent_page_id' => $new_parent_page_id
		);
	}

	public function copy_post( $post_id ) {

		// Getting original post data
		$orig_post = $this->get_orig_blog_post( $post_id );
		$orig_post_meta = $this->get_orig_blog_post_meta( $post_id );

		// Insert post in the new blog ( we should be currently on it)
		$postarr = $this->get_postarr( $orig_post );
		$new_page_id = wp_insert_post( $postarr );

		if ( $new_page_id ) {
			// Insert post meta
			foreach ( $orig_post_meta as $post_meta ) {
				update_post_meta( $new_page_id, $post_meta->meta_key, $post_meta->meta_value );
			}			
		}

		return $new_page_id;
		
	}
}