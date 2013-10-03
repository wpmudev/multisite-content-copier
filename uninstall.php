<?php

if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

exit();

$plugin_dir = plugin_dir_path( __FILE__ );

require_once( $plugin_dir . 'origin-plugin.php' );
require_once( $plugin_dir . 'model/model.php' );
require_once( $plugin_dir . 'inc/settings-handler.php' );

delete_option( Origin_Plugin::$version_option_slug );

$model = Origin_Plugin_Model::get_instance();
delete_option( $model->schema_created_option_slug );
$model->delete_tables();

delete_option( Origin_Plugin_Settings_Handler::$settings_slug );
