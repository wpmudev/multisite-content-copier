<?php

function mcc_upgrade_11() {
	$model = mcc_get_model();
	$model->create_synced_posts_relationships_table();
}

function mcc_upgrade_111() {

	global $wpdb;

	$queue_table = $wpdb->base_prefix . 'mcc_queue';

	// First of all let's clean the table

	$blogs_ids = $wpdb->get_col( "SELECT DISTINCT src_blog_id FROM $queue_table" );
	if ( ! empty( $blogs_ids ) ) {
		foreach ( $blogs_ids as $blog_id ) {
			$blog_details = get_blog_details( $blog_id );
			if ( ! $blog_details ) {
				$wpdb->query( "DELETE FROM $queue_table WHERE src_blog_id = $blog_id " );
			}
		}
	}

	$blogs_ids = $wpdb->get_col( "SELECT DISTINCT dest_blog_id FROM $queue_table" );
	if ( ! empty( $blogs_ids ) ) {
		foreach ( $blogs_ids as $blog_id ) {
			$blog_details = get_blog_details( $blog_id );
			if ( ! $blog_details ) {
				$wpdb->query( "DELETE FROM $queue_table WHERE dest_blog_id = $blog_id " );
			}
		}
	}

	// Now let's adapt the old settings to the newest ones
	$queue_items = $wpdb->get_results( "SELECT * FROM $queue_table" );
	if ( ! empty( $queue_items ) ) {
		foreach ( $queue_items as $item ) {
			$settings = maybe_unserialize( $item->settings );

			$type = '';

			if ( ! isset( $settings['class'] ) )
				continue;
			
			switch ( $settings['class'] ) {
				case 'Multisite_Content_Copier_Post_Copier': {
					$type = 'post';
					break;
				}
				case 'Multisite_Content_Copier_CPT_Copier': {
					$type = 'post';
					break;
				}
				case 'Multisite_Content_Copier_User_Copier': {
					$type = 'user';
					break;
				}
				case 'Multisite_Content_Copier_Plugins_Activator': {
					$type = 'plugin';
					break;
				}
				case 'Multisite_Content_Copier_Page_Copier': {
					$type = 'page';
					break;
				}
			}
			unset( $settings['class'] );

			$items_ids = array();
			if ( isset( $settings['post_ids'] ) ) {
				$items_ids = $settings['post_ids'];
				unset( $settings['post_ids'] );
			}
			if ( isset( $settings['users'] ) ) {
				$items_ids = $settings['users'];
				unset( $settings['users'] );
			}
			if ( isset( $settings['plugins'] ) ) {
				$items_ids = $settings['plugins'];
				unset( $settings['plugins'] );
			}

			$new_settings = array(
				'type' => $type,
				'args' => $settings,
				'items_ids' => $items_ids
			);

			$wpdb->update(
				$queue_table,
				array( 'settings' => maybe_serialize( $new_settings ) ),
				array( 'ID' => $item->ID ),
				array( '%s' ),
				array( '%d' )
			);


		}
	}

}