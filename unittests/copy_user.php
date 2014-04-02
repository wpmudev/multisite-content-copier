<?php

require_once( '/vagrant/www/wordpress-wpmudev/wp-content/plugins/multisite-content-copier/multisite-content-copier.php' ); 
  
class MCC_Copy_Users extends WP_UnitTestCase {  
    function setUp() {  
          
        parent::setUp(); 

        global $multisite_content_copier_plugin;
        $this->plugin = $multisite_content_copier_plugin; 
        $this->plugin->include_copier_classes();

        $this->orig_blog_id = 1;
        $this->dest_blog_id = 2;

        $this->users = array();
        $this->users_ids = array();

        $this->setup_initial_data();



    } // end setup  

    function setup_initial_data() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        switch_to_blog( $this->orig_blog_id );
        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );

        $user_id = wp_create_user( 'user1', $random_password, 'user1@gmail.com' );
        add_user_to_blog( get_current_blog_id(), $user_id, 'administrator' );
        $this->users[ $user_id ] = 'administrator';
        $this->users_ids[] = $user_id;

        $user_id = wp_create_user( 'user2', $random_password, 'user2@gmail.com' );
        add_user_to_blog( get_current_blog_id(), $user_id, 'author' );
        $this->users[ $user_id ] = 'author';
        $this->users_ids[] = $user_id;

        $user_id = wp_create_user( 'user3', $random_password, 'user3@gmail.com' );
        add_user_to_blog( get_current_blog_id(), $user_id, 'author' );
        $this->users[ $user_id ] = 'author';
        $this->users_ids[] = $user_id;

        restore_current_blog();
    }

    function tearDown() {
        parent::tearDown();
        include_once( ABSPATH . 'wp-admin/includes/user.php' );

        foreach ( $this->users as $user_id => $role ) {
            wp_delete_user( $user_id );  
        }        

    }

    /**
     * Copy 2 users
     * @return type
     */
    function test_copy_few_users() {
        $users_to_copy = array( $this->users_ids[0], $this->users_ids[1] );

        switch_to_blog( $this->dest_blog_id );

        $copier = Multisite_Content_Copier_Factory::get_copier( 'user', $this->orig_blog_id, $users_to_copy );
        $copier->execute();

        $current_users = get_users();
        foreach ( $current_users as $user ) {
            $current_users[ $user->data->ID ] = $user->roles[0];
        }

        foreach ( $users_to_copy as $user_id ) {
            $role = $this->users[ $user_id ];
            $this->assertTrue( array_key_exists( $user_id, $current_users ) );
            $this->assertTrue( $current_users[ $user_id ] == $role );
        }

        // Let's check if the user that we didn't want to copy hasn't been added
        $this->assertFalse( array_key_exists( $this->users_ids[2], $current_users ) );

        restore_current_blog();
    }

    function test_copy_already_existent_user_with_different_role() {
        switch_to_blog( $this->dest_blog_id );
        // First we'll add an already existent user with a different role than has in the orig blog
        // Instead of administrator we'll add it as a subscriber
        $already_existent_user = add_user_to_blog( get_current_blog_id(), $this->users_ids[0], 'subscriber' );

        $users_to_copy = array(
            'users' => $this->users_ids[0]
        );
        $copier = Multisite_Content_Copier_Factory::get_copier( 'user', $this->orig_blog_id, $users_to_copy );
        $copier->execute();

        // The user must exists but he should have subscriber role
        $user = get_userdata( $this->users_ids[0] );
        
        $this->assertEquals( $user->roles[0], 'subscriber' );

        restore_current_blog();
    }

    /**
     * Copy a user from a blog that has assigned a role 
     * that does not exist in the destination blog
     * @return type
     */
    function test_copy_user_with_no_existant_role() {
        global $wp_roles;
        switch_to_blog( $this->orig_blog_id );

        $result = add_role(
            'orig_blog_test_role',
            __( 'Orig Blog Test Role' ),
            array(
                'read'         => true,
                'edit_posts'   => true,
                'delete_posts' => false,
            )
        );
        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $custom_role_user_id = wp_create_user( 'custom_role_user', $random_password, 'custom_role_user_id@gmail.com' );
        add_user_to_blog( get_current_blog_id(), $custom_role_user_id, 'orig_blog_test_role' );
        restore_current_blog();

        switch_to_blog( $this->dest_blog_id );
        $users_to_copy = array(
            'users' => array( $custom_role_user_id )
        );
        $args = array(
            'default_role' => 'administrator'
        );
        $copier = Multisite_Content_Copier_Factory::get_copier( 'user', $this->orig_blog_id, $users_to_copy, $args );
        $copier->execute();

        $copied_user = get_user_by( 'id', $custom_role_user_id );
        $copied_user_role = $copied_user->roles[0];

        $this->assertEquals( 'administrator', $copied_user_role );

        restore_current_blog();

        switch_to_blog( $this->orig_blog_id );
        wp_delete_user( $custom_role_user_id ); 
        restore_current_blog();
    }
}