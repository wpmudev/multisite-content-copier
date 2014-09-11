<?php

class Multisite_Content_Copier_User_Copier extends Multisite_Content_Copier_Abstract {
	
	public function get_default_args() {
		return array(
			'default_role' => 'subscriber'
		);
	}

	public function execute() {
		$users = array();

		switch_to_blog( $this->orig_blog_id );
		$args = array();

		if ( is_array( $this->items ) )
			$args['include'] = $this->items;
		
		$users = get_users( $args );
		restore_current_blog();

		foreach ( $users as $user )
			$this->copy_item( $user );

		/**
		 * Fired after posts have been copied in destination blog
		 * 
		 * @param Array $users List of users added to the destination blog
		 * @param Integer $orig_blog_id Source blog ID
		 */
		do_action( 'mcc_copy_users', $users, $this->orig_blog_id );
		
	}

	public function copy_item( $user ) {

		if ( ! isset( $user->roles[0] ) || ! isset( $user->data->ID ) )
			return false;

		$args = array(
			'number' => 1,
			'include' => $user->data->ID
		);
		$user_in_blog = get_users( $args );

		if ( ! empty( $user_in_blog ) )
			return false;

		switch_to_blog( $this->orig_blog_id );
		$orig_user = get_user_by( 'id', $user->data->ID );
		$orig_role = $orig_user->roles[0];
		restore_current_blog();

		require_once( ABSPATH . 'wp-admin/includes/user.php' );

		$roles = get_editable_roles();
		$roles = array_keys( $roles );

		$new_role = ! in_array( $orig_role, $roles ) ? $this->args['default_role'] : $orig_role;

		return add_user_to_blog( get_current_blog_id(), $user->data->ID, $new_role );
	}

}