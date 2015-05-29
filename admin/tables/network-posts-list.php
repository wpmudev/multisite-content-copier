<?php
class MCC_Posts_List_Table extends WP_List_Table {
	public $selected;
	public $post_type;
	public $blog_id;

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
			'post_type' => $this->post_type,
			'posts_per_page' => $per_page,
			'paged' => $current_page,
			'order' => 'DESC',
			'orderby' => 'date'
		);
		
		// Taxonomy filter
		$tax_query = array();

		$taxonomies = $this->get_post_type_taxonomies();
		foreach ( $taxonomies as $tax => $tax_name ) {
			if ( isset( $_POST[ $tax ] ) && absint( $_POST[ $tax ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => array( absint( $_POST[ $tax ] ) )
				);
			}
		} 

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query'] = $tax_query;
		}

		

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
			'status'	  => __( 'Status', MULTISTE_CC_LANG_DOMAIN ),
			'date'		  => __( 'Created', MULTISTE_CC_LANG_DOMAIN )
		);

		return $sites_columns;
	}


	function column_cb( $item ) {
		return '<input type="checkbox" name="post_id[]" ' . checked( in_array( $item->ID, $this->selected ), true, false ) . ' id="post_id_' . $item->ID . '" class="post_id" value="' . $item->ID . '">';
	}

	function column_title( $item ) {
		if ( empty( $item->post_title ) )
			$title = __( '(no title)', MULTISTE_CC_LANG_DOMAIN );
		else
			$title = $item->post_title;


		return '<label for="post_id_' . $item->ID . '">' . $title . '</label>';
	}

	function column_date( $item ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->post_date ) );
	}

	function column_status( $item ) {
		$status = '';
		switch ( $item->post_status ) {
			case 'private':
				$status = __('Privately Published');
				break;
			case 'publish':
				$status = __('Published');
				break;
			case 'future':
				$status = __('Scheduled');
				break;
			case 'pending':
				$status = __('Pending Review');
				break;
			case 'draft':
			case 'auto-draft':
				$status = __('Draft');
				break;
		}
		return $status;

	}

	function get_post_type_taxonomies() {
		$_taxonomies = get_object_taxonomies( $this->post_type );
		
		// We allow filtering by hierachical taxonomies
		$taxonomies = array();
		foreach ( $_taxonomies as $taxonomy ) {
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$labels = get_taxonomy( $taxonomy )->labels;
				$taxonomies[ $taxonomy ] = $labels->name;
			}
		}

		return $taxonomies;
	}



	function extra_tablenav( $which ) {
		$taxonomies = $this->get_post_type_taxonomies();
        ?>
        	<div class="alignleft actions">
            	<input type="submit" name="" id="doaction" class="button-primary action" value="<?php echo esc_attr( __( 'Add items to the list', MULTISTE_CC_LANG_DOMAIN ) ); ?>">
            </div>
            <div class="alignleft actions">
            	<?php $dropdowns = ''; ?>
                <?php foreach ( $taxonomies as $tax => $tax_name ): ?>
					<?php 
						$dropdowns .= wp_dropdown_categories( array(
							'show_option_all' => sprintf( __( 'Show all %s'), $tax_name ),
							'name' => $tax,
							'id' => 'filter_' . $tax,
							'taxonomy' => $tax,
							'selected' => isset( $_POST[ $tax ] ) ? absint( $_POST[ $tax ] ) : '',
							'hide_if_empty' => true,
							'echo' => false
						) ); 
					?>
            	<?php endforeach; ?>
            	<?php if ( ! empty( $dropdowns ) ): ?>
            		<?php echo $dropdowns; ?>
	            	<input type="submit" name="filter" id="filter" class="button action" value="<?php echo esc_attr( __( 'Filter', MULTISTE_CC_LANG_DOMAIN ) ); ?>">
	            <?php endif; ?>
            </div>
            <div class="alignleft actions">
                <span class="spinner"></span>
            </div>
        <?php
        
    }

}