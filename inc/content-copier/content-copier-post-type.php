<?php

/**
 * Parent class for CPTs, post and pages
 * @package default
 */
class Multisite_Content_Copier_Post_Type_Copier extends Multisite_Content_Copier_Abstract {

	/**
	 * Saves posts copied data:
	 * Array(
	 * 		source_post_ID => Array(
	 * 			new_post_ID => Integer
	 * 			new_post_parent_ID => Integer/false
	 * 		)
	 * )
	 */
	private $posts_created = array();

	/**
	 * Saves source parents relationships
	 * Useful to remap later the posts
	 */
	private $parents_mapping = array();

	// Saves the post parents IDs that we are copying
	// In order to not copy them twice
	private $copied_parents_ids = array();

	public function get_default_args() {
		return array(
			'copy_images' => false,
			'keep_user' => true,
			'update_date' => false,
			'copy_parents' => false,
			'copy_comments' => false,
			//'updating' => false,
			//'sync' => false
		);
	}

	public function execute() {

		if ( $this->args['copy_parents'] )
			$this->add_parents_to_items_list();

		foreach( $this->items as $item_id ) {

			$this->log( "-- COPY ITEM: " . $item_id );
			$post_created = $this->copy_item( $item_id );

			$this->posts_created[ $item_id ] = $post_created;
			update_post_meta( $item_id, 'mcc_copied', true, $this->posts_created[ $item_id ] );
		}

		$this->remap_parents();

		/**
		 * Fired after posts are copied in destination blog
		 * 
		 * @param Array $posts_created {
		 * 		List of Posts that have been copied with the following relationship:
		 * 		
		 * 		source_post_id_1 => new_post_id_1,
		 * 		source_post_id_2 => new_post_id_2,
		 * 		...
		 * }
		 * @param Integer $orig_blog_id Source blog ID
		 */
		do_action( 'mcc_copy_posts', $this->posts_created, $this->orig_blog_id, $this->args );

		return $this->posts_created;
	}

	/**
	 * Add source posts parents to the list to copy
	 * 
	 */
	public function add_parents_to_items_list() {

		switch_to_blog( $this->orig_blog_id );
		$this->parents_mapping = array();

		foreach ( $this->items as $item_id ) {
			$cursor = $item_id;
			$finished = false;
			while( ! $finished ) {
				$parent_post_id = false;
				$parent_post_id = wp_get_post_parent_id( $cursor );

				if ( empty( $parent_post_id ) ) {
					$finished = true;
				}
				else {
					$this->parents_mapping[ $cursor ] = $parent_post_id;
					$cursor = $parent_post_id;
				}
			}
		}

		restore_current_blog();

		$this->items = array_merge( $this->parents_mapping, $this->items );
		$this->items = array_unique( $this->items );
	}

	/**
	 * Remap the source parents posts to the newly created
	 * 
	 */
	public function remap_parents() {
		foreach ( $this->posts_created as $source_post_id => $new_post_id ) {
			if ( ! empty( $this->parents_mapping[ $source_post_id ] ) && ! empty( $this->posts_created[ $this->parents_mapping[ $source_post_id ] ] ) ) {
				$args = array(
					'ID' => $new_post_id,
					'post_parent' => $this->posts_created[ $this->parents_mapping[ $source_post_id ] ]
				);
				wp_update_post( $args );
			}
		}
	}


	/**
	 * This need to be overriden by subclasses
	 * 
	 * @param type $item_id 
	 * @return type
	 */
	public function copy_item( $item_id ) {
		
		// We need to remove these filters
		// WP could remove iframes, for example
		// but we have to trust in the source post content
		// in this case
		remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		$source_post_id = $item_id;
		$source_blog_id = $this->orig_blog_id;

		/**
		 * Triggered before a post is copied
		 * 
		 * @param Integer $source_blog_id Source Blog ID
		 * @param Integer $source_post_id Source Post ID
		 */
		do_action( 'mcc_before_copy_post', $source_blog_id, $source_post_id );

		$new_item_id = $this->copy_post( $item_id );
		
		if ( ! $new_item_id )
			return false;

		if ( $this->args['copy_images'] )
			$this->copy_media( $item_id, $new_item_id );

		if ( $this->args['update_date'] )
			$this->update_post_date( $new_item_id, current_time( 'mysql' ) );


		if ( $this->args['copy_comments'] )
			$this->copy_comments( $item_id, $new_item_id );


		add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		return $new_item_id;
	}

