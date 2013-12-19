<?php

function mcc_include_core_files() {
	$mcc_path = plugin_dir_path( __FILE__ );
	require_once( $mcc_path . 'inc/content-copier/content-copier.php' );
	require_once( $mcc_path . 'inc/content-copier/content-copier-page.php' );
	require_once( $mcc_path . 'inc/content-copier/content-copier-post.php' );
	require_once( $mcc_path . 'inc/content-copier/content-copier-plugin.php' );
	require_once( $mcc_path . 'inc/content-copier/content-copier-user.php' );
	require_once( $mcc_path . 'inc/content-copier/content-copier-cpt.php' );
	require_once( $mcc_path . 'inc/helpers.php' );
	require_once( $mcc_path . 'model/copier-model.php' );

	if ( class_exists( 'Woocommerce' ) ) {
		require_once( MULTISTE_CC_INCLUDES_DIR . 'integration/woocommerce.php' );
	}
}

/**
 * Copy posts, pages or CPTs
 * 
 * @param String $type page,post or Custom Post Type slug
 * @param Array $items_ids page,post or cpt IDs
 * @param Integer $source_blog_id Source Blog ID
 * @param Array $dest_blog_ids Destination blog IDs
 * @param Array $args Settings for the copy execution
 			'copy_images' => false, Will copy images inside the post
			'update_date' => false, Will update the date of the post to the current date
			'copy_parents' => false, Will copy the parents of the items (if exist)
			'copy_comments' => false Will copy comments of the items (if exist)
			'copy_terms' => false Will copy terms (categories, tags) of the items
 * 
 * @return type
 */
function mcc_copy_items( $type, $items_ids, $source_blog_id, $dest_blog_ids, $args = array() ) {
	global $wpdb;

	$cpt_slug = '';
	switch ( $type ) {
		case 'post': {
			$class = 'Multisite_Content_Copier_Post_Copier';
			break;
		}
		case 'page': {
			$class = 'Multisite_Content_Copier_Page_Copier';
			break;
		}
		default: {
			$class = 'Multisite_Content_Copier_CPT_Copier';
			$cpt_slug = $type;
			break;
		}
	}

	if ( ! is_array( $dest_blog_ids ) || empty( $dest_blog_ids ) )
		return false;

	if ( ! is_array( $items_ids ) || empty( $items_ids ) )
		return false;

	$settings = $args;
	$settings['post_ids'] = $items_ids;
	$settings['post_type'] = $cpt_slug;

	$current_blog = get_current_blog_id();
	foreach ( $dest_blog_ids as $_blog_id ) {
		$blog_id = absint( $_blog_id );
		switch_to_blog( $blog_id );


		$wpdb->query( "BEGIN;" );
		$copier = new $class( absint( $source_blog_id ), $settings );
		$copier->execute();
		$wpdb->query( "COMMIT;" );

	}
	switch_to_blog( $current_blog );
}