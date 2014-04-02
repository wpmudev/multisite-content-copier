<?php

require_once( '/vagrant/www/wordpress-wpmudev/wp-content/plugins/multisite-content-copier/multisite-content-copier.php' ); 
require_once( 'includes/mcc_acpf_hooks.php' ); 
  
/**
 * Test the fields that has multiple/single Post Objects fields
 */
class MCC_ACPF_Integration extends WP_UnitTestCase {  
    function setUp() {  
          
        parent::setUp(); 

        global $multisite_content_copier_plugin;
        $this->plugin = $multisite_content_copier_plugin; 
        $this->plugin->include_copier_classes();

        $this->orig_blog_id = 1;
        $this->dest_blog_id = 2;

        $this->setup_initial_data();



    } // end setup  

    function setup_initial_data() {
        switch_to_blog( $this->orig_blog_id );
        add_action( 'init', array( &$this, 'register_post_type' ) );
        do_action( 'init' );
        restore_current_blog();

        switch_to_blog( $this->dest_blog_id );
        add_action( 'init', array( &$this, 'register_post_type' ) );
        do_action( 'init' );
        restore_current_blog();
    }

    /**
     * Register a sample CPT
     */    
    function register_post_type() {
        $labels = array(
            'name'                => __( 'Plural Name', 'text-domain' ),
            'singular_name'       => __( 'Singular Name', 'text-domain' ),
            'add_new'             => _x( 'Add New Singular Name', 'text-domain', 'text-domain' ),
            'add_new_item'        => __( 'Add New Singular Name', 'text-domain' ),
            'edit_item'           => __( 'Edit Singular Name', 'text-domain' ),
            'new_item'            => __( 'New Singular Name', 'text-domain' ),
            'view_item'           => __( 'View Singular Name', 'text-domain' ),
            'search_items'        => __( 'Search Plural Name', 'text-domain' ),
            'not_found'           => __( 'No Plural Name found', 'text-domain' ),
            'not_found_in_trash'  => __( 'No Plural Name found in Trash', 'text-domain' ),
            'parent_item_colon'   => __( 'Parent Singular Name:', 'text-domain' ),
            'menu_name'           => __( 'Plural Name', 'text-domain' ),
        );
    
        $args = array(
            'labels'                   => $labels,
            'hierarchical'        => false,
            'description'         => 'description',
            'taxonomies'          => array(),
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => null,
            'menu_icon'           => null,
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'has_archive'         => true,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => true,
            'capability_type'     => 'post',
            'supports'            => array(
                'title', 'editor', 'author', 'thumbnail',
                'excerpt','custom-fields', 'trackbacks', 'comments',
                'revisions', 'page-attributes', 'post-formats'
                )
        );
    
        register_post_type( 'event_speaker', $args );

        $labels = array(
            'name'                => __( 'Plural Name', 'text-domain' ),
            'singular_name'       => __( 'Singular Name', 'text-domain' ),
            'add_new'             => _x( 'Add New Singular Name', 'text-domain', 'text-domain' ),
            'add_new_item'        => __( 'Add New Singular Name', 'text-domain' ),
            'edit_item'           => __( 'Edit Singular Name', 'text-domain' ),
            'new_item'            => __( 'New Singular Name', 'text-domain' ),
            'view_item'           => __( 'View Singular Name', 'text-domain' ),
            'search_items'        => __( 'Search Plural Name', 'text-domain' ),
            'not_found'           => __( 'No Plural Name found', 'text-domain' ),
            'not_found_in_trash'  => __( 'No Plural Name found in Trash', 'text-domain' ),
            'parent_item_colon'   => __( 'Parent Singular Name:', 'text-domain' ),
            'menu_name'           => __( 'Plural Name', 'text-domain' ),
        );
    
        $args = array(
            'labels'                   => $labels,
            'hierarchical'        => false,
            'description'         => 'description',
            'taxonomies'          => array(),
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => null,
            'menu_icon'           => null,
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'has_archive'         => true,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => true,
            'capability_type'     => 'post',
            'supports'            => array(
                'title', 'editor', 'author', 'thumbnail',
                'excerpt','custom-fields', 'trackbacks', 'comments',
                'revisions', 'page-attributes', 'post-formats'
                )
        );
    
        register_post_type( 'event_venue', $args );
    }



    function tearDown() {
        parent::tearDown();
    }

    function test_registered_cpts() {
        switch_to_blog( $this->orig_blog_id );
        $post_types = get_post_types();
        $this->assertTrue( in_array( 'event_speaker', $post_types ) );
        restore_current_blog();


        switch_to_blog( $this->dest_blog_id );
        $post_types = get_post_types();
        $this->assertTrue( in_array( 'event_speaker', $post_types ) );
        restore_current_blog();
    }

    function test_copy_speakers_that_dont_exist() {
        global $wpdb;

        switch_to_blog( $this->orig_blog_id );
        $this->orig_post_id = $this->factory->post->create_object( array(
            'post_content' => 'content of post',
            'post_type' => 'post',
            'post_name' => 'a-post',
        ) );

        $this->orig_event_speaker_id_1 = $this->factory->post->create_object( array(
            'post_content' => 'An event speaker 1',
            'post_type' => 'event_speaker',
            'post_name' => 'a-event_speaker-1',
        ) );

        $this->orig_event_speaker_id_2 = $this->factory->post->create_object( array(
            'post_content' => 'An event speaker 2',
            'post_type' => 'event_speaker',
            'post_name' => 'a-event_speaker-2',
        ) );

        $this->orig_event_venue_id = $this->factory->post->create_object( array(
            'post_content' => 'An event venue',
            'post_type' => 'event_venue',
            'post_name' => 'a-event_venue',
        ) );

        update_post_meta( $this->orig_post_id, 'event_speaker', array( $this->orig_event_speaker_id_1, $this->orig_event_speaker_id_2 ) );
        update_post_meta( $this->orig_post_id, 'event_venue', $this->orig_event_venue_id );
        restore_current_blog();

        switch_to_blog( $this->dest_blog_id );
        $copier = Multisite_Content_Copier_Factory::get_copier( 'post', $this->orig_blog_id, array( $this->orig_post_id ), array() );
        $copier->execute();

        $results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_name = 'a-post' " );
        $this->assertNotEmpty( $results );

        $copied_post_id = $results[0]->ID;
        $event_speakers = get_post_meta( $copied_post_id, 'event_speaker', true );

        $this->assertEquals( count( $event_speakers ), 2 );

        foreach ( $event_speakers as $event_speaker ) {
            $this->assertEquals( get_post_type( $event_speaker ), 'event_speaker' );
        }

        $event_venue = get_post_meta( $copied_post_id, 'event_venue', true );
        $this->assertEquals( get_post_type( $event_venue ), 'event_venue' );
        
        restore_current_blog();
    }
    
}