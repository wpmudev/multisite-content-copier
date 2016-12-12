<?php
class MCC_Sites_List_Table extends WP_List_Table {
	function __construct( $args = array() ) {

        parent::__construct( array(
            'singular'  => __( 'Site', MULTISTE_CC_LANG_DOMAIN ),  
            'plural'    => __( 'Sites', MULTISTE_CC_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
	}

	public function prepare_items() {
		global $wpdb, $current_site;

		if ( isset( $_POST['mcc-assign-group'] ) && ! empty( $_POST['group_selected'] ) && ! empty( $_POST['blog_id'] ) && is_array( $_POST['blog_id'] ) ) {
			$model = mcc_get_model();

			foreach ( $_POST['blog_id'] as $blog_id ) {
				$model->assign_group_to_blog( absint( $blog_id ), absint( $_POST['group_selected'] ) );
			}
		}

		if ( isset( $_POST['mcc-remove-from-group'] ) && ! empty( $_POST['group_selected'] ) && ! empty( $_POST['blog_id'] ) && is_array( $_POST['blog_id'] ) ) {
			$model = mcc_get_model();

			foreach ( $_POST['blog_id'] as $blog_id ) {
				$model->remove_blog_from_group( absint( $blog_id ), absint( $_POST['group_selected'] ) );
			}
		}

		$per_page = 10;

    	$columns = $this->get_columns();
    	$hidden = array();
    	$sortable = $this->get_sortable_columns();

    	$this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $pagenum = $this->get_pagenum();

        $s = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST[ 's' ] ) ) : '';

		$like_s = esc_sql( $wpdb->esc_like( $s ) );
		

		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

		if ( empty($s) ) {
			// Nothing to do.
		} elseif ( preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.$/', $s ) ) {
			// IPv4 address
			$reg_blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->registration_log} WHERE {$wpdb->registration_log}.IP LIKE ( '{$like_s}$wild' )" );

			if ( !$reg_blog_ids )
				$reg_blog_ids = array( 0 );

			$query = "SELECT *
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
				AND {$wpdb->blogs}.blog_id IN (" . implode( ', ', $reg_blog_ids ) . ")";
		} else {
			if ( is_numeric($s) ) {
				$query .= " AND ( {$wpdb->blogs}.blog_id = '{$like_s}' )";
			} elseif ( is_subdomain_install() ) {
				$like_s = '%' . $like_s . '%';
				$query .= " AND ( {$wpdb->blogs}.domain LIKE '$like_s' ) ";
			} else {
				$like_s = '%' . $like_s . '%';
				$query .= " AND  ( {$wpdb->blogs}.path LIKE '$like_s' )";
			}

		}

		$order_q = '';
		if ( isset( $_REQUEST['orderby'] ) ) {
			$order = 'ASC';
			if ( ! empty( $_REQUEST['order'] ) && in_array( strtolower( $_REQUEST['order'] ), array( 'asc', 'desc' ) ) )
				$order = strtoupper( $_REQUEST['order'] );

			if ( 'blogname' == $_REQUEST['orderby'] ) {
				if ( is_subdomain_install() ) {
					$order_q = "ORDER BY {$wpdb->blogs}.domain $order";
				}
				else {
					$order_q = "ORDER BY {$wpdb->blogs}.path $order";
				}
			}
		}

		// Don't do an unbounded count on large networks
		if ( ! wp_is_large_network() )
			$total = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT COUNT( blog_id )', $query ) );

		$query .= "$order_q LIMIT " . intval( ( $pagenum - 1 ) * $per_page ) . ", " . intval( $per_page );

		$this->items = $wpdb->get_results( $query, ARRAY_A );

		if ( wp_is_large_network() )
			$total = count($this->items);

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page' => $per_page,
		) );
	}

	function get_columns() {
		$sites_columns = array(
			'cb'          => '<input type="checkbox" />',
			'blogname'    => __( 'URL' ),
			'groups'	  => __( 'Groups', MULTISTE_CC_LANG_DOMAIN )
		);

		return $sites_columns;
	}


	function column_cb( $item ) {
		return '<input type="checkbox" name="blog_id[]" value="' . $item['blog_id'] . '">';
	}

	function column_blogname( $item ) {
		$blogname = untrailingslashit( $item['domain'] . $item['path'] );
		return $blogname;
	}

	function column_groups( $item ) {
		$blog_id = $item['blog_id'];

		$model = mcc_get_model();
		$groups = $model->get_blog_groups( $blog_id );
		

		$return = array();
		foreach ( $groups as $group ) {
			$return[] = $group->group_name;
		}

		return implode( '<br/>', $return );
	}


	function extra_tablenav( $which ) {
        if ( 'top' == $which) {
        	$model = mcc_get_model();
        	$groups = $model->get_blogs_groups();
            ?>
                <div class="alignleft actions">
                    <select name="group_selected">
                    	<?php mcc_get_groups_dropdown(); ?>
                    </select>
                    <input type="submit" name="mcc-assign-group" id="mcc-assign-group" class="button" value="<?php _e( 'Assign to Group', MULTISTE_CC_LANG_DOMAIN ); ?>">
                    <input type="submit" name="mcc-remove-from-group" id="mcc-remove-group" class="button" value="<?php _e( 'Remove from Group', MULTISTE_CC_LANG_DOMAIN ); ?>">
                </div>
                <script>
                	jQuery(document).ready(function($) {
                		$('#mcc-blogs-groups-table-form').submit(function(e) {
                			var confirm = confirm( "<?php _e( 'Are you sure?', MULTISTE_CC_LANG_DOMAIN ); ?>" );
                			return confirm;
                		});
                	});
                </script>
            <?php
                
        }
        
    }

}