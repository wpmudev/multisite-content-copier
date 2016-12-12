<?php

class MCC_Post_Meta_Box {

	public function __construct() {
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		//add_action( 'edit_form_top', array( &$this, 'maybe_sync_post' ) );
		//add_action( 'save_post', array( &$this, 'process_sync_form' ), 10, 3 );
	}

	public function enqueue_scripts( $hook ) {
		if ( $hook == 'post.php' && is_super_admin() ) {
			wp_enqueue_script( 'mcc-meta-box', MULTISTE_CC_ASSETS_URL . 'js/meta-box.js', array( 'jquery' ) );
			//wp_enqueue_script( 'mcc-meta-box-sync', MULTISTE_CC_ASSETS_URL . 'js/meta-box-sync.js', array( 'jquery' ) );

			$object = array(
				'select_an_option' => __( 'You must select a destination', MULTISTE_CC_LANG_DOMAIN ),
				'select_a_group' => __( 'You must select a group', MULTISTE_CC_LANG_DOMAIN )
			);
			wp_localize_script( 'mcc-meta-box', 'mcc_meta_texts', $object );
		}
	}

	public function add_meta_boxes( $post_type ) {

		if ( ! is_super_admin() )
			return;

		$post_types = array();
		$_post_types = mcc_get_registered_cpts();
		foreach ( $_post_types as $post_type )
			$post_types[] = $post_type->name;

		$post_types = array_merge( $post_types, array( 'post', 'page' ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box( 
		        'copier-meta-box',
		        __( 'Multisite Content Copier: Copy content', MULTISTE_CC_LANG_DOMAIN ),
		        array( &$this, 'render_copier_meta_box' ),
		        $post_type,
		        'normal',
		        'default'
		    );

		    //add_meta_box( 
		        //'syncer-meta-box',
		        //__( 'Multisite Content Copier: Sync post', MULTISTE_CC_LANG_DOMAIN ),
		        //array( &$this, 'render_syncer_meta_box' ),
		        //$post_type,
		        //'normal',
		        //'default'
//		    );
		}
	}

	public function render_copier_meta_box( $post ) {

		$model = mcc_get_model();
		

		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future' ) ) ) {
			echo '<p>' . __( 'Please save this post if you would like to copy it.', MULTISTE_CC_LANG_DOMAIN ) . '</p>';

		}
		else {

			?>
				<?php if ( get_post_meta( $post->ID, 'mcc_copied' ) ): ?>
					<p><?php _e( 'You have already copied this post, copying it again could cause duplicated posts', MULTISTE_CC_LANG_DOMAIN ); ?></p>
				<?php endif; ?>
				
				<?php $this->render_form_fields( 'mcc_' ); ?>

				
				<?php 
					$link = add_query_arg(
						array(
							'content_blog' => get_current_blog_id(),
							'post_id' => $post->ID,
							'mcc_action' => 'mcc_submit_metabox'
						),
						Multisite_Content_Copier::$network_main_menu_page->get_permalink()
					);
					$link = wp_nonce_url( $link, 'mcc_submit_meta_box' );
				?>
				<a id="mcc_copy_link" class="button-primary" href="<?php echo esc_url( $link ); ?>"><?php _e( 'Copy', MULTISTE_CC_LANG_DOMAIN ); ?></a>
			<?php 
		}
	}

