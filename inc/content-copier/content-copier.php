<?php

abstract class Multisite_Content_Copier_Copier {

	protected $orig_blog_id;

	protected $orig_blog_url;
	protected $dest_blog_url;

	public function __construct( $orig_blog_id ) {
		
		$this->orig_blog_id = $orig_blog_id;

		$orig_blog_details = get_blog_details( $orig_blog_id, true );
		$this->orig_blog_url = $orig_blog_details->siteurl;

		$dest_blog_details = get_blog_details( get_current_blog_id(), true );
		$this->dest_blog_url = $dest_blog_details->siteurl;
	}

	abstract protected function get_defaults_args();

	public function get_all_media_in_post( $post_id ) {
		switch_to_blog( $this->orig_blog_id );
		$orig_post = get_post( $post_id );

		$orig_post_content = $orig_post->post_content;

		// Get all images in the post

		// We can insert in DB those images without a height/width
		// or those that are children of the post
		$images_as_attachments = array();

		// But we'll need to copy directly those ones with height/width
		// As another theme could not match the sizes with the current one
		$images_no_attachments = array();

		// 1. We try to get all images directly from the post content
		$pattern = "/<img.*?src=[\"'](.+?)[\"'].*?>/";
		preg_match_all( $pattern, $orig_post_content, $matches );

		if ( ! empty( $matches[1] ) ) {
			$images = array();
			$model = mcc_get_copier_model();
			$attachments_ids = array();

			foreach ( $matches[1] as $match ) {
				// Getting info about each image
				$file = basename( $match );
				$ext = pathinfo( $file, PATHINFO_EXTENSION );
				$pattern = "/\-([0-9]*)x([0-9]*)\.(" . $ext . ")$/";

				preg_match_all( $pattern, $file, $sizes );

				$image = array(
					'orig_src' => $match,
					'width' => ! empty( $sizes[1] ) ? $sizes[1][0] : false,
					'height' => ! empty( $sizes[2] ) ? $sizes[2][0] : false,
					'name' => preg_replace( $pattern, '', $file )
				);

				// No we need to know the post_ids of the attachments
				// and split the info in attachments/no attachments
				$data = $model->get_attachment_data( $image['name'] );

				if ( ! empty( $data->post_id ) && ( ! $image['width'] || ! $image['height'] ) ) {
					$image['post_id'] = $data->post_id;
					$image['orig_upload_file'] = $data->meta_value;
					$images_as_attachments[] = $image;
					$attachments_ids[] = $data->post_id;
				}
				elseif ( ! empty( $data->post_id ) && $image['width'] && $image['height'] ) {
					$image['post_id'] = $data->post_id;
					$image['orig_upload_file'] = $data->meta_value;
					$images_no_attachments[] = $image;
				}

			}

		}

		$attachments = array();
		foreach ( $attachments_ids as $id ) {
			$attachments[] = get_post( $id );
		}

		// 2. Now the thumbnail
		$thumbnail = get_post( get_post_thumbnail_id( $orig_post->ID ) );
		
		// 3. Now we get all the images that are children of the post
		$images = get_children(
			array(
				'post_parent' => $orig_post->ID,
				'post_type' => 'attachment',
				'numberposts' => -1,
				'post_mime_type' => 'image',
				'exclude' => ! empty( $thumbnail->ID ) ? $thumbnail->ID : 0,
			)
		);

		// We need to exclude those that are already in no_attachments
		$orig_images = array();
		foreach ( $images as $image ) {

			$metadata = get_post_meta( $image->ID, '_wp_attached_file', true );

			$found = false;
			foreach ( $images_no_attachments as $no_attachment ) {
				
				if ( strpos( $metadata, $no_attachment['name'] ) > -1 ) {
					$found = true;
					break;
				}
			}

			if ( ! $found )
				$orig_images[] = $image;
		}


		if ( ! empty( $thumbnail ) ) {
			$thumbnail->is_thumbnail = true;
			// All of them joined
			$orig_images = array_merge( array( $thumbnail ), $orig_images );
		}

		//var_dump($orig_images);

		$already_found_attachments = array();
		foreach ( $orig_images as $orig_image ) {
			$already_found_attachments[] = $orig_image->ID;
		}

		// 4. Getting those images that must be attachments in DB
		$images = get_posts(
			array(
				'post_type' => 'attachment',
				'numberposts' => -1,
				'orderby'        => 'title',
				'order'           => 'ASC',
				'post_mime_type' => 'image',
				'exclude' => $already_found_attachments,
				'include' => $attachments_ids
			)
		);

		foreach ( $images as $image ) {
			$already_found_attachments[] = $image->ID;
		}

		// Now we have here all the attachments data. We can start to upload, attach and replace in the post
		$images_as_attachments = array_merge( $orig_images, $images );		

		restore_current_blog();

		// Removing repeated attachments
		$new_images_as_attachments = array();
		$ids_arr = array();
		foreach ( $images_as_attachments as $attachment ) {
			if ( ! in_array( $attachment->ID, $ids_arr ) ) {
				$ids_arr[] = $attachment->ID;
				$new_images_as_attachments[] = $attachment;
			}
		}

		$images_as_attachments = $new_images_as_attachments;

		return array(
			'attachments' => $images_as_attachments,
			'no_attachments' => $images_no_attachments
		);

		
	}

