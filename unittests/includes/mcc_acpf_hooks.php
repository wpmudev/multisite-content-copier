<?php

/**
 * Plugin Name: MCC Hooks
 */

add_action( 'mcc_copy_post_meta', 'mcc_hooks_copy_post_meta', 10, 5 );
function mcc_hooks_copy_post_meta( $source_blog_id, $post_id, $new_post_id, $meta_key, $meta_value ) {

	if ( $meta_key == 'event_speaker' && is_array( $meta_value ) ) {
		// The meta value has an array of posts IDs.
		// We need to also copy those posts if they have not
		// been created

		$new_meta_value = array();

		// The best way to know if the posts is created
		// could be a slug comparison		
		foreach ( $meta_value as $speaker_id ) {
			switch_to_blog( $source_blog_id );
			$speaker = get_post( $speaker_id );
			restore_current_blog();

			if ( empty( $speaker ) )
				continue;

			// Let's try to find the post with the same slug
			global $wpdb;
			$speaker_name = $speaker->post_name;
			$destination_speakers = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_name = '$speaker_name' " );

			if ( empty( $destination_speakers ) ) {
				// There's no speaker created, let's copy it
				remove_action( 'mcc_copy_post_meta', 'mcc_hooks_copy_post_meta', 10, 5 );
				$copier = Multisite_Content_Copier_Factory::get_copier( 'post', $source_blog_id, array( $speaker->ID ), array() );
				$new_speaker = $copier->copy_item( $speaker->ID );

				add_action( 'mcc_copy_post_meta', 'mcc_hooks_copy_post_meta', 10, 5 );

				$new_meta_value[] = $new_speaker['new_post_id'];
			}
			else {
				// The speaker already exists, let's assign it
				$new_meta_value[] = $destination_speakers[0]->ID;
			}

		}

		// updating the meta value
		update_post_meta( $new_post_id, $meta_key, $new_meta_value );
		
	}

	if ( $meta_key == 'event_venue' ) {

		$venue_id = absint( $meta_value );
		if ( ! empty( $venue_id ) ) {
			switch_to_blog( $source_blog_id );
			$venue = get_post( $venue_id );
			restore_current_blog();

			if ( ! $venue )
				return;

			global $wpdb;
			$venue_name = $venue->post_name;
			$destination_venues = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_name = '$venue_name' " );

			if ( empty( $destination_venues ) ) {
				// There's no venue created, let's copy it
				remove_action( 'mcc_copy_post_meta', 'mcc_hooks_copy_post_meta', 10, 5 );
				$copier = Multisite_Content_Copier_Factory::get_copier( 'post', $source_blog_id, array( $venue->ID ), array() );
				$new_venue = $copier->copy_item( $venue->ID );

				add_action( 'mcc_copy_post_meta', 'mcc_hooks_copy_post_meta', 10, 5 );

				$new_meta_value = $new_venue['new_post_id'];
			}
			else {
				// The venue already exists, let's assign it
				$new_meta_value = $destination_venues[0]->ID;
			}

			// updating the meta value
			update_post_meta( $new_post_id, $meta_key, $new_meta_value );
		}
	}
}