	/**
	 * Render the meta box that handles the sync
	 * 
	 * @return type
	 */
	public function render_syncer_meta_box() {
		global $post;
		$synced = get_post_meta( $post->ID, 'post_synced', false );
		$model = mcc_get_model();
		

		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'pending' ) ) ) {
			echo '<p>' . __( 'Please save this post if you would like to sync it.', MULTISTE_CC_LANG_DOMAIN ) . '</p>';
		}
		else {

			$this->render_form_fields( 'mcc_sync_' );
		}

		wp_nonce_field( 'mcc_sync_post', '_mccnonce' );
		?>
			<input type="submit" class="button-primary" name="mcc_sync_submit" value="<?php _e( 'Sync content', MULTISTE_CC_LANG_DOMAIN ); ?>" />
		<?php

		//$this->render_sync_scripts();
	}

	private function render_form_fields( $prefix_slug ) {
		global $post;
		?>
			<h4><?php _e( 'Select destinations', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
			<div style="margin-left:20px;">
				<p>
					<label>
						<input type="radio" name="<?php echo $prefix_slug; ?>dest_blog_type" class="<?php echo $prefix_slug; ?>dest_blog_type" value="all"> 
						<?php _e( 'All sites', MULTISTE_CC_LANG_DOMAIN ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="<?php echo $prefix_slug; ?>dest_blog_type" class="<?php echo $prefix_slug; ?>dest_blog_type" value="group">
						<?php _e( 'Site group', MULTISTE_CC_LANG_DOMAIN ); ?>
					</label>
					<select name="<?php echo $prefix_slug; ?>blog_group" id="<?php echo $prefix_slug; ?>blog_group">
						<?php mcc_get_groups_dropdown(); ?>
					</select>
				</p>
				<?php $settings = mcc_get_settings(); ?>
				<?php if ( $settings['blog_templates_integration'] ): ?>
					<p>
						<label>
							<input type="radio" name="<?php echo $prefix_slug; ?>dest_blog_type" class="<?php echo $prefix_slug; ?>dest_blog_type" value="nbt-group">
							<?php _e( 'Blog Templates Group', MULTISTE_CC_LANG_DOMAIN ); ?>
						</label>
						<select name="<?php echo $prefix_slug; ?>nbt_blog_group" id="<?php echo $prefix_slug; ?>nbt_blog_group">
							<?php mcc_get_nbt_groups_dropdown(); ?>
						</select>
					</p>
				<?php endif; ?>
			</div>

			<h4><?php _e( 'Additional Options', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
			<?php
				switch ( $post->post_type ) {
					case 'post':
						$options =  mcc_get_post_additional_settings();
						break;
					case 'page':
						$options =  mcc_get_page_additional_settings();
						break;						
					default:
						$options =  mcc_get_cpt_additional_settings();
						break;
				}
			?>
			<ul style="margin-left:20px;">
				<?php foreach ( $options as $option_slug => $label ): ?>
					<li><label><input type="checkbox" class="mcc_options" name="<?php echo $option_slug; ?>" value="<?php echo $option_slug; ?>"></input> <?php echo $label; ?></label></li>
				<?php endforeach; ?>
			</ul>
		<?php
	}

	public function maybe_update_sync_content( $post_ID, $post_after, $post_before ) {

	}

	public function maybe_sync_post() {
		//if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'true' ) {
			//$destination = $_GET['d'];
			//$group = isset( $_GET['g'] ) ? absint( $_GET['g'] ) : 0;
//
			//if ( 'all' == $destination ) {
				//wp_update_network_counts();
				//$blogs_count = get_blog_count();
				//var_dump($blogs_count);
			//}
		//}
	}

	/**
	 * Establish relationships between this post and
	 * the rest of the blogs in order to sync content
	 * 
	 * @param type $new_status 
	 * @param type $old_status 
	 * @param type $post 
	 * @return type
	 */
	public function process_sync_form() {
		global $post;

		if ( ! empty( $_POST['mcc_sync_submit'] ) ) {
			check_admin_referer( 'mcc_sync_post', '_mccnonce' );

			$errors = array();

			if ( empty( $_POST['mcc_sync_dest_blog_type'] ) || ! in_array( $_POST['mcc_sync_dest_blog_type'], array( 'all', 'group', 'nbt-group' ) ) ) {
				$errors[] = new WP_Error( 'sync_destination', __( 'You must select a destination', MULTISTE_CC_LANG_DOMAIN ) );
			}

			if ( 'group' == $destination ) {
				if ( empty( $_POST['mcc_sync_blog_group'] ) ) {
					$errors[] = new WP_Error( 'sync_blog_group', __( 'You must select a group', MULTISTE_CC_LANG_DOMAIN ) );
				}
			}

			if ( 'nbt-group' == $destination ) {
				if ( empty( $_POST['mcc_sync_nbt_blog_group'] ) ) {
					$errors[] = new WP_Error( 'sync_blog_group', __( 'You must select a group', MULTISTE_CC_LANG_DOMAIN ) );
				}
			}

			if ( ! empty( $errors ) ) {
				ob_start();
				foreach ( $errors as $error ) {
					?>
						<p><?php echo $error->get_error_message(); ?></p>
					<?php
				}
				wp_die( ob_get_clean() );
			}

			else {
				add_filter( 'redirect_post_location', array( &$this, 'redirect_post_location' ), 10, 2 );
			}

		}
		

	}

	public function redirect_post_location( $location, $post_id ) {
		$destination = $_POST['mcc_sync_dest_blog_type'];

		$group = 0;
		if ( 'group' == $destination ) {
			$group = absint( $_POST['mcc_sync_blog_group'] );
		}

		if ( 'nbt-group' == $destination ) {
			$group = absint( $_POST['mcc_sync_nbt_blog_group'] );
		}

		$location = add_query_arg(
			array(
				'sync' => 'true',
				'd' => $destination,
				'g' => $group
			),
			$location
		);

		return $location;
	}



}