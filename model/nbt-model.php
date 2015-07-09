<?php

class Multisite_Content_Copier_NBT_Model {

	static $instance;

	private $nbt_relationships_table;

	/**
	 * Return an instance of the class
	 * 
	 * @since 0.1
	 * 
	 * @return Object
	 */
	public static function get_instance() {
		if ( self::$instance === null )
			self::$instance = new self();
            
        return self::$instance;
	}
 
	/**
	 * Set the tables names, charset, collate and creates the schema if needed.
	 * This way, the schema will be created when the model is created for first time.
	 */
	protected function __construct() {
		global $wpdb;

		$this->nbt_relationships_table = $wpdb->base_prefix . 'mcc_nbt_relationship';
		$this->templates_table = $wpdb->base_prefix . 'nbt_templates';

		// Get the correct character collate
        $this->db_charset_collate = '';
        if ( ! empty($wpdb->charset) )
          $this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
          $this->db_charset_collate .= " COLLATE $wpdb->collate";

	}

	public function create_nbt_relationships_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->nbt_relationships_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              template_id bigint(20),
              blog_id bigint(20),
              PRIMARY KEY  (ID),
              UNIQUE KEY `blog_id` (`blog_id`)
            )  ENGINE=MyISAM $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	public function insert_relationship( $blog_id, $template_id ) {
		global $wpdb;

		$relationship = $this->get_relationship( array( 'blog_id' => $blog_id, 'template_id' => $template_id ) );
		if ( ! empty( $relationship ) ) {
			$this->update_relationship( $blog_id, $template_id );
			return true;
		}

		return $wpdb->insert(
			$this->nbt_relationships_table,
			array( 
				'template_id' => $template_id,
				'blog_id' => $blog_id 
			),
			array( '%d', '%d' )
		);
	}

	public function update_relationship( $blog_id, $template_id ) {
		global $wpdb;

		$wpdb->update(
			$this->nbt_relationships_table,
			array( 'template_id' => $template_id ),
			array( 'blog_id' => $blog_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	public function get_relationship( $fields ) {
		global $wpdb;

		if ( empty( $fields['template_id'] ) || empty( $fields['blog_id'] ) ) {
			return false;
		}
		$where = array();
		if ( isset( $fields['blog_id'] ) ) {
			$where[] = $wpdb->prepare( "blog_id = %d", $fields['blog_id'] );
		}
		elseif ( $field == 'template_id' ) {
			$where[] = $wpdb->prepare( "template_id = %d", $fields['template_id'] );
		}
		else {
			return false;
		}

		$where = implode( " AND ", $where );

		return $wpdb->get_results( "SELECT * FROM $this->nbt_relationships_table WHERE $where" );
	}

	public function get_all_relationships( $args = array() ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$defaults = array(
			'offset' => 0,
			'per_page' => 20,
			's' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		$query = "SELECT rt.template_id, t.name, t.description, COUNT( t.ID ) bcount FROM $this->templates_table t
			INNER JOIN $this->nbt_relationships_table rt ON t.ID = rt.template_id
			WHERE t.network_id = $current_site_id";

		if ( $s ) {
			$wild = '';
	        if ( false !== strpos( $s, '*' ) ) {
	            $wild = '%';
	            $s = trim( $s, '*' );
	        }
	        $like_s = esc_sql( $wpdb->esc_like( $s ) );

	        $query .= " AND t.name LIKE ( '%{$like_s}$wild%' )";
		}

		$query .= $wpdb->prepare( " GROUP BY t.ID
			LIMIT %d, %d", 
			$offset, 
			$per_page 
		);

		

		return $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_relationships_count() {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$query = "SELECT rt.template_id, t.name, t.description, COUNT( t.ID ) bcount FROM $this->templates_table t
			INNER JOIN $this->nbt_relationships_table rt ON t.ID = rt.template_id
			WHERE t.network_id = $current_site_id
			GROUP BY t.ID
			LIMIT 0, 1000";

		return $results = count( $wpdb->get_results( $query ) );

	}

	public function delete_relationships( $field, $value ) {
		global $wpdb;

		if ( $field == 'template_id' ) {
			$where = $wpdb->prepare( " template_id = %d", $value );
		}
		elseif ( $field == 'blog_id' ) {
			$where = $wpdb->prepare( " blog_id = %d", $value );
		}
		else {
			return false;
		}

		$wpdb->query( "DELETE FROM $this->nbt_relationships_table WHERE $where" );
	}

	public function get_template_blogs( $template_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT blog_id FROM $this->nbt_relationships_table WHERE template_id = %d", $template_id );

		return $wpdb->get_col( $query );
	}

	public function is_template( $template_id ) {
		global $wpdb;

		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->templates_table WHERE ID = %d", $template_id ) );

		if ( empty( $results ) )
			return false;

		return true;

	}


	public function drop_nbt_relationships_table() {		
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS $this->nbt_relationships_table" );	
	}

	



}