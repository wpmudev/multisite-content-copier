<?php

require_once( '/vagrant/www/wordpress-wpmudev/wp-content/plugins/multisite-content-copier/multisite-content-copier.php' ); 
  
class MCC_Activate_Plugins extends WP_UnitTestCase {  
    function setUp() {  
          
        parent::setUp(); 

        global $multisite_content_copier_plugin;
        $this->plugin = $multisite_content_copier_plugin;
        $this->plugin->include_copier_classes(); 

        $this->dest_blog_id = 2;

        $this->network_activated_plugins = array( 'test-plugin2/test-plugin.php' );
        $this->single_activated_plugins = array( 'test-plugin1/test-plugin.php' );
        $this->deactivated_plugins = array( 'test-plugin3/test-plugin.php', 'test-plugin4/test-plugin.php', 'test-plugin5/test-plugin.php' );
        $this->plugins_to_activate = array( 'test-plugin3/test-plugin.php', 'test-plugin4/test-plugin.php' );

        $this->setup_initial_data();

    } // end setup  

    function setup_initial_data() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        activate_plugins( $this->network_activated_plugins, '', true );

        switch_to_blog( $this->dest_blog_id );
        activate_plugins( $this->single_activated_plugins, '', false );
        restore_current_blog();
    }

    function tearDown() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins( $this->network_activated_plugins, false, true );

        switch_to_blog( $this->dest_blog_id );
        deactivate_plugins( $this->single_activated_plugins, false, false );
        deactivate_plugins( $this->deactivated_plugins, false, false );
        restore_current_blog();
    }

    function test_init_test() {
        switch_to_blog( $this->dest_blog_id );

        foreach ( $this->network_activated_plugins as $plugin ) {
            $this->assertTrue( is_plugin_active( $plugin ) );
            $this->assertTrue( is_plugin_active_for_network( $plugin ) );
        }
        
        foreach ( $this->single_activated_plugins as $plugin ) {
            $this->assertTrue( is_plugin_active( $plugin ) );
            $this->assertFalse( is_plugin_active_for_network( $plugin ) );
        }

        foreach ( $this->deactivated_plugins as $plugin ) {
            $this->assertFalse( is_plugin_active( $plugin ) );
            $this->assertFalse( is_plugin_active_for_network( $plugin ) );
        }

        restore_current_blog();

        // Everything is ready for testing
    }

    function test_activate_plugins() {
        switch_to_blog( $this->dest_blog_id );

        $activator = Multisite_Content_Copier_Factory::get_copier( 'plugin', 0, $this->plugins_to_activate );
        $activator->execute();

        foreach ( $this->plugins_to_activate as $plugin ) {
            $this->assertTrue( is_plugin_active( $plugin ) );
        }

        $no_active_plugins = array_diff( $this->plugins_to_activate, $this->deactivated_plugins );
        foreach ( $no_active_plugins as $plugin ) {
            $this->assertFalse( is_plugin_active( $plugin ) );
        }

        restore_current_blog();
    }
}