	public function copy_media( $post_id, $new_post_id ) {

		$all_media = $this->get_all_media_in_post( $post_id );

		$images_as_attachments = $all_media['attachments'];
		$images_as_no_attachments = $all_media['no_attachments'];

		switch_to_blog( $this->orig_blog_id );
		// Just adding some custom properties
		foreach ( $images_as_attachments as $key => $image ) {
			$dir = get_attached_file( $image->ID );

			// The path of the file
			$images_as_attachments[ $key ]->path = $dir;

			// Is the image a thumbnail?
			if ( empty( $image->is_thumbnail ) )
				$images_as_attachments[ $key ]->is_thumbnail = false;

			// Now the images metadata (sizes)
			$metadata = wp_get_attachment_metadata( $image->ID );
			$images_as_attachments[ $key ]->metadata = $metadata;

		}

		$orig_upload_dir = wp_upload_dir();

		$orig_upload_basedir = $orig_upload_dir['basedir'];
		$orig_upload_baseurl = $orig_upload_dir['baseurl'];
		restore_current_blog();

		//var_dump($images_as_attachments);
		// Now uploading the files
		$upload_dir = wp_upload_dir();

		$tmp_upload_dir = $upload_dir['basedir'];

		// We'll need to change the images URLs in the post content
		$new_post = get_post( $new_post_id );
		$new_post_content = $new_post->post_content;

		foreach ( $images_as_attachments as $image ) {

			$info = pathinfo( $image->path );
			$file_name =  $info['basename'];

			$new_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
			$new_file = $upload_dir['path'] . "/$new_file_name";


			if ( @copy( $image->path, $new_file ) ) {

				// Set correct file permissions
				$stat = stat( dirname( $new_file ));
				$perms = $stat['mode'] & 0000666;
				@ chmod( $new_file, $perms );

				// Compute the URL
				$url = $upload_dir['url'] . "/$new_file_name";

				if ( is_multisite() )
					delete_transient( 'dirsize_cache' );

				$results = array( 'file' => $new_file, 'url' => $url );

				$wp_filetype = wp_check_filetype( basename( $new_file ), null );

				// Inserting new attachment
				$attachment = array(
					'guid' => $upload_dir['url'] . '/' . basename( $new_file ), 
					'post_mime_type' => $wp_filetype['type'],
					'post_title' => $image->post_title,
					'post_content' => '',
					'post_status' => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment, $new_file, $new_post_id );

				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generating metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $new_file );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				// If the image is a thumbnail we'll need to update the post meta
				if ( $image->is_thumbnail ) {
					update_post_meta( $new_post_id, '_thumbnail_id', $attach_id );
				}
				else {
					
					// First we try with the plain file
					$new_post_content = str_replace( $image->guid, $attachment['guid'], $new_post_content );
					
					// Now with the other sizes
					if ( ! empty( $attach_data['sizes'] ) ) {
						foreach ( $attach_data['sizes'] as $key => $attach_size ) {
							if ( isset( $image->metadata['sizes'][ $key ] ) ) {
								$old_url = dirname( $image->guid ) . '/' . $image->metadata['sizes'][ $key ]['file'];
								$new_post_content = str_replace( $old_url, dirname( $attachment['guid'] ) . '/' . $attach_size['file'], $new_post_content );
							}
						}
					}

					
				}
				
			}
		}
		var_dump($images_as_no_attachments);
		foreach ( $images_as_no_attachments as $image ) {

			// Source dirs info
			$orig_file = $orig_upload_basedir . '/' . dirname( $image['orig_upload_file'] ) . '/' . basename( $image['orig_src'] );
			$orig_base_file = $orig_upload_basedir . '/' . $image['orig_upload_file'];

			// Source src info
			$orig_url_file = $orig_upload_baseurl . '/' . dirname( $image['orig_upload_file'] ) . '/' . basename( $image['orig_src'] );
			$orig_url_base_file = $orig_upload_baseurl . '/' . $image['orig_upload_file'];

			// New filenames
			$new_file_name = basename( $image['orig_src'] );
			$new_base_file_name = basename( $image['orig_upload_file'] );

			// Destination dirs info
			$dest_file = $upload_dir['path'] . '/' . $new_file_name;
			$dest_base_file = $upload_dir['path'] . '/' . $new_base_file_name;

			// Destination src info
			$dest_url_file = $upload_dir['baseurl'] . '/' . dirname( $image['orig_upload_file'] ) . '/' . basename( $image['orig_src'] );
			$dest_url_base_file = $upload_dir['baseurl'] . '/' . $image['orig_upload_file'];

			// Copying the file with width and height in its name
			if ( @copy( $orig_file, $dest_file ) ) {
				$new_post_content = str_replace( $orig_url_file, $dest_url_file, $new_post_content );
			}

			// Copying the base file
			if ( @copy( $orig_base_file, $dest_base_file ) ) {
				$new_post_content = str_replace( $orig_url_base_file, $dest_url_base_file, $new_post_content );
			}

		}

