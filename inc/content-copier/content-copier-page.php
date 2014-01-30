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
		$this->updating = $updating;
		$this->sync = $sync;

	}

	protected function get_defaults_args() {
		return array(
			'copy_images' => false,
			'post_ids' => array(),
			'keep_user' => true,
			'update_date' => false,
			'copy_parents' => false,
			'copy_comments' => false,
			'updating' => false,
			'sync' => false
		);
	}

	public function execute() {
		foreach( $this->post_ids as $post_id ) {
			$this->copy( $post_id );
			update_post_meta( $post_id, 'mcc_copied', true );
		}
	}

	public function copy( $post_id ) {

		$source_post_id = $post_id;
		$source_blog_id = $this->orig_blog_id;
		do_action( 'mcc_before_copy_post', $source_blog_id, $source_post_id );

		$new_post_id = $this->copy_post( $post_id );
		
		if ( ! $new_post_id )
			return false;

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

		if ( empty( $orig_post ) )
			return false;

		$orig_post_meta = $this->get_orig_blog_post_meta( $post_id );

		// Insert post in the new blog ( we should be currently on it)
		$postarr = $this->get_postarr( $orig_post );

		// Are we updating the post
		if ( $this->updating ) {

			$model = mcc_get_model();
			$child_id = $model->get_synced_child( $post_id, $this->orig_blog_id );

			if ( ! empty( $child_id ) )
				$postarr['ID'] = absint( $child_id );
		}

		$new_post_id = wp_insert_post( $postarr );

		if ( $new_post_id ) {
			do_action( 'mcc_copy_post', $this->orig_blog_id, $post_id, $new_post_id );

			// Do we have to sync the post for the future?
			if ( $this->sync ) {
				$model = mcc_get_model();
				$dest_post = array( 
					array( 
						'blog_id' => get_current_blog_id(), 
						'post_id' => $new_post_id 
					)
				);

				$settings = array(
					'class' => get_class( $this ),
					'copy_images' => $this->copy_images
				);
				$model->add_synced_content( $post_id, $this->orig_blog_id, $dest_post, $settings );
			}

			// Insert post meta
			foreach ( $orig_post_meta as $post_meta ) {
				$unserialized_meta_value = maybe_unserialize( $post_meta->meta_value );
				update_post_meta( $new_post_id, $post_meta->meta_key, $unserialized_meta_value );
				do_action( 'mcc_copy_post_meta', $this->orig_blog_id, $post_id, $new_post_id, $post_meta->meta_key, $unserialized_meta_value );
			}			
		}

		return $new_post_id;
		
	}
}