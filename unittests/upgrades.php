<?php

require_once( '/vagrant/www/wordpress-wpmudev/wp-content/plugins/multisite-content-copier/multisite-content-copier.php' ); 
  
class MCC_Copy_CPT extends WP_UnitTestCase {  

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


        $this->orig_blog_id = 1;
        $this->dest_blog_id = 2;

        $this->setup_initial_data();

    } // end setup  
      
    function setup_initial_data() {
        //update_option( 'multisite_content_copier_plugin_version', '1.0.4' );
    }

    function tearDown() {
        parent::tearDown();
        delete_site_option( 'multisite_content_copier_plugin_version' );

        $model = mcc_get_model();
        $model->delete_tables();
    }


    function test_upgrade_111() {

        update_site_option( 'multisite_content_copier_plugin_version', '1.1' );
        $model = mcc_get_model();
        $model->create_schema();

        $settings = array(
            'copy_images' => 1,
            'update_date' => 1,
            'copy_parents' => 1,
            'copy_terms' => 1,
            'class' => 'Multisite_Content_Copier_Post_Copier',
            'post_ids' => array( 25, 23 )
        );
        $result = $model->insert_queue_item( $this->orig_blog_id, $this->dest_blog_id, $settings );

        $settings = array(
            'post_type' => 'product',
            'class' => 'Multisite_Content_Copier_CPT_Copier',
            'post_ids' => array( 104 )
        );
        $model->insert_queue_item( $this->orig_blog_id, $this->dest_blog_id, $settings ); 

        $settings = array(
            'class' => 'Multisite_Content_Copier_User_Copier',
            'users' => 'all'
        );
        $model->insert_queue_item( $this->orig_blog_id, $this->dest_blog_id, $settings );  

        $settings = array(
            'class' => 'Multisite_Content_Copier_Plugins_Activator',
            'plugins' => array( 'add-existing-users/add-existing-users.php' )
        );
        $model->insert_queue_item( $this->orig_blog_id, $this->dest_blog_id, $settings );  

        $settings = array(
            'class' => 'Multisite_Content_Copier_Page_Copier',
            'post_ids' => array( 129, 107, 40 ),
            'copy_parents' => 1
        );
        $model->insert_queue_item( $this->orig_blog_id, $this->dest_blog_id, $settings );  


        // This blog does not exist
        $settings = array(
            'class' => 'Multisite_Content_Copier_Page_Copier',
            'post_ids' => array( 129, 107, 40 ),
            'copy_parents' => 1
        );
        $model->insert_queue_item( $this->orig_blog_id, 200, $settings ); 

        $this->plugin->maybe_upgrade();

        $this->assertEquals( get_site_option('multisite_content_copier_plugin_version'), '1.1.1' );

        switch_to_blog( $this->dest_blog_id );
        $queue = mcc_get_queue_for_blog();
        delete_transient( 'mcc_copying' );
        add_filter( 'mcc_execute_copier', array( &$this, 'disable_execute' ), 10, 2 );
        $this->plugin->maybe_copy_content();
        restore_current_blog();
    }


    public function disable_execute( $execute, $copier ) {
        return false;
    }

    public function test_upgrade_122() {
        
    }
    

} // end class  
