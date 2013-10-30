<?php

class Multisite_Content_Copier_Plugins_Activator {
	
	private $plugins;

	public function __construct( $src_blog_id, $args ) {

		$settings = wp_parse_args( $args, $this->get_defaults_args() );

		extract( $settings );

		$this->plugins = is_array( $plugins ) ? $plugins : array();
	}

	private function get_defaults_args() {
		return array(
			'plugins' => array()
		);
	}

	public function execute() {
		$plugins = array();
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		foreach( $this->plugins as $plugin ) {
			if ( ! is_plugin_active( $plugin ) )
				$plugins[] = $plugin;				
		}
		activate_plugins( $plugins );
	}

	
}