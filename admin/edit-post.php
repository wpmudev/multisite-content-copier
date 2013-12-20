<?php

class MCC_Post_Meta_Box {

	public function __construct() {
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'transition_post_status', array( &$this, 'maybe_sync_post' ), 10, 3 );
	}

	public function enqueue_scripts( $hook ) {
		if ( $hook == 'post.php' && is_super_admin() ) {
			wp_enqueue_script( 'mcc-meta-box', MULTISTE_CC_ASSETS_URL . 'js/meta-box.js', array( 'jquery' ) );

			$object = array(
				'select_an_option' => __( 'You must select a destination', MULTISTE_CC_LANG_DOMAIN ),
				'select_a_group' => __( 'You must select a group', MULTISTE_CC_LANG_DOMAIN )
			);
			wp_localize_script( 'mcc-meta-box', 'mcc_meta_texts', $object );
		}
	}

	public function add_meta_boxes( $post_type, $post ) {

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
		        __( 'Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ),
		        array( &$this, 'render_copier_meta_box' ),
		        $post_type,
		        'normal',
		        'default'
		    );
		}
	}

	public function render_copier_meta_box( $post ) {

		$model = mcc_get_model();
		$synced = get_post_meta( $post->ID, 'post_synced', false );

		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'pending' ) ) ) {
			echo '<p>' . __( 'Please save this post if you would like to copy it.', MULTISTE_CC_LANG_DOMAIN ) . '</p>';

			if ( ! $synced ) {
				?>
					<h4><?php _e( 'Sync content', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
					<p><?php _e( 'Check this box if you\'d like to sync this content. Every time this post is updated, changes will be updated in selected blogs.' ); ?></p>
					<label><input type="checkbox" name="mcc-sync-content" /> <?php _e( 'Sync this post', MULTISTE_CC_LANG_DOMAIN ); ?></label>
				<?php
			}
		}
		else {
			
			?>
				<p><input type="checkbox" name="mcc-sync-content" disabled <?php checked( $synced ); ?> /> <?php _e( 'Post synced', MULTISTE_CC_LANG_DOMAIN ); ?></p>
			<?php
			$settings = array(
				'post_ids' => array( $post->ID ),
				'class' => 'Multisite_Content_Copier_Post_Copier'
			);
			?>
				<h4><?php _e( 'Select destinations', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
				<?php if ( get_post_meta( $post->ID, 'mcc_copied' ) ): ?>
					<p><?php _e( 'You have already copied this post, copying it again could cause duplicated posts', MULTISTE_CC_LANG_DOMAIN ); ?></p>
				<?php endif; ?>
				<div style="margin-left:20px;">
					<p>
						<label>
							<input type="radio" name="mcc_dest_blog_type" value="all"> 
							<?php _e( 'All sites', MULTISTE_CC_LANG_DOMAIN ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="radio" name="mcc_dest_blog_type" value="group">
							<?php _e( 'Site group', MULTISTE_CC_LANG_DOMAIN ); ?>
						</label>
						<select name="mcc_group" id="mcc_group" >
							<?php mcc_get_groups_dropdown(); ?>
						</select>
					</p>
					<?php $settings = mcc_get_settings(); ?>
					<?php if ( $settings['blog_templates_integration'] ): ?>
						<p>
							<label>
								<input type="radio" name="mcc_dest_blog_type" value="nbt_group">
								<?php _e( 'Select by Blog Templates groups', MULTISTE_CC_LANG_DOMAIN ); ?>
							</label>
							<select name="mcc_nbt_group" id="mcc_nbt_group">
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

	public function maybe_update_sync_content( $post_ID, $post_after, $post_before ) {

	}

	public function maybe_sync_post( $new_status, $old_status, $post ) {
		if ( 'publish' == $new_status && $old_status != $new_status ) {
			$post_id = $post->ID;

			if ( ! empty( $_POST['mcc-sync-content'] ) && is_super_admin() ) {
				$settings = array();
				
				$action = in_array( get_post_type( $post ), array( 'post', 'page' ) ) ? 'add-' . get_post_type( $post ) : 'add-cpt';
				$class = mcc_get_action_copier_class( $action );

				$settings['class'] = $class;
				$settings['sync'] = true;
				$settings['post_ids'] = array( $post->ID );

				$src_blog_id = get_current_blog_id();

				global $wpdb,$current_site;

				$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

				$dest_blogs_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT blog_id FROM $wpdb->blogs 
						WHERE site_id = %d
						AND blog_id != %d
						ORDER BY blog_id",
						$current_site_id,
						get_current_blog_id()
					)
				);

				$model = mcc_get_model();
				foreach ( $dest_blogs_ids as $dest_blog_id ) {
					// Inserting a queue item for each blog
					if ( $dest_blog_id != $src_blog_id && ! is_main_site( $dest_blog_id ) ) {
						$model->insert_queue_item( $src_blog_id, $dest_blog_id, $settings );
					}	
				}

				update_post_meta( $post_id, 'post_synced', true );

			}
			
		}
	}



}