<?php

require_once( 'C:\wamp\www\wpmudev2\wp-content\plugins\multisite-content-copier/multisite-content-copier.php' ); 
 

class MCC_Copy_Post extends WP_UnitTestCase {  
	function setUp() {  
		parent::setUp();
	}

	function tearDown() {
		global $wpdb;
		parent::tearDown();
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( 'blogtemplates/blogtemplates.php', true, true );
		$wpdb->query("DROP TABLE IF EXISTS wp_mcc_nbt_relationship");
	}

	function set_nonces_parameters() {
		$_GET['page'] = 'mcc_settings_page';
		$_REQUEST['_wp_http_referer'] = 'whatever';
		$_REQUEST['mcc_settings_nonce'] = wp_create_nonce( 'submit_mcc_settings' );
		$_SERVER['SERVER_NAME'] = 'dmain.com';
	}

	function test_activate_nbt_integration_nbt_no_active() {
		global $multisite_content_copier_plugin;
		$multisite_content_copier_plugin->init_plugin();
		
		$settings_menu = $multisite_content_copier_plugin::$network_settings_menu_page;
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Need to pass the IFs and nonces verifications
		$_POST[mcc_get_settings_slug()]['blog_templates_integration'] = 'on';
		$_POST['submit_mcc_settings'] = 'on';
		$this->set_nonces_parameters();

		add_filter( 'mcc_update_settings_screen_redirect_url', array( &$this, 'set_redirect_to' ) );
		$settings_menu->sanitize_settings();
		remove_filter( 'mcc_update_settings_screen_redirect_url', array( &$this, 'set_redirect_to' ) );

		$settings = mcc_get_settings();
		$this->assertFalse( $settings['blog_templates_integration'] );
	}

	function set_redirect_to( $link ) {
		return false;
	}

	function test_activate_nbt_integration_nbt_active_active() {
		global $multisite_content_copier_plugin, $wpdb;
		$multisite_content_copier_plugin->init_plugin();
		
		$settings_menu = $multisite_content_copier_plugin::$network_settings_menu_page;

		Multisite_Content_Copier_Errors_Handler::reset_errors();

		$this->set_nonces_parameters();

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		$result = activate_plugin( 'blogtemplates/blogtemplates.php', '', true, false );
		$this->assertTrue( is_plugin_active_for_network( 'blogtemplates/blogtemplates.php' ) );

		$_POST[mcc_get_settings_slug()]['blog_templates_integration'] = 'on';
		$_POST['submit_mcc_settings'] = 'on';
		

		add_filter( 'mcc_update_settings_screen_redirect_url', array( &$this, 'set_redirect_to' ) );
		$settings_menu->sanitize_settings();
		remove_filter( 'mcc_update_settings_screen_redirect_url', array( &$this, 'set_redirect_to' ) );

		$settings = mcc_get_settings();
		$this->assertTrue( $settings['blog_templates_integration'] );

		$tables = $wpdb->get_results( "SHOW TABLES LIKE 'wp_mcc_nbt_relationship'" );
		$this->assertCount( 1, $tables );
	}

}