	/**
	 * Copy a single post
	 * 
	 * Copy just the post and postmeta, nothing else, no terms or images
	 * 
	 * @param type $post_id 
	 * @return type
	 */
	public function copy_post( $item_id ) {

		// Getting original post data
		$orig_post = $this->get_orig_blog_post( $item_id );

		$this->log( "SOURCE POST: " );
		$this->log( $orig_post );
		
		if ( empty( $orig_post ) ) {
			return false;
		}

		$orig_post_meta = $this->get_orig_blog_post_meta( $item_id );

		// Insert post in the new blog ( we should be currently on it)
		$postarr = $this->get_postarr( $orig_post );

		$new_item_id = wp_insert_post( $postarr, true );

		$this->log( "POST INSERTED, RESULT: " );
		$this->log( $new_item_id );

		if ( ! is_wp_error( $new_item_id ) ) {

			/**
			 * Triggered when a single post/page/CPT has been copied
			 * 
			 * @param Integer $source_blog_id Source Blog ID where the post is
			 * @param Integer $item_id Post ID in the source blog
			 * @param Integer $new_item_id Id of the post that has just been copied
			 */
			do_action( 'mcc_copy_post', $this->orig_blog_id, $item_id, $new_item_id );


			// Insert post meta
			foreach ( $orig_post_meta as $post_meta ) {
				$unserialized_meta_value = maybe_unserialize( $post_meta->meta_value );
				update_post_meta( $new_item_id, $post_meta->meta_key, $unserialized_meta_value );

				/**
				 * Triggered when a single post/page/CPT Metadata has been copied
				 * 
				 * @param Integer $source_blog_id Source Blog ID where the post is
				 * @param Integer $item_id Post ID in the source blog
				 * @param Integer $new_item_id Id of the post that has just been copied
				 * @param Integer $meta_key Metadata slug
				 * @param Integer $meta_value Unserialized Metadata value
				 */
				do_action( 'mcc_copy_post_meta', $this->orig_blog_id, $item_id, $new_item_id, $post_meta->meta_key, $unserialized_meta_value );
			}			

			// Set post format
			switch_to_blog( $this->orig_blog_id );
			$post_format = get_post_format( $item_id );
			restore_current_blog();

			if ( $post_format === false )
				$post_format = '';
			set_post_format( $new_item_id, $post_format );
		}

		return $new_item_id;
		
	}

