<?php

require_once( 'C:\wamp\www\wpmudev2\wp-content\plugins\multisite-content-copier/multisite-content-copier.php' ); 
  
class MCC_Copy_Page extends WP_UnitTestCase {  

    protected $plugin;  
    protected $orig_blog_id;  
    protected $dest_blog_id; 
    protected $copier;
    protected $images_array;
    protected $base_dir;
    protected $new_page_id;
  
    function setUp() {  
          
        parent::setUp(); 

        global $multisite_content_copier_plugin;
        $this->plugin = $multisite_content_copier_plugin; 
        $this->plugin->include_copier_classes();

        $this->orig_blog_id = 2;
        $this->dest_blog_id = 3;
        $this->current_time = '2013/10';

        if ( $this->orig_blog_id == 1 )
            $this->orig_upload_subfolder = $this->current_time . '/';
        else
            $this->orig_upload_subfolder = 'sites/' . $this->orig_blog_id . '/' . $this->current_time . '/';

        switch_to_blog( $this->dest_blog_id );
        $upload_dir = wp_upload_dir();
        $this->dest_upload_subfolder = 'sites/' . $this->dest_blog_id . $upload_dir['subdir'] . '/';
        $this->dest_base_dir = $upload_dir['path'] . '/';
        restore_current_blog();

        $this->setup_initial_data();

    } // end setup  
      
    function setup_initial_data() {

        switch_to_blog( $this->orig_blog_id );

        // Copying images to the first upload folder
        $this->image = array( 'filename' => 'fondos-paisajes-1024-7.jpg', 'post_mime_type' => 'image/jpg' );

        $upload_dir = wp_upload_dir( $this->current_time );

        $current_dir = dirname( __FILE__ );
        $this->base_dir = $upload_dir['path'] . '/';

        copy( $current_dir . '/images/' . $this->image['filename'], $this->base_dir . $this->image['filename'] );

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $this->attachment_id = $this->factory->attachment->create_object( $this->image['filename'], 0, array(
            'post_mime_type' => $this->image['post_mime_type'],
            'post_type' => 'attachment',
            'post_title' => $this->image['filename'],
            'guid' => $upload_dir['url'] . '/' . basename( $upload_dir['path'] . "/" . $this->image['filename'] )
        ) );

        $metadata = wp_generate_attachment_metadata( $this->attachment_id, $this->base_dir . $this->image['filename'] );
        wp_update_attachment_metadata( $this->attachment_id, $metadata );

        $attachment_file_meta = get_post_meta( $this->attachment_id, '_wp_attached_file' );
        $new_attachment_file_meta = ltrim( $upload_dir['subdir'], '/' ) . '/' . $this->image['filename'];
        update_post_meta( $this->attachment_id, '_wp_attached_file', $new_attachment_file_meta );


        restore_current_blog();
    }

    function tearDown() {
        $files = glob( $this->base_dir . '/*');
        foreach ( $files as $image ) {
            unlink( $image );
        }

        $files = glob( $this->dest_base_dir . '/*');
        foreach ( $files as $image ) {
            unlink( $image );
        }
    }

    function test_copy_single_image() {
        switch_to_blog( $this->dest_blog_id );
        $new_attachment_id = Multisite_Content_Copier_Copier::copy_single_image( $this->orig_blog_id, $this->attachment_id );
        $this->assertNotEmpty( $new_attachment_id );

        $attachment = get_post( $new_attachment_id );
        $this->assertEquals( $attachment->post_title, $this->image['filename'] );

        $glob = glob( $this->dest_base_dir . '/' . $attachment->post_title );
        $this->assertNotEmpty( $glob );
        
        restore_current_blog();
    }
}