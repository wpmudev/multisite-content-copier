<?php
class MCC_Posts_List_Table extends WP_List_Table {
	function __construct( $args = array() ) {

		$args = wp_parse_args( $args, array( 'blog_id' => 1, 'post_type' => 'post' , 'selected' => array() ) );

		extract( $args );
		$this->blog_id = $blog_id;
		$this->post_type = $post_type;
		$this->selected = $selected;

        parent::__construct( array(
            'singular'  => __( 'Post', MULTISTE_CC_LANG_DOMAIN ),  
            'plural'    => __( 'Posts', MULTISTE_CC_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
	}

	public function prepare_items() {
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
        
        switch_to_blog( $this->blog_id );
		
		$args = array(
			'post_status' => 'publish',
			'post_type' => $this->post_type,
			'posts_per_page ' => $per_page,
			'paged' => $current_page,
			'order' => 'DESC',
			'orderby' => 'date'
		);

		if ( ! empty( $_REQUEST['s'] ) )
			$args['s'] = $_REQUEST['s'];

		$wp_query = new WP_Query( $args );
		restore_current_blog();

		$this->items = $wp_query->posts;
        $total_items = $wp_query->found_posts; 

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
		) );
	}

	function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			?>
				<div class="tablenav <?php echo esc_attr( $which ); ?>">

					<div class="alignleft actions">
						<?php $this->bulk_actions(); ?>
					</div>
			<?php
					$this->extra_tablenav( $which );
					$this->pagination( $which );
			?>

					<br class="clear" />
				</div>
			<?php
		}
	}

	function get_columns() {
		$blogname_columns = ( is_subdomain_install() ) ? __( 'Domain' ) : __( 'Path' );
		$sites_columns = array(
			'cb'          => '<input type="checkbox" />',
			'title'    	  => __( 'Title', MULTISTE_CC_LANG_DOMAIN ),
			'date'		  => __( 'Published', MULTISTE_CC_LANG_DOMAIN )
		);

		return $sites_columns;
	}


	function column_cb( $item ) {
		return '<input type="checkbox" name="post_id[]" ' . checked( in_array( $item->ID, $this->selected ), true, false ) . ' class="post_id" value="' . $item->ID . '">';
	}

	function column_title( $item ) {
		return $item->post_title;
	}

	function column_date( $item ) {
		
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->post_date ) );
	}

	function get_bulk_actions() {
        $actions = array(
            'add'    => __( 'Add to the list', MULTISTE_CC_ADMIN_DIR )
        );
        return $actions;
    }


	function extra_tablenav( $which ) {
        ?>
            <div class="alignleft actions">
                <span class="spinner"></span>
            </div>
        <?php
        
    }

}