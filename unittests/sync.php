<?php

require_once( 'C:\wamp\www\wpmudev2\wp-content\plugins\multisite-content-copier/multisite-content-copier.php' ); 
  
class MCC_Sync extends WP_UnitTestCase {  

    protected $plugin;  
    protected $orig_blog_id;  
    protected $dest_blog_id; 
    protected $copier;
    protected $images_array;
    protected $base_dir;
    protected $new_post_id;
  
    function setUp() {  
          
        parent::setUp(); 

        global $multisite_content_copier_plugin;
        $this->plugin = $multisite_content_copier_plugin; 
        $this->plugin->include_copier_classes();

        $this->setup_initial_data();

    } // end setup  
      
    function setup_initial_data() {
        delete_site_option( 'mcc_schema_created' );
        Multisite_Content_Copier_Model::$instance = null;
        $model = mcc_get_model();

        //update_option( 'multisite_content_copier_plugin_version', '1.0.4' );
    }

    function tearDown() {
        parent::tearDown();
        delete_site_option( 'mcc_schema_created' );
        delete_site_option( 'multisite_content_copier_plugin_version' );
        $model = mcc_get_model();
        $model->delete_tables();
    }

    function test_add_relationships() {
        $model = mcc_get_model();
        $src_blog_id_1 = 1;
        $src_blog_id_2 = 2;

        $src_post_id_1 = 3;
        $src_post_id_2 = 4;

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEmpty($results);

        $dest_posts = array(
            array( 
                'blog_id' => 10,
                'post_id' => 3
            ),
            array( 
                'blog_id' => 3,
                'post_id' => 2
            )
        );
        $model->add_synced_content( $src_post_id_1, $src_blog_id_1, $dest_posts );

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEquals( count( $results ), 2 );

        // If we insert the same rows we should get the exact same results
        $dest_posts = array(
            array( 
                'blog_id' => 10,
                'post_id' => 3
            ),
            array( 
                'blog_id' => 3,
                'post_id' => 2
            )
        );
        $model->add_synced_content( $src_post_id_1, $src_blog_id_1, $dest_posts );

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEquals( count( $results ), 2 );

        // Now we insert the same children for a different parent but they should not be inserted
        $dest_posts = array(
            array( 
                'blog_id' => 10,
                'post_id' => 3
            ),
            array( 
                'blog_id' => 3,
                'post_id' => 2
            )
        );
        $model->add_synced_content( $src_post_id_2, $src_blog_id_2, $dest_posts );

        $results = $model->get_synced_children( $src_post_id_2, $src_blog_id_2 );
        $this->assertEmpty( $results );

        // Now we insert a new one
        $dest_posts = array(
            array( 
                'blog_id' => 21,
                'post_id' => 312
            )
        );
        $model->add_synced_content( $src_post_id_1, $src_blog_id_1, $dest_posts );

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEquals( count( $results ), 3 );

        // Inserting a child for that is in the same blog ID that the parent
        // This should not be inserted
        $dest_posts = array(
            array( 
                'blog_id' => $src_blog_id_1,
                'post_id' => 90
            )
        );

        $model->add_synced_content( $src_post_id_1, $src_blog_id_1, $dest_posts );

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEquals( count( $results ), 3 );

        // Getting just one child ID
        $results = $model->get_synced_child( $src_post_id_1, $src_blog_id_1, 10 );
        $this->assertEquals( $results, 3 );

        // Deleting
        $model->delete_synced_content( $src_post_id_1, $src_blog_id_1 );

        $results = $model->get_synced_children( $src_post_id_1, $src_blog_id_1 );
        $this->assertEquals( count( $results ), 0 );

    }


    public function test_copy_synced_page() {
        $src_blog_id = 1;
        $dest_blog_id = 2;

        switch_to_blog( $src_blog_id );
        $src_post_id = $this->factory->post->create_object( array(
            'post_content' => 'A content',
            'post_type' => 'page',
            'post_name' => 'a-page',
            'post_date' => '2013-09-25 00:00:00'
        ) );
        restore_current_blog();

        switch_to_blog( $dest_blog_id );
        $args = array(
            'post_ids' => array( $src_blog_id ),
            'sync' => true
        );

        $copier = new Multisite_Content_Copier_Page_Copier( $src_blog_id, $args );

        $results = $copier->copy( $src_post_id );
        
        $this->assertTrue( is_integer( $results['new_post_id'] ) && $results['new_post_id'] > 0 );

        $new_page = get_post( $results['new_post_id'] );
        $this->assertEquals( $new_page->post_name, 'a-page' );

        $model = mcc_get_model();
        $synced_child = $model->get_synced_child( $src_post_id, $src_blog_id, $dest_blog_id );
        $this->assertEquals( $synced_child, $results['new_post_id'] );

        restore_current_blog();

        // Let's update the page
        switch_to_blog( $src_blog_id );
        $page = get_post( $src_post_id );
        $postarr = (array)$page;
        $postarr['post_name'] = 'a-child-page';
        $postarr['post_content'] = 'A Child Page Content';
        wp_insert_post( $postarr );

        restore_current_blog();

        switch_to_blog( $dest_blog_id );
        $args = array(
            'post_ids' => array( $src_blog_id ),
            'updating' => true
        );

        $copier = new Multisite_Content_Copier_Page_Copier( $src_blog_id, $args );

        $results = $copier->copy( $src_post_id );

        $this->assertTrue( $results['new_post_id'] == $synced_child );

        $updated_page = get_post( $results['new_post_id'] );
        $this->assertTrue( $updated_page->post_name == 'a-child-page' );

        restore_current_blog();
    }

    public function test_copy_synced_post() {
        $src_blog_id = 1;
        $dest_blog_id = 2;

        switch_to_blog( $src_blog_id );
        $src_post_id = $this->factory->post->create_object( array(
            'post_content' => 'A content',
            'post_type' => 'post',
            'post_name' => 'a-post',
            'post_date' => '2013-09-25 00:00:00'
        ) );
        restore_current_blog();

        switch_to_blog( $dest_blog_id );
        $args = array(
            'post_ids' => array( $src_blog_id ),
            'sync' => true
        );

        $copier = new Multisite_Content_Copier_Post_Copier( $src_blog_id, $args );

        $results = $copier->copy( $src_post_id );
        
        $this->assertTrue( is_integer( $results['new_post_id'] ) && $results['new_post_id'] > 0 );

        $new_page = get_post( $results['new_post_id'] );
        $this->assertEquals( $new_page->post_name, 'a-post' );

        $model = mcc_get_model();
        $synced_child = $model->get_synced_child( $src_post_id, $src_blog_id, $dest_blog_id );
        $this->assertEquals( $synced_child, $results['new_post_id'] );

        restore_current_blog();

        // Let's update the page
        switch_to_blog( $src_blog_id );
        $post = get_post( $src_post_id );
        $postarr = (array)$post;
        $postarr['post_name'] = 'a-child-post';
        $postarr['post_content'] = 'A Child Post Content';
        wp_insert_post( $postarr );

        restore_current_blog();

        switch_to_blog( $dest_blog_id );
        $args = array(
            'post_ids' => array( $src_blog_id ),
            'updating' => true
        );

        $copier = new Multisite_Content_Copier_Page_Copier( $src_blog_id, $args );

        $results = $copier->copy( $src_post_id );

        $this->assertTrue( $results['new_post_id'] == $synced_child );

        $updated_post = get_post( $results['new_post_id'] );
        $this->assertTrue( $updated_post->post_name == 'a-child-post' );

        restore_current_blog();
    }
    

} // end class  
