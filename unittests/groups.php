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

        $model = mcc_get_model();
        $model->create_schema();

        $model = mcc_get_nbt_model();
        $model->create_nbt_relationships_table();


        if ( is_subdomain_install() ) {
            $this->domain = '';
            $this->path = '/testsite';
        }
        else {
            $this->domain = 'testsite';
            $this->path = null;   
        }


        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->new_blog_id = wpmu_create_blog( $this->domain, $this->path, 'Test Site Title', 1 );
        

        $this->setup_initial_data();

    } // end setup  
      
    function setup_initial_data() {
        //update_site_option( 'multisite_content_copier_plugin_version', MULTISTE_CC_VERSION );
    }

    function tearDown() {
        parent::tearDown();
        delete_site_option( 'multisite_content_copier_plugin_version' );
        
        $model = mcc_get_model();
        $model->delete_tables();

        $model = mcc_get_nbt_model();
        $model->drop_nbt_relationships_table();

        if ( is_integer( $this->new_blog_id ) )
           wpmu_delete_blog( $this->new_blog_id, true );
    }

    function test_insert_delete_group() {
        
        $model = mcc_get_model();
        $group_id_1 = $model->add_new_blog_group( 'group1' );
        $group_id_2 = $model->add_new_blog_group( 'group2' );

        $this->assertTrue( is_integer( $group_id_1 ) );
        $this->assertTrue( is_integer( $group_id_2 ) );

        $group_1 = $model->get_blog_group( $group_id_1 );

        $this->assertEquals( $group_1->bcount, 0 );

        $model->delete_blog_group( $group_id_1 );
        $group_1 = $model->get_blog_group( $group_id_1 );

        $this->assertEmpty( $group_1 );
       
    }

    function test_assign_groups() {
        $model = mcc_get_model();
        $group_id_1 = $model->add_new_blog_group( 'group1' );
        $group_id_2 = $model->add_new_blog_group( 'group2' );

        $model->assign_group_to_blog( $this->new_blog_id, $group_id_1 );
        $model->assign_group_to_blog( $this->new_blog_id, $group_id_2 );

        $group_1 = $model->get_blog_group( $group_id_1 );

        $this->assertEquals( $group_1->bcount, 1 );

        $model->remove_blog_from_group( $this->new_blog_id, $group_id_1 );

        $group_1 = $model->get_blog_group( $group_id_1 );

        $this->assertEquals( $group_1->bcount, 0 );
    }


    
    

} // end class  