		$new_post->post_content = $new_post_content;

		// Updating the post
		wp_insert_post( $new_post );

	}

	protected function get_orig_blog_post( $post_id ) {
		switch_to_blog( $this->orig_blog_id );

		// Get the source post
		$post = get_post( $post_id );

		restore_current_blog();

		return $post;
	}

	protected function get_orig_blog_post_meta( $post_id ) {

		switch_to_blog( $this->orig_blog_id );

		// Get the source postmeta
		$model = mcc_get_copier_model();
		$post_meta = $model->get_post_meta( $post_id );

		restore_current_blog();

		return $post_meta;

	}

	protected function get_orig_blog_post_terms( $post_id ) {

		switch_to_blog( $this->orig_blog_id );

		$post_terms = wp_get_object_terms( $post_id, array( 'category', 'post_tag' ), array( 'fields' => 'all' ) );

		restore_current_blog();
	}

	protected function get_postarr( $post_object ) {
		return array(
			'menu_order' 				=> $post_object->menu_order,
			'comment_status' 			=> $post_object->comment_status,
			'ping_status' 				=> $post_object->ping_status,
			'post_author' 				=> $post_object->post_author, // Copy author?
			'post_date' 				=> $post_object->post_date, // Update dates?
			'post_date_gmt' 			=> $post_object->post_date_gmt, // Update dates?
			'post_content' 				=> $post_object->post_content,
			'post_title' 				=> $post_object->post_title,
			'post_excerpt' 				=> $post_object->post_excerpt,
			'post_status' 				=> $post_object->post_status,
			'post_password' 			=> $post_object->post_password,
			'to_ping' 					=> $post_object->to_ping,
			'pinged' 					=> $post_object->pinged,
			'post_modified' 			=> $post_object->post_modified, // Update dates?
			'post_modified_gmt' 		=> $post_object->post_modified_gmt, // Update dates?
			'post_content_filtered' 	=> $post_object->post_content_filtered,
			'post_parent' 				=> 0, // Copy parents ?
			'post_type' 				=> $post_object->post_type,
			'post_mime_type' 			=> $post_object->post_mime_type,
			'comment_count' 			=> 0, // Copy comments ?
			'filter' 					=> $post_object->filter,
			'format_content' 			=> $post_object->format_content,
		);
	}

}

interface Multisite_Content_Copier_Post {
	public function copy_post( $post_id );
}

