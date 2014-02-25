<?php

class Multisite_Content_Copier_Plugin_Copier extends Multisite_Content_Copier_Abstract {

	public function get_default_args() {
		return array();
	}

	public function execute() {
		$plugins = array();
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		foreach( $this->items as $plugin ) {
			if ( ! is_plugin_active( $plugin ) )
				$plugins[] = $plugin;				
		}
		activate_plugins( $plugins );
	}

	public function copy_item() {}

	
}