<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MCC_NBT_Groups_List_Table extends WP_List_Table {

    private $data;

	function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Blog Templates Group', MULTISTE_CC_ADMIN_DIR ),  
            'plural'    => __( 'Blog Templates Groups', MULTISTE_CC_ADMIN_DIR ), 
            'ajax'      => false        
        ) );
        
    }


    function get_columns(){
        $columns = array(
            'name'      => __( 'Template name', MULTISTE_CC_ADMIN_DIR ),
            'description'      => __( 'Template description', MULTISTE_CC_ADMIN_DIR ),
            'count'     => __( 'Sites', MULTISTE_CC_ADMIN_DIR )
        );
        return $columns;
    }



    function column_name( $item ) {
        return stripslashes_deep( $item['name'] );
    }

    function column_description( $item ) {
        return stripslashes_deep( $item['description'] );
    }


    function column_count( $item ) {
        return ( empty( $item['bcount'] ) ? 0 : $item['bcount'] );
    }

 

    function prepare_items() {

        $nbt_model = mcc_get_nbt_model();

    	$per_page = 10;

        // Searching?
        $s = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST[ 's' ] ) ) : '';

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
            'offset' => ( $current_page - 1 ) * $per_page,
            'per_page' => $per_page,
            's' => $s
        );
        $data = $nbt_model->get_all_relationships( $args );

        $total_items = $nbt_model->get_relationships_count();

        if ( $total_items == 1000 )
            $total_items = count( $data );

        $this->items = (array)$data;
        
        $pag_args = array(
            'total_items' => $total_items,                
            'per_page'    => $per_page               
        );
        
        if ( $total_items < 1000 )
            $pag_args['total_pages'] = ceil( $total_items / $per_page );

        $this->set_pagination_args( $pag_args );

    }

}
?>