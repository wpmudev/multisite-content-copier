<?php

class Multisite_Content_Copier_User_Copier extends Multisite_Content_Copier_Copier {
	
	protected $users;
	protected $default_role;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->users_ids = $users;
		$this->default_role = $default_role;

	}

	protected function get_defaults_args() {
		return array(
			'users' => 'all',
			'default_role' => 'subscriber'
		);
	}

	public function execute() {
		$users = array();

		switch_to_blog( $this->orig_blog_id );
		$args = array();

		if ( is_array( $this->users_ids ) )
			$args['include'] = $this->users_ids;
		
		$users = get_users( $args );
		restore_current_blog();

		foreach ( $users as $user ) {
			$this->copy( $user );
		}
		
	}

	public function copy( $user ) {

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

		$new_role = ! in_array( $orig_role, $roles ) ? $this->default_role : $orig_role;

		return add_user_to_blog( get_current_blog_id(), $user->data->ID, $new_role );
	}

}