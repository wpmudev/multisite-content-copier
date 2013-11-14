<?php

add_action( 'wp_ajax_mcc_get_sites_search', 'mcc_get_sites_search' );
function mcc_get_sites_search() {
	global $wpdb, $current_site;

	if ( ! empty( $_POST['term'] ) ) 
		$term = $_REQUEST['term'];
	else
		echo json_encode( array() );

	$s = isset( $_REQUEST['term'] ) ? stripslashes( trim( $_REQUEST[ 'term' ] ) ) : '';
	$wild = '%';
	if ( false !== strpos($s, '*') ) {
		$wild = '%';
		$s = trim($s, '*');
	}

	$like_s = esc_sql( like_escape( $s ) );
	$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

	if ( is_subdomain_install() ) {
		$blog_s = $wild . $like_s . $wild;
		$query .= " AND  ( {$wpdb->blogs}.domain LIKE '$blog_s' ) LIMIT 10";
	}
	else {
		if ( $like_s != trim('/', $current_site->path) )
			$blog_s = $current_site->path . $like_s . $wild . '/';
		else
			$blog_s = $like_s;	

		$query .= " AND  ( {$wpdb->blogs}.path LIKE '$blog_s' ) LIMIT 10";
	}
			
	$results = $wpdb->get_results( $query );

	$returning = array();
	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$details = get_blog_details( $row->blog_id );
			$ajax_url = get_admin_url( $row->blog_id , 'admin-ajax.php' );
			$returning[] = array( 
				'blog_name' => $details->blogname,
				'path' => $row->path, 
				'blog_id' => $row->blog_id,
				'ajax_url' => esc_url( $ajax_url )
			);
			
		}
	}

	echo json_encode( $returning );

	die();
}


add_action( 'wp_ajax_mcc_get_posts_search', 'mcc_get_posts_search' );
function mcc_get_posts_search() {
	$blog_id = absint( $_POST['blog_id'] );

	switch_to_blog( $blog_id );
	add_filter( 'posts_where', 'mcc_set_wp_query_filter' );
	$query = new WP_Query(
		array(
			'post_type' => 'post',
			'posts_per_page' => 10,
			'status' => 'publish'
		)
	);
	remove_filter( 'posts_where', 'mcc_set_wp_query_filter' );
	restore_current_blog();

	$returning = array();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) { 
			$query->the_post();
			$returning[] = array(
				'the_title' => get_the_title(),
				'the_id'	=> get_the_ID()
			);
		}

		wp_reset_postdata();
	}

	echo json_encode( $returning );

	die();

}

add_action( 'wp_ajax_mcc_get_users_search', 'mcc_get_users_search' );
function mcc_get_users_search() {
	$blog_id = absint( $_POST['blog_id'] );
	$usersearch = isset( $_POST['term'] ) ? trim( $_POST['term'] ) : '';

	switch_to_blog( $blog_id );
	$args = array(
		'number' => 10,
		'offset' => 0,
		'search' => '*' . $usersearch . '*',
		'fields' => 'all_with_meta'
	);

	// Query the user IDs for this page
	$wp_user_search = new WP_User_Query( $args );

	$results = $wp_user_search->get_results();
	restore_current_blog();

	$returning = array();
	foreach ( $results as $user_id => $user ) {
		$returning[] = array(
			'username' => $user->data->user_login,
			'user_id'	=> $user_id
		);
	}

	echo json_encode( $returning );

	die();

}


function mcc_set_wp_query_filter( $where = '' ) {
	global $wpdb;
	$s = $_POST['term'];
	$where .= $wpdb->prepare( " AND post_title LIKE %s", '%' . $s . '%' );

	return $where;
}

add_action( 'wp_ajax_mcc_insert_all_blogs_queue', 'mcc_insert_all_blogs_queue' );
function mcc_insert_all_blogs_queue() {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$offset = $_POST['offset'];
	$interval = $_POST['interval'];
	$content_blog_id = absint( $_POST['content_blog_id'] );

	if ( empty( $_POST['settings'] ) )
		$settings = array();
	else
		$settings = $_POST['settings'];

	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT blog_id FROM $wpdb->blogs 
			WHERE site_id = %d
			AND blog_id != %d
			ORDER BY blog_id
			LIMIT %d, %d",
			$current_site_id,
			$content_blog_id,
			$offset,
			$interval
		)
	);

	if ( ! empty( $results ) ) {
		$model = mcc_get_model();
		foreach ( $results as $dest_blog_id ) {
			$model->insert_queue_item( $content_blog_id, $dest_blog_id, $settings );	
		}
	}

	die();
	
}