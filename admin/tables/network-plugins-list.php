<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MCC_Plugins_List_Table extends WP_List_Table {

    private $data;
    private $current_selected_plugins;

	function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Plugin', MULTISTE_CC_ADMIN_DIR ),  
            'plural'    => __( 'Plugins', MULTISTE_CC_ADMIN_DIR ), 
            'ajax'      => false        
        ) );
        
    }


    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'name'      => __( 'Plugin name', MULTISTE_CC_ADMIN_DIR ),
            'description'      => __( 'Plugin description', MULTISTE_CC_ADMIN_DIR )
        );
        return $columns;
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" class="plugin-item" name="plugins[]" ' . checked( $item['checked'], true, false ) . ' value="%s" />',
            $item['slug']
        );
    }


    function column_name( $item ) {
        return $item['name'];
    }

    function column_description( $item ) {
        return $item['description'];
    }

    function display_tablenav( $which ) {

    }


    function prepare_items() {

        if ( empty( $this->current_selected_plugins ) )
            $this->current_selected_plugins = array();

        $all_plugins = get_plugins();

        $per_page = 10000;

        $this->items = array();
		foreach ( $all_plugins as $plugin_file => $plugin ) {
			if ( ! is_network_only_plugin( $plugin_file ) && ! is_plugin_active_for_network( $plugin_file ) ) {
				$this->items[] = array(
					'slug' => $plugin_file,
					'name' => $plugin['Name'],
					'description' => $plugin['Description'],
					'checked' => in_array( $plugin_file, $this->current_selected_plugins )
				);
			}
		}

    	$columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $current_page = $this->get_pagenum();
        
        $total_items = count( $this->items );

        $this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>