	public function get_all_media_in_post( $item_id ) {
		switch_to_blog( $this->orig_blog_id );
		$orig_post = get_post( $item_id );

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

		$images = array();
		$attachments_ids = array();
		if ( ! empty( $matches[1] ) ) {
			
			$model = mcc_get_copier_model();
			
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

				// No we need to know the item_ids of the attachments
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

		$gallery_attachments_ids = $this->parse_gallery_attachments( $orig_post->post_content );
		$attachments_ids = array_merge( $attachments_ids, $gallery_attachments_ids );
		$attachments = array_map( 'get_post', $attachments_ids );

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


		$already_found_attachments = array();
		foreach ( $orig_images as $orig_image ) {
			$already_found_attachments[] = $orig_image->ID;
		}

		// 4. Getting those images that must be attachments in DB
		$images = array();
		if ( ! empty( $attachments_ids ) ) {
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
		}

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

	/**
	 * Get the attachments IDs inside a gallery shortcode if it exists in the post content
	 */
	public function parse_gallery_attachments( $post_content ) {
		$pattern = $this->get_gallery_pattern();
		preg_match_all( "/$pattern/s", $post_content, $matches );

		if ( isset( $matches[3][0] ) )
			$attr = shortcode_parse_atts( $matches[3][0] );

		$ids = array();
		if ( isset( $attr['ids'] ) )
			$ids = array_map( 'absint', explode( ',', $attr['ids'] ) );

		return $ids;
	}

	public function get_gallery_pattern() {
		return '\[(\[?)(gallery)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';
	}

	

	/**
	 * Copy images to the new blog
	 * 
	 * @param Integer $post_id Source Post ID
	 * @param Integer $new_post_id Destination Post ID
	 * @return type
	 */
	public function copy_media( $post_id, $new_post_id ) {

		// Get all media in the post
		$all_media = $this->get_all_media_in_post( $post_id );
		$orig_post = get_blog_post( $this->orig_blog_id, $post_id );
		$new_post = get_post( $new_post_id );
		$new_post_content = $new_post->post_content;

		/**
		 * Filter the media extracted from the source Post
		 *
		 * @param Array $all_media Images found in the post. They are 
		 * splitted in attachments (those who are attached to the source post 
		 * and also those that are not attached but are in the upload folder
		 * and have been found without width and height in the filename)
		 * and no attachments (rest of the images)
		 * 
		 * @param Integer $post_id Source Post ID
		 */
		$all_media = apply_filters( 'mcc_copy_media', $all_media, $post_id, $new_post_id );

		$images_as_attachments = $all_media['attachments'];
		$images_as_no_attachments = $all_media['no_attachments'];

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		foreach ( $images_as_attachments as $key => $image ) {

			switch_to_blog( $this->orig_blog_id );
			$image_alt_text = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
			$metadata = wp_get_attachment_metadata( $image->ID );

			// Is the image a thumbnail?
			$is_thumbnail = false;
			if ( ! empty( $image->is_thumbnail ) )
				$is_thumbnail = true;
			restore_current_blog();

			$new_attachment = (array)$image;
            unset( $new_attachment['ID'] );
            unset( $new_attachment['guid'] );

			$upload = $this->fetch_remote_file( $image->guid );

			if ( is_wp_error( $upload ) ) {
                continue;
			}

            if ( $info = wp_check_filetype( $upload['file'] ) ) {
                $new_attachment['post_mime_type'] = $info['type'];
            }
            else {
                continue;
            }

            $new_attachment['post_parent'] = $new_post_id;

            $new_attachment['guid'] = $upload['url'];

            // Generate the new attachment
            $new_attachment_id = wp_insert_attachment( $new_attachment, $upload['file'] );
            wp_update_attachment_metadata( $new_attachment_id, wp_generate_attachment_metadata( $new_attachment_id, $upload['file'] ) );

            // Update alt text
            update_post_meta( $new_attachment_id, '_wp_attachment_image_alt', $image_alt_text );

            if ( $is_thumbnail )
            	set_post_thumbnail( $new_post_id, $new_attachment_id );

			do_action( 'mcc_copy_attachment', $new_attachment_id, $image->ID, $new_post_id, $post_id );

            // First we try with the plain file
			$new_post_content = str_replace( $image->guid, $new_attachment['guid'], $new_post_content );

			$parts = pathinfo( $image->guid );
			$name = basename( $parts['basename'], ".{$parts['extension']}" );
			$parts_new = pathinfo( $upload['url'] );
			$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

			$from_url = $parts['dirname'] . '/' . $name;
 			$to_url = $parts_new['dirname'] . '/' . $name_new;

			// Now with the other sizes
			$new_post_content = str_replace( $from_url, $to_url, $new_post_content );

			// If the image was inside a gallery shortcode, replace the ID inside the shortcode
			$pattern = $this->get_gallery_pattern();
			
			preg_match_all( "/$pattern/s", $new_post_content, $matches );

			if ( ! empty( $matches[3][0] ) ) {
				$attr = shortcode_parse_atts( $matches[3][0] );
				$new_id = $new_attachment_id;
				$old_id = $image->ID;
				$gallery_ids = $this->parse_gallery_attachments( $new_post_content );

				$key = array_search( $old_id, $gallery_ids );
				if ( $key !== false ) {
					$gallery_ids[ $key ] = $new_id;
					$ids_string = ' ids="' . implode( ',', $gallery_ids ) . '"';
					$new_post_content = str_replace( $matches[3][0], $ids_string, $new_post_content );
				}
			}
					
		}


		foreach ( $images_as_no_attachments as $image ) {

			$new_attachment = array(
                'post_status' => 'publish'
            );


            $upload = $this->fetch_remote_file( $image['orig_src'] );

            if ( is_wp_error( $upload ) ) {
                continue;
            }

            if ( $info = wp_check_filetype( $upload['file'] ) )
                $new_attachment['post_mime_type'] = $info['type'];
            else
                continue;

            $new_attachment['post_parent'] = $new_post_id;

            $new_attachment['guid'] = $upload['url'];

            // Generate the new attachment
            $new_attachment_id = wp_insert_attachment( $new_attachment, $upload['file'] );
            wp_update_attachment_metadata( $new_attachment_id, wp_generate_attachment_metadata( $new_attachment_id, $upload['file'] ) );

            // First we try with the plain file
			$new_post_content = str_replace( $image['orig_src'], $new_attachment['guid'], $new_post_content );

			$parts = pathinfo( $image['orig_src'] );
			$name = basename( $parts['basename'], ".{$parts['extension']}" );
			$parts_new = pathinfo( $upload['url'] );
			$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

			$from_url = $parts['dirname'] . '/' . $name;
 			$to_url = $parts_new['dirname'] . '/' . $name_new;

			// Now with the other sizes
			$new_post_content = str_replace( $from_url, $to_url, $new_post_content );

		}

		$new_post->post_content = $new_post_content;

		// Updating the post
		$post_id = wp_insert_post( $new_post );

	}

	/**
     * Fetch an image and download it. Then create a new empty file for  it
     * that can be filled later
     * 
     * Code based on WordPress Importer plugin
     * 
     * @return WP_Error/Array Image properties/Error
     */
    public function fetch_remote_file( $url )  {
        $file_name = basename( $url );

        $upload = wp_upload_bits( $file_name, null, 0 );

        if ( $upload['error'] )
            return new WP_Error( 'upload_dir_error', $upload['error'] );

        $headers = wp_get_http( $url, $upload['file'] );

        // request failed
        if ( ! $headers ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __('Remote server did not respond for file: %s', MULTISTE_CC_LANG_DOMAIN ), $url ) );
        }

        // make sure the fetch was successful
        if ( $headers['response'] != '200' ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s', MULTISTE_CC_LANG_DOMAIN ), esc_html($headers['response']), get_status_header_desc($headers['response']) ) );
        }

        $filesize = filesize( $upload['file'] );

        if ( 0 == $filesize ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Zero size file downloaded', MULTISTE_CC_LANG_DOMAIN ) );
        }

        $max_size = (int) apply_filters( 'mcc_attachment_size_limit', 0 );
        if ( ! empty( $max_size ) && $filesize > $max_size ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf(__('Remote file is too large, limit is %s', MULTISTE_CC_LANG_DOMAIN ), size_format($max_size) ) );
        }

