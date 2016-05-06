<?php

if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$plugin_dir = plugin_dir_path( __FILE__ );


delete_site_option( 'multisite_content_copier_plugin_version' );
delete_site_option( 'multisite_content_copier_settings' );
delete_site_option( 'mcc_schema_created' );
delete_site_option( 'show_nbt_integration_notice' );
delete_option( 'mcc_copying' );

global $wpdb;

$queue_table = $wpdb->base_prefix . 'mcc_queue';
$blogs_groups_table = $wpdb->base_prefix . 'mcc_blogs_groups';
$blogs_groups_relationship_table = $wpdb->base_prefix . 'mcc_blogs_groups_relationship';
$nbt_relationships_table = $wpdb->base_prefix . 'mcc_nbt_relationship';

$wpdb->query( "DROP TABLE IF EXISTS $queue_table;" );
$wpdb->query( "DROP TABLE IF EXISTS $blogs_groups_table;" );
$wpdb->query( "DROP TABLE IF EXISTS $blogs_groups_relationship_table;" );
$wpdb->query( "DROP TABLE IF EXISTS $blogs_groups_relationship_table;" );
$wpdb->query( "DROP TABLE IF EXISTS $nbt_relationships_table;" );

