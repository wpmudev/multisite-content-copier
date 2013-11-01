<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MCC_Permanent_Assignments_List_Table extends WP_List_Table {

    private $data;

	function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Assignment', MULTISTE_CC_ADMIN_DIR ),  
            'plural'    => __( 'Assignments', MULTISTE_CC_ADMIN_DIR ), 
            'ajax'      => false        
        ) );
        
    }


    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'source_blog'   => __( 'Source Blog', MULTISTE_CC_ADMIN_DIR ),
            'count'         => __( 'Blogs count', MULTISTE_CC_ADMIN_DIR )
        );
        return $columns;
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="assignments[]" value="%d" />',
            $item->ID
        );
    }


    function column_source_blog( $item ) {
        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete',
                'assignment' => (int)$item->ID 
            )
        );

        $edit_link = add_query_arg( 
            array( 
                'action' => 'edit',
                'assignment' => (int)$item->ID 
            )
        );

        $actions = array(
            'edit' => sprintf( __( '<a href="%s">Edit</a>', MULTISTE_CC_LANG_DOMAIN ), $edit_link ),
            'delete'    => sprintf( __( '<a href="%s">Delete</a>', MULTISTE_CC_LANG_DOMAIN ), $delete_link )
        );

        $bloginfo = get_blog_details( $item->src_blog_id, false );
        $admin_link = get_admin_url( $item->src_blog_id );
        return $bloginfo->blogname . ' <a href="' . $admin_link . '">' . __( 'Go to dashboard', MULTISTE_CC_LANG_DOMAIN ) . '</a>' . $this->row_actions($actions);
    }


    function column_count( $item ) {
        return ( empty( $item->bcount ) ? 0 : $item->bcount );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete', MULTISTE_CC_ADMIN_DIR )
        );
        return $actions;
    }

 

    function prepare_items() {

        $model = mcc_get_model();

        if( 'delete' === $this->current_action() ) {

            if ( isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ) {
                if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) )
                    wp_die( 'Security check error', MULTISTE_CC_ADMIN_DIR );

                foreach ( $_POST['groups'] as $group )
                    $model->delete_blog_group( absint( $group ) );
            }
            elseif ( isset( $_GET['group'] ) && $group_id = absint( $_GET['group'] ) ) {
                $model->delete_blog_group( $group_id );
            }
        }

    	$per_page = 10;

    	$columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $current_page = $this->get_pagenum();

        
        $args = array(
            'page' => $current_page,
            'per_page' => $per_page
        );
        $data = $model->get_permanent_assigments( $args );

        $total_items = count( $data );

        $this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>