        return $upload;
    }

	/**
	 * When switching between blogs wp_upload_dir()
	 * may return an incorrect URL for images
	 * This code is directly copied from WP core but with minor changes
	 * 
	 * @return Array
	 */
	public function set_correct_upload_url( $upload_dir ) {
		$siteurl = trailingslashit( get_option( 'siteurl' ) );
		$upload_path = trim( get_option( 'upload_path' ) );

		if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $upload_path );
		} else {
			$dir = $upload_path;
		}

		if ( ! $url = get_option( 'upload_url_path' ) ) {
			if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) ) {

				$url = $siteurl . 'wp-content/uploads';
				if ( ! ( is_main_site() && defined( 'MULTISITE' ) ) ) {
					if ( ! get_site_option( 'ms_files_rewriting' ) ) {
						// If ms-files rewriting is disabled (networks created post-3.5), it is fairly straightforward:
						// Append sites/%d if we're not on the main site (for post-MU networks). (The extra directory
						// prevents a four-digit ID from conflicting with a year-based directory for the main site.
						// But if a MU-era network has disabled ms-files rewriting manually, they don't need the extra
						// directory, as they never had wp-content/uploads for the main site.)

						if ( defined( 'MULTISITE' ) )
							$ms_dir = '/sites/' . get_current_blog_id();
						else
							$ms_dir = '/' . get_current_blog_id();

						$dir .= $ms_dir;
						$url .= $ms_dir;

					} elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
						// Handle the old-form ms-files.php rewriting if the network still has that enabled.
						// When ms-files rewriting is enabled, then we only listen to UPLOADS when:
						//   1) we are not on the main site in a post-MU network,
						//      as wp-content/uploads is used there, and
						//   2) we are not switched, as ms_upload_constants() hardcodes
						//      these constants to reflect the original blog ID.
						//
						// Rather than UPLOADS, we actually use BLOGUPLOADDIR if it is set, as it is absolute.
						// (And it will be set, see ms_upload_constants().) Otherwise, UPLOADS can be used, as
						// as it is relative to ABSPATH. For the final piece: when UPLOADS is used with ms-files
						// rewriting in multisite, the resulting URL is /files. (#WP22702 for background.)

						if ( defined( 'BLOGUPLOADDIR' ) )
							$dir = untrailingslashit( BLOGUPLOADDIR );
						else
							$dir = ABSPATH . UPLOADS;
						$url = trailingslashit( $siteurl ) . 'files';
					}
				}

				$baseurl = $url;

				$subdir = '';
				if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
					// Generate the yearly and monthly dirs
					$time = current_time( 'mysql' );
					$y = substr( $time, 0, 4 );
					$m = substr( $time, 5, 2 );
					$subdir = "/$y/$m";
				}

				$url .= $subdir;

				$upload_dir['url'] = $url;
				$upload_dir['baseurl'] = $baseurl;
			}
				
		}

		return $upload_dir;
		
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
			'post_name' 				=> $post_object->post_name,
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

	public function get_orig_post_parent( $orig_post_id ) {
		$orig_post = get_blog_post( $this->orig_blog_id, $orig_post_id );
		return $orig_post->post_parent;
	}

	public function update_dest_post_parent( $dest_post_id, $dest_parent_item_id ) {
		wp_update_post( array( 'ID' => $dest_post_id, 'post_parent' => $dest_parent_item_id ) );
	}

	public function update_post_date( $item_id, $date ) {
		/**
		 * Filters the date when the destination post was created
		 * 
		 * @param String $date 
		 * @param Integer $item_id Destination Post ID 
		 */
		$date = apply_filters( 'mcc_update_copied_post_date', $date, $item_id );
		wp_update_post( array( 'ID' => absint( $item_id ), 'post_date' => $date ) );
	}

	public function copy_comments( $item_id, $new_item_id ) {
		switch_to_blog( $this->orig_blog_id );
		// Source comments
		$orig_comments = get_comments(
			array(
				'post_id' => $item_id
			)
		);

		restore_current_blog();

		$parent_children_rels = array();
		$new_comments = array();
		foreach( $orig_comments as $orig_comment ) {
			$_orig_comment = $orig_comment;

			// The source comment ID
			$orig_comment_ID = $_orig_comment->comment_ID;

			$_orig_comment->comment_post_ID = $new_item_id;
			$_orig_comment = (array)$_orig_comment;

			// We don't need the source ID for the new comment
			unset( $_orig_comment['comment_ID'] );

			$new_comment_ID = wp_insert_comment( $_orig_comment );

			// Saving the comment data for the next loop
			$new_comments[ $new_comment_ID ] = get_comment( $new_comment_ID );

			// Parent-child relatioship
			$parent_children_rels[ $orig_comment_ID ] = $new_comment_ID; 

			/**
			 * Triggered when a new comment has been copied
			 * 
			 * @param Integer $new_comment_ID
			 */
			do_action( 'mcc_copied_comment', $new_comment_ID );

			// Now the comment meta
			global $wpdb;

			switch_to_blog( $this->orig_blog_id );
			$comment_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->commentmeta WHERE comment_id = $orig_comment_ID" );
			restore_current_blog();

			if ( ! empty( $comment_meta ) ) {
				$insert_array = array();
				foreach ( $comment_meta as $metadata ) {
					$_metadata = array(
						'meta_key' => $metadata->meta_key,
						'meta_value' => $metadata->meta_value
					);

					/**
					 * Filters the comment metadata that will be attached to the
					 * comment created
					 * 
					 * @param Arra $_metadata
					 * @param Integer $new_comment_ID Destination Comment ID 
					 */
					$_metadata = apply_filters( 'mcc_copy_comment_meta', $_metadata, $new_comment_ID );
					update_comment_meta( $new_comment_ID, $_metadata['meta_key'], $_metadata['meta_value'] );
				}
			}

			/**
			 * Triggered when the comment metadata have been copied
			 * 
			 * @param Integer $new_comment_ID
			 */
			do_action( 'mcc_copied_comment_meta', $new_comment_ID );

			

		}

		// Setting the new parents correctly
		foreach ( $new_comments as $new_comment ) {
			if ( $new_comment->comment_parent > 0 ) {
				$_new_comment = $new_comment;
				$_new_comment->comment_parent = $parent_children_rels[ $new_comment->comment_parent ];
				$_new_comment = (array)$_new_comment;
				wp_update_comment( $_new_comment );

				/**
				 * Triggered when the relationship between
				 * parent/children comment are set
				 * 
				 * @param Integer $new_comment_ID
				 */
				do_action( 'mcc_set_comment_parent_children_rel', $_new_comment['comment_ID'] );
			}


		}


	}

	protected function get_orig_blog_post_terms( $item_id, $taxonomy ) {
		switch_to_blog( $this->orig_blog_id );
		$post_terms = wp_get_object_terms( $item_id, array( $taxonomy ), array( 'fields' => 'all' ) );
		restore_current_blog();

		return $post_terms;
	}

	protected function get_orig_blog_post_taxonomies( $item_id ) {
		switch_to_blog( $this->orig_blog_id );
		$post_taxonomies = get_object_taxonomies( get_post_type( $item_id ), 'names' );
		$post_format_key = array_search( 'post_format', $post_taxonomies );
		if ( false !==  $post_format_key ) {
			unset( $post_taxonomies[ $post_format_key ] );
		}
		restore_current_blog();

		return $post_taxonomies;
	}

	protected function copy_terms( $item_id, $new_item_id ) {

		// Categories
		$taxonomies = $this->get_orig_blog_post_taxonomies( $item_id );

		if ( empty( $taxonomies ) )
			return;
		
		foreach ( $taxonomies as $taxonomy ) {
			
			$terms = $this->get_orig_blog_post_terms( $item_id, $taxonomy );

			$term_ids = array();
			foreach ( $terms as $term ) {
				$term_name = $term->name;
				$term_description = $term->description;

				$destination_term = get_term_by( 'name', $term_name, $taxonomy, ARRAY_A );

				if ( ! $destination_term ) {
					$source_term_id = $term->term_id;
					$destination_term = wp_insert_term( $term_name, $taxonomy, array( 'description' => $term_description ) );

					$source_blog_id = $this->orig_blog_id;

					/**
					 * Triggered when a term has been copied
					 * 
					 * @param Integer $source_term_ID Source Term ID from the source blog
					 * @param Integer $destination_term destination Term ID in the destination blog
					 * @param Integer $source_blog_id Source Blog ID
					 */
					do_action( 'mcc_term_copied', $source_term_id, $destination_term, $source_blog_id );
				}

				if ( ! is_wp_error( $destination_term ) && ! empty( $destination_term['term_id'] ) )
					$term_ids[] = absint( $destination_term['term_id'] );
			}
			if ( ! empty( $term_ids ) )
				wp_set_object_terms( $new_item_id, $term_ids, $taxonomy );
		}


	}
}