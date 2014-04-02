<?php

require_once( 'C:\wamp\www\wpmudev2\wp-content\plugins\multisite-content-copier/api.php' ); 
  
class MCC_Api extends WP_UnitTestCase {  

    protected $plugin;  
    protected $orig_blog_id;  
    protected $dest_blog_id; 
    protected $copier;
    protected $images_array;
    protected $base_dir;
    protected $new_page_id;
  
    function setUp() {  
          
        parent::setUp(); 

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
        mcc_include_core_files();

        switch_to_blog( $this->orig_blog_id );

        // PAGE
        $post_content = 'a_content';

        $this->orig_parent_page_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'page',
            'post_name' => 'page-parent',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        $post_content = '<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png"><img class="alignnone size-full wp-image-274" alt="solo-cabeza" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png" width="1555" height="767" /><a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024.jpg"><img class="alignnone size-thumbnail wp-image-275" alt="IMG_2301-768x1024" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024-150x150.jpg" width="150" height="150" /></a></a>
<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7.jpg"><img class="alignnone size-medium wp-image-271" alt="fondos-paisajes-1024 (7)" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7-300x158.jpg" width="300" height="158" /></a>
&nbsp;
&nbsp;
<img class="alignnone" alt="" src="http://localhost/wpmudev2/wp-content/uploads/2013/08/uptown-laneway01-150x150.jpg" width="150" height="150" />
&nbsp;
&nbsp;';

        $this->orig_page_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'page',
            'post_parent' => $this->orig_parent_page_id,
            'post_name' => 'page-child',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        // Copying images to the first upload folder
        $this->images_array = array(
            array( 'filename' => 'fondos-paisajes-1024-7.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => $this->orig_page_id, 'thumbnail' => false ),
            array( 'filename' => 'IMG_2301-768x1024.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => 0, 'thumbnail' => false ),
            array( 'filename' => 'solo-cabeza2.png', 'post_mime_type' => 'image/png', 'post_parent' => $this->orig_page_id, 'thumbnail' => false ),
            array( 'filename' => 'thumbnail.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => false, 'thumbnail' => true )
        );

        $upload_dir = wp_upload_dir( $this->current_time );

        $current_dir = dirname( __FILE__ );
        $this->base_dir = $upload_dir['path'] . '/';

        foreach( $this->images_array as $image ) {
            copy( $current_dir . '/images/' . $image['filename'], $this->base_dir . $image['filename'] );
        }

        

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        foreach ( $this->images_array as $image ) {
            $attachment_id = $this->factory->attachment->create_object( $image['filename'], 0, array(
                'post_mime_type' => $image['post_mime_type'],
                'post_type' => 'attachment',
                'post_title' => $image['filename'],
                'post_parent' => $image['post_parent'],
                'guid' => $upload_dir['url'] . '/' . basename( $upload_dir['path'] . "/" . $image['filename'] )
            ) );

            $metadata = wp_generate_attachment_metadata( $attachment_id, $this->base_dir . $image['filename'] );
            wp_update_attachment_metadata( $attachment_id, $metadata );

            $attachment_file_meta = get_post_meta( $attachment_id, '_wp_attached_file' );
            $new_attachment_file_meta = ltrim( $upload_dir['subdir'], '/' ) . '/' . $image['filename'];
            update_post_meta( $attachment_id, '_wp_attached_file', $new_attachment_file_meta );

            if ( $image['thumbnail'] )
                set_post_thumbnail( $this->orig_page_id, $attachment_id );
        }


        // POST
        $post_content = 'a_content';

        $this->orig_parent_post_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'post',
            'post_name' => 'post-parent',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        $post_content = '<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png"><img class="alignnone size-full wp-image-274" alt="solo-cabeza" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png" width="1555" height="767" /><a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024.jpg"><img class="alignnone size-thumbnail wp-image-275" alt="IMG_2301-768x1024" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024-150x150.jpg" width="150" height="150" /></a></a>
<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7.jpg"><img class="alignnone size-medium wp-image-271" alt="fondos-paisajes-1024 (7)" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7-300x158.jpg" width="300" height="158" /></a>
&nbsp;
&nbsp;
<img class="alignnone" alt="" src="http://localhost/wpmudev2/wp-content/uploads/2013/08/uptown-laneway01-150x150.jpg" width="150" height="150" />
&nbsp;
&nbsp;';

        $this->orig_post_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'post',
            'post_parent' => $this->orig_parent_post_id,
            'post_name' => 'post-child',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        // Copying images to the first upload folder
        $this->images_array = array(
            array( 'filename' => 'fondos-paisajes-1024-7.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => $this->orig_post_id, 'thumbnail' => false ),
            array( 'filename' => 'IMG_2301-768x1024.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => 0, 'thumbnail' => false ),
            array( 'filename' => 'solo-cabeza2.png', 'post_mime_type' => 'image/png', 'post_parent' => $this->orig_post_id, 'thumbnail' => false ),
            array( 'filename' => 'thumbnail.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => false, 'thumbnail' => true )
        );

        $upload_dir = wp_upload_dir( $this->current_time );

        $current_dir = dirname( __FILE__ );
        $this->base_dir = $upload_dir['path'] . '/';

        foreach( $this->images_array as $image ) {
            copy( $current_dir . '/images/' . $image['filename'], $this->base_dir . $image['filename'] );
        }

        

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        foreach ( $this->images_array as $image ) {
            $attachment_id = $this->factory->attachment->create_object( $image['filename'], 0, array(
                'post_mime_type' => $image['post_mime_type'],
                'post_type' => 'attachment',
                'post_title' => $image['filename'],
                'post_parent' => $image['post_parent'],
                'guid' => $upload_dir['url'] . '/' . basename( $upload_dir['path'] . "/" . $image['filename'] )
            ) );

            $metadata = wp_generate_attachment_metadata( $attachment_id, $this->base_dir . $image['filename'] );
            wp_update_attachment_metadata( $attachment_id, $metadata );

            $attachment_file_meta = get_post_meta( $attachment_id, '_wp_attached_file' );
            $new_attachment_file_meta = ltrim( $upload_dir['subdir'], '/' ) . '/' . $image['filename'];
            update_post_meta( $attachment_id, '_wp_attached_file', $new_attachment_file_meta );

            if ( $image['thumbnail'] )
                set_post_thumbnail( $this->orig_post_id, $attachment_id );
        }

        // CPT
        $labels = array(
            'name'               => 'Books',
            'singular_name'      => 'Book',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Book',
            'edit_item'          => 'Edit Book',
            'new_item'           => 'New Book',
            'all_items'          => 'All Books',
            'view_item'          => 'View Book',
            'search_items'       => 'Search Books',
            'not_found'          => 'No books found',
            'not_found_in_trash' => 'No books found in Trash',
            'parent_item_colon'  => '',
            'menu_name'          => 'Books'
          );

          $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'book' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
          );        
        register_post_type( 'book', $args );

        $post_content = 'a_content';

        $this->orig_parent_cpt_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'book',
            'post_name' => 'book-parent',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        $post_content = '<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png"><img class="alignnone size-full wp-image-274" alt="solo-cabeza" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'solo-cabeza2.png" width="1555" height="767" /><a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024.jpg"><img class="alignnone size-thumbnail wp-image-275" alt="IMG_2301-768x1024" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'IMG_2301-768x1024-150x150.jpg" width="150" height="150" /></a></a>
<a href="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7.jpg"><img class="alignnone size-medium wp-image-271" alt="fondos-paisajes-1024 (7)" src="http://localhost/phpunit-wp/wp-content/uploads/' . $this->orig_upload_subfolder . 'fondos-paisajes-1024-7-300x158.jpg" width="300" height="158" /></a>
&nbsp;
&nbsp;
<img class="alignnone" alt="" src="http://localhost/wpmudev2/wp-content/uploads/2013/08/uptown-laneway01-150x150.jpg" width="150" height="150" />
&nbsp;
&nbsp;';

        $this->orig_cpt_id = $this->factory->post->create_object( array(
            'post_content' => $post_content,
            'post_type' => 'book',
            'post_parent' => $this->orig_parent_cpt_id,
            'post_name' => 'book-child',
            'post_date' => '2013-09-25 00:00:00',
            'post_status' => 'publish'
        ) );

        // Copying images to the first upload folder
        $this->images_array = array(
            array( 'filename' => 'fondos-paisajes-1024-7.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => $this->orig_cpt_id, 'thumbnail' => false ),
            array( 'filename' => 'IMG_2301-768x1024.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => 0, 'thumbnail' => false ),
            array( 'filename' => 'solo-cabeza2.png', 'post_mime_type' => 'image/png', 'post_parent' => $this->orig_cpt_id, 'thumbnail' => false ),
            array( 'filename' => 'thumbnail.jpg', 'post_mime_type' => 'image/jpg', 'post_parent' => false, 'thumbnail' => true )
        );

        $upload_dir = wp_upload_dir( $this->current_time );

        $current_dir = dirname( __FILE__ );
        $this->base_dir = $upload_dir['path'] . '/';

        foreach( $this->images_array as $image ) {
            copy( $current_dir . '/images/' . $image['filename'], $this->base_dir . $image['filename'] );
        }

        

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        foreach ( $this->images_array as $image ) {
            $attachment_id = $this->factory->attachment->create_object( $image['filename'], 0, array(
                'post_mime_type' => $image['post_mime_type'],
                'post_type' => 'attachment',
                'post_title' => $image['filename'],
                'post_parent' => $image['post_parent'],
                'guid' => $upload_dir['url'] . '/' . basename( $upload_dir['path'] . "/" . $image['filename'] )
            ) );

            $metadata = wp_generate_attachment_metadata( $attachment_id, $this->base_dir . $image['filename'] );
            wp_update_attachment_metadata( $attachment_id, $metadata );

            $attachment_file_meta = get_post_meta( $attachment_id, '_wp_attached_file' );
            $new_attachment_file_meta = ltrim( $upload_dir['subdir'], '/' ) . '/' . $image['filename'];
            update_post_meta( $attachment_id, '_wp_attached_file', $new_attachment_file_meta );

            if ( $image['thumbnail'] )
                set_post_thumbnail( $this->orig_cpt_id, $attachment_id );
        }

        restore_current_blog();

        switch_to_blog( $this->dest_blog_id );
        $labels = array(
            'name'               => 'Books',
            'singular_name'      => 'Book',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Book',
            'edit_item'          => 'Edit Book',
            'new_item'           => 'New Book',
            'all_items'          => 'All Books',
            'view_item'          => 'View Book',
            'search_items'       => 'Search Books',
            'not_found'          => 'No books found',
            'not_found_in_trash' => 'No books found in Trash',
            'parent_item_colon'  => '',
            'menu_name'          => 'Books'
          );

          $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'book' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
          );        
        register_post_type( 'book', $args );
        restore_current_blog();
        //update_option( 'multisite_content_copier_plugin_version', '1.0.4' );
    }

    function tearDown() {

        parent::tearDown();

        global $wpdb;
        $wpdb->query( "BEGIN;");
        switch_to_blog( $this->orig_blog_id );
        $wpdb->query( "DELETE FROM $wpdb->posts");
        $wpdb->query( "DELETE FROM $wpdb->postmeta");
        restore_current_blog();

        switch_to_blog( $this->dest_blog_id );
        $wpdb->query( "DELETE FROM $wpdb->posts");
        $wpdb->query( "DELETE FROM $wpdb->postmeta");
        restore_current_blog();

        $wpdb->query( "COMMIT;");
        
        $files = glob( $this->base_dir . '/*');
        foreach ( $files as $image ) {
            unlink( $image );
        }

        $files = glob( $this->dest_base_dir . '/*');
        foreach ( $files as $image ) {
            unlink( $image );
        }

        

    }

    function test_copy_page_with_api() {
        $args = array(
            'copy_images' => true,
            'keep_user' => true,
            'update_date' => false,
            'copy_parents' => false,
            'copy_comments' => false
        );

        mcc_copy_items( 'page', array( $this->orig_page_id ), $this->orig_blog_id, array( $this->dest_blog_id ), $args );

        switch_to_blog( $this->dest_blog_id );
        $pages = get_pages();
        $pages = wp_filter_object_list( $pages, $args = array( 'post_name' => 'page-child' ) );
        $this->assertEquals( count( $pages ), 1 );
        restore_current_blog();


    }

    function test_copy_post_with_api() {
        $args = array(
            'copy_images' => true,
            'keep_user' => true,
            'update_date' => false,
            'copy_parents' => false,
            'copy_comments' => false,
            'copy_terms' => false
        );

        mcc_copy_items( 'post', array( $this->orig_post_id ), $this->orig_blog_id, array( $this->dest_blog_id ), $args );

        switch_to_blog( $this->dest_blog_id );
        $posts = get_posts();
        $posts = wp_filter_object_list( $posts, $args = array( 'post_name' => 'post-child' ) );
        $this->assertEquals( count( $posts ), 1 );
        restore_current_blog();


    }

    function test_copy_cpt_with_api() {
        $args = array(
            'copy_images' => true,
            'keep_user' => true,
            'update_date' => false,
            'copy_parents' => false,
            'copy_comments' => false,
            'copy_terms' => false
        );

        mcc_copy_items( 'book', array( $this->orig_cpt_id ), $this->orig_blog_id, array( $this->dest_blog_id ), $args );

        switch_to_blog( $this->dest_blog_id );
        $books = get_posts( array( 'post_type' => 'book' ));
        $books = wp_filter_object_list( $books, $args = array( 'post_name' => 'book-child' ) );
        $this->assertEquals( count( $books ), 1 );
        restore_current_blog();


    }

    

} // end class  
