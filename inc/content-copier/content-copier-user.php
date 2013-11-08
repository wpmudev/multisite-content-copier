<?php

class Multisite_Content_Copier_User_Copier extends Multisite_Content_Copier_Copier {
	
	protected $users;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->users_ids = $users;

	}

	protected function get_defaults_args() {
		return array(
			'users' => 'all'
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

		return add_user_to_blog( get_current_blog_id(), $user->data->ID, $user->roles[0] );
	}

}