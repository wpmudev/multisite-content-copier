<?php
class MCC_Users_List_Table extends WP_List_Table {
	function __construct( $args = array() ) {

		$args = wp_parse_args( $args, array( 'blog_id' => 1, 'enabled' => false , 'selected' => array() ) );

		extract( $args );
		$this->blog_id = $blog_id;
		$this->selected = $selected;
		$this->enabled = $enabled;

        parent::__construct( array(
            'singular'  => __( 'User', MULTISTE_CC_LANG_DOMAIN ),  
            'plural'    => __( 'Users', MULTISTE_CC_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
	}

	public function prepare_items() {
		$per_page = 2;

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
			'number' => $per_page,
			'offset' => $per_page * ( $current_page - 1 ),
			'order' => 'ASC',
			'orderby' => 'user_login'
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['search'] = '*' . $_REQUEST['s'] . '*';
			$args['search_columns'] = array( 'user_login', 'user_email' );
		}

		$wp_query = new WP_User_Query( $args );
		restore_current_blog();

		$this->items = $wp_query->results;
        $total_items = $wp_query->total_users; 

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
			'username'    => __( 'Username' ),
			'email'		  => __( 'Email' ),
		);

		return $sites_columns;
	}


	function column_cb( $item ) {
		
		return '<input type="checkbox" name="user_id[]" ' . checked( in_array( $item->data->ID, $this->selected ), true, false ) . ' ' . disabled( $this->enabled == false, true, false ) . ' class="user_id" value="' . $item->data->ID . '">';
	}

	function column_username( $item ) {
		$avatar = get_avatar( $item->data->ID, '32' );
		return $avatar . ' ' . $item->data->user_login;
	}

	function column_email( $item ) {
		
		return $item->data->user_email;
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

    function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" ' . disabled( $this->enabled == false, true, false ) . ' type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

}