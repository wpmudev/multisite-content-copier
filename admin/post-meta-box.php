<?php

class MCC_Post_Meta_Box {

	public function __construct() {
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts( $hook ) {
		if ( $hook == 'post.php' && is_super_admin() ) {
			wp_enqueue_script( 'mcc-meta-box', MULTISTE_CC_ASSETS_URL . 'js/meta-box.js', array( 'jquery' ) );

			$object = array(
				'select_an_option' => __( 'You must select a destination blog', MULTISTE_CC_LANG_DOMAIN ),
				'select_a_group' => __( 'You must select a group of blogs', MULTISTE_CC_LANG_DOMAIN )
			);
			wp_localize_script( 'mcc-meta-box', 'mcc_meta_texts', $object );
		}
	}

	public function add_meta_box( $post_type, $post ) {

		if ( $post->post_status != 'publish' || ! in_array( $post->post_type, array( 'post', 'page' ) ) || ! is_super_admin() )
			return;

		add_meta_box( 
	        'copier-meta-box',
	        __( 'Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ),
	        array( &$this, 'render_copier_meta_box' ),
	        'post',
	        'normal',
	        'default'
	    );
	}

	public function render_copier_meta_box( $post ) {
		$settings = array(
			'post_ids' => array( $post->ID ),
			'class' => 'Multisite_Content_Copier_Post_Copier'
		);
		?>
			<h4><?php _e( 'Select the destination blogs', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
			<?php if ( get_post_meta( $post->ID, 'mcc_copied' ) ): ?>
				<p><?php _e( 'You have already copied this post, copying it again could cause duplicated posts', MULTISTE_CC_LANG_DOMAIN ); ?></p>
			<?php endif; ?>
			<p>
				<label>
					<input type="radio" name="mcc_dest_blog_type" value="all"> 
					<?php _e( 'Copy to all blogs', MULTISTE_CC_LANG_DOMAIN ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="mcc_dest_blog_type" value="group">
					<?php _e( 'Select by blogs group', MULTISTE_CC_LANG_DOMAIN ); ?>
				</label>
				<select name="mcc_group" id="mcc_group" >
					<?php mcc_get_groups_dropdown(); ?>
				</select>
			</p>
			<h4><?php _e( 'Additional options', MULTISTE_CC_LANG_DOMAIN ); ?></h4>
			<?php $options =  mcc_get_post_additional_settings(); ?>
			<ul>
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
			<a id="mcc_copy_link" class="button-primary" href="<?php echo esc_url( $link ); ?>"><?php _e( 'Copy!', MULTISTE_CC_LANG_DOMAIN ); ?></a>
		<?php 
	}


}