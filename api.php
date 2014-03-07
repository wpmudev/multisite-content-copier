<?php

/** Example of use:
Copying two pages ( IDs 51 and 64 ) from blog ID = 4 to blog ID = 10 and 15
We want also to copy images and updtae the pages date to the current date

mcc_include_core_files();
$source_blog_id = 4
$dest_blog_ids = array( 10, 15 );
$pages_ids = array( 51, 64 )
$args = array(
	'copy_images' => truem
	'update_date' => true
);

mcc_copy_items( 'page', $pages_ids, $source_blog_id, $dest_blog_ids, $args );

**/

function mcc_include_core_files() {
	$mcc_path = plugin_dir_path( __FILE__ );
	require_once( $mcc_path . 'inc/content-copier/content-copier-factory.php' );
	require_once( $mcc_path . 'inc/helpers.php' );
	require_once( $mcc_path . 'model/copier-model.php' );

	if ( class_exists( 'Woocommerce' ) ) {
		require_once( MULTISTE_CC_INCLUDES_DIR . 'integration/woocommerce.php' );
	}
}

/**
 * Copy posts, pages or CPTs
 * 
 * @param String $type page or post
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

	if ( ! is_array( $dest_blog_ids ) || empty( $dest_blog_ids ) )
		return false;

	if ( ! is_array( $items_ids ) || empty( $items_ids ) )
		return false;

	$current_blog = get_current_blog_id();
	foreach ( $dest_blog_ids as $_blog_id ) {
		$blog_id = absint( $_blog_id );
		switch_to_blog( $blog_id );


		$wpdb->query( "BEGIN;" );
		$copier = Multisite_Content_Copier_Factory::get_copier( $type, $source_blog_id, $items_ids, $args );
		$copier->execute();
		$wpdb->query( "COMMIT;" );

	}
	switch_to_blog( $current_blog );
}

