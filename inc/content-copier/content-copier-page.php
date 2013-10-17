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

	public function copy() {
		foreach( $this->pages_ids as $page_id ) {
			$new_page_id = $this->copy_post( $page_id );

			if ( $this->copy_images ) {
				$this->copy_media( $post_id, $new_page_id );
			}
		}
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