<?php

class Multisite_Content_Copier_CPT_Copier extends Multisite_Content_Copier_Page_Copier {

	private $post_type;

	public function __construct( $orig_blog_id, $args ) {
		parent::__construct( $orig_blog_id, $args );

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		if ( empty( $settings['post_type'] ) )
			return false;

		extract( $settings );

		$this->post_type = $post_type;
	}

	protected function get_defaults_args() {
		$defaults = parent::get_defaults_args();
		$defaults['post_type'] = false;
		return $defaults;
	}

	public function copy( $post_id ) {
		$results = parent::copy( $post_id );

		$new_post_id = $results['new_post_id'];
		$new_parent_post_id = $results['new_parent_post_id'];

		return $results;
	}

}