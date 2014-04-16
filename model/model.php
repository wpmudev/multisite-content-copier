<?php

/**
 * The main model class.
 * 
 * Please, do not spread your queries through your
 * code, group your queries here.
 * 
 * You can create new classes for different models
 */

class Multisite_Content_Copier_Model {

	static $instance;

	// Tables names
	private $queue_table;
	private $blogs_groups_table;
	private $blogs_groups_relationship_table;
	private $synced_posts_relationships_table;

	// Charset and Collate
	private $db_charset_collate;


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

		 // Get the correct character collate
        $this->db_charset_collate = '';
        if ( ! empty($wpdb->charset) )
          $this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
          $this->db_charset_collate .= " COLLATE $wpdb->collate";

		$this->queue_table = $wpdb->base_prefix . 'mcc_queue';
		$this->blogs_groups_table = $wpdb->base_prefix . 'mcc_blogs_groups';
		$this->blogs_groups_relationship_table = $wpdb->base_prefix . 'mcc_blogs_groups_relationship';
		$this->synced_posts_relationships_table = $wpdb->base_prefix . 'mcc_synced_posts_relationships';

	}

	/**
	 * Create the required DB schema
	 * 
	 * @since 0.1
	 */
	public function create_schema() {
		$this->create_queue_table();
		$this->create_blogs_groups_table();
		$this->create_blogs_groups_relationship_table();
		$this->create_synced_posts_relationships_table();
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_queue_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->queue_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              src_blog_id bigint(20),
              dest_blog_id bigint(20),
              settings text DEFAULT '',
              PRIMARY KEY  (ID)
            )  ENGINE=MyISAM $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_blogs_groups_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->blogs_groups_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              group_name text,
              group_slug varchar(255),
              bcount bigint(20),
              PRIMARY KEY  (ID)
            )  ENGINE=MyISAM $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_blogs_groups_relationship_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->blogs_groups_relationship_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              blog_group_id bigint(20),
              blog_id bigint(20),
              PRIMARY KEY  (ID),
              UNIQUE KEY `group` (`blog_group_id`,`blog_id`)
            )  ENGINE=MyISAM $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	public function create_synced_posts_relationships_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->synced_posts_relationships_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              src_blog_id bigint(20),
              src_post_id bigint(20),
              dest_blog_id bigint(20),
              dest_post_id bigint(20),
              settings text,
              PRIMARY KEY  (ID),
              UNIQUE KEY `relation` (`src_blog_id`,`src_post_id`,`dest_blog_id`, `dest_post_id`),
              UNIQUE KEY `dest_relation` (`dest_blog_id`, `dest_post_id`)
            )  ENGINE=MyISAM $this->db_charset_collate;";

        dbDelta($sql);
	}


	public function get_synced_children( $src_post_id, $src_blog_id = 0 ) {
		global $wpdb;

		if ( ! $src_blog_id )
			$src_blog_id = get_current_blog_id();

		$pq = $wpdb->prepare(
			"SELECT dest_blog_id, dest_post_id FROM $this->synced_posts_relationships_table WHERE src_blog_id = %d AND src_post_id = %d",
			$src_blog_id,
			$src_post_id
		);

		return $wpdb->get_results( $pq );
	}

	public function get_synced_child( $src_post_id, $src_blog_id, $dest_blog_id = 0 ) {
		global $wpdb;

		if ( ! $dest_blog_id )
			$dest_blog_id = get_current_blog_id();

		$pq = $wpdb->prepare(
			"SELECT dest_post_id FROM $this->synced_posts_relationships_table WHERE src_blog_id = %d AND src_post_id = %d AND dest_blog_id = %d",
			$src_blog_id,
			$src_post_id,
			$dest_blog_id
		);

		return $wpdb->get_var( $pq );
	}

	public function add_synced_content( $src_post_id, $src_blog_id, $dest_posts, $settings = array() ) {
		global $wpdb;

		if ( ! is_array( $dest_posts ) || empty( $dest_posts ) )
			return new WP_Error( 'dest_posts',  __( "Destination posts must be an array", MULTISTE_CC_LANG_DOMAIN ) );

		$dest_blogs_ids = wp_list_pluck( $dest_posts, 'blog_id' );
		$dest_posts_ids = wp_list_pluck( $dest_posts, 'post_id' );

		if ( count( $dest_blogs_ids ) != count( $dest_posts_ids ) )
			return new WP_Error( 'dest_posts',  __( "Destination posts must be a coherent array", MULTISTE_CC_LANG_DOMAIN ) );

		$settings = maybe_serialize( $settings );

		$query = "INSERT IGNORE INTO $this->synced_posts_relationships_table ( src_blog_id, src_post_id, dest_blog_id, dest_post_id, settings )";
		$insert = array();
		for ( $i = 0; $i < count( $dest_blogs_ids ); $i++ ) {
			if ( $src_blog_id != $dest_blogs_ids[ $i ] )
				$insert[] = $wpdb->prepare( "(%d,%d,%d,%d,%s)", $src_blog_id, $src_post_id, $dest_blogs_ids[ $i ], $dest_posts_ids[ $i ], $settings );
		}
		
		if ( empty( $insert ) )
			return new WP_Error( 'dest_posts',  __( "Nothing to insert", MULTISTE_CC_LANG_DOMAIN ) );

		$insert = implode( ',', $insert );

		$query .= "VALUES $insert";

		$wpdb->query( $query );
	}

	public function get_synced_parent( $dest_post_id, $dest_blog_id = 0 ) {
		global $wpdb;

		if ( ! $src_blog_id )
			$src_blog_id = get_current_blog_id();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT src_blog_id, src_post_id FROM $this->synced_posts_relationships_table WHERE dest_blog_id = %d AND dest_post_id = %d",
				$dest_post_id,
				$dest_blog_id
			)
		);
	}

	public function post_is_synced( $src_post_id, $src_blog_id = 0 ) {
		global $wpdb;

		if ( ! $src_blog_id )
			$src_blog_id = get_current_blog_id();

		$cache = wp_cache_get( $src_post_id . ' ' . $src_blog_id, 'post_synced' );

		if ( ! empty( $cache ) )
			return $cache;

		$results = $wpdb->get_row( 
			$wpdb->prepare(
				"SELECT ID FROM $this->synced_posts_relationships_table WHERE src_blog_id = %d AND src_post_id = %d LIMIT 1",
				$src_blog_id,
				$src_post_id
			) 
		);

		wp_cache_set( $src_post_id . ' ' . $src_blog_id, $results, 'post_synced' );

		return $results;
	}

	public function delete_synced_content( $post_id, $blog_id = 0 ) {
		global $wpdb;

		if ( ! $blog_id )
			$blog_id = get_current_blog_id();

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $this->synced_posts_relationships_table
				WHERE ( src_blog_id = %d AND src_post_id = %d )",
				$blog_id,
				$post_id
			)
		);
	}


	/**
	 * Upgrades for the 0.2 version schema
	 */
	public function upgrade_schema_02() {
	}

	/**
	 * Drop the schema
	 */
	public function delete_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS $this->queue_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $this->blogs_groups_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $this->blogs_groups_relationship_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $this->synced_posts_relationships_table;" );
	}

	public function get_queued_elements_for_blog( $blog_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $this->queue_table WHERE dest_blog_id = %d", $blog_id )
		);

		return $results;
	}

	public function insert_queue_item( $src_blog_id, $dest_blog_id, $settings ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->queue_table,
			array( 
				'src_blog_id' => $src_blog_id,
				'dest_blog_id' => $dest_blog_id,
				'settings' => maybe_serialize( $settings )
			),
			array( '%d', '%d', '%s' )
		);

		if ( $inserted )
			return $wpdb->insert_id;
		else
			return false;
	}

	public function delete_queue_item( $id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->queue_table WHERE ID = %d", $id ) );
	}

	public function delete_queue_for_blog( $blog_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->queue_table WHERE dest_blog_id = %d OR src_blog_id = %d", $blog_id, $blog_id ) );	
	}

	public function get_blog_groups( $blog_id ) {
		global $wpdb;

		$q = $wpdb->prepare(
			"SELECT bg.ID, bg.group_name FROM $this->blogs_groups_table bg
			LEFT JOIN $this->blogs_groups_relationship_table bgr ON bg.ID = bgr.blog_group_id
			WHERE bgr.blog_id = %d",
			$blog_id
		);

		$results = $wpdb->get_results( $q );

		return $results;

	}

	public function get_blogs_from_group( $group_id ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->blogs_groups_relationship_table WHERE blog_group_id = %d", $group_id ) );

		return $results;
	}

	public function get_blogs_groups() {
		global $wpdb;

		$results = $wpdb->get_results( 
			"SELECT * FROM $this->blogs_groups_table",
			ARRAY_A
		);

		return $results;
	}

	public function get_blog_group( $group_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->blogs_groups_table WHERE ID = %d", $group_id ) );
	}

	public function add_new_blog_group( $group_name ) {
		global $wpdb;

		$wpdb->insert(
			$this->blogs_groups_table,
			array( 
				'group_name' => $group_name,
				'group_slug' => sanitize_title( $group_name ),
				'bcount' => 0
			),
			array( '%s', '%s', '%d' )
		);

		return $wpdb->insert_id;
	}

	public function delete_blog_group( $id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->blogs_groups_table WHERE ID = %d", $id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->blogs_groups_relationship_table WHERE group_id = %d", $id ) );

	}

	public function is_group( $group_id ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->blogs_groups_table WHERE ID = %d", $group_id ) );

		if ( ! empty( $results ) )
			return true;

		return false;
	}

	public function assign_group_to_blog( $blog_id, $group_id ) {
		global $wpdb;

		if ( ! $this->is_group( $group_id ) )
			return;

		$result = $wpdb->insert(
			$this->blogs_groups_relationship_table,
			array(
				'blog_group_id' => $group_id,
				'blog_id' => $blog_id
			),
			array( '%d', '%d' )
		);

		if ( $result )
			$this->refresh_group_blogs_counts( $group_id );

	}

	public function remove_blog_from_group( $blog_id, $group_id ) {
		global $wpdb;

		if ( ! $this->is_group( $group_id ) )
			return;

		$wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM $this->blogs_groups_relationship_table
				WHERE blog_id = %d AND blog_group_id = %d",
				$blog_id,
				$group_id
			)
		);

		$this->refresh_group_blogs_counts( $group_id );
	}

	public function refresh_group_blogs_counts( $group_id ) {
		global $wpdb;

		$bcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( ID ) FROM $this->blogs_groups_relationship_table WHERE blog_group_id = %d ", $group_id ) );
		$wpdb->query( $wpdb->prepare( "UPDATE $this->blogs_groups_table SET bcount = $bcount WHERE ID = %d", $group_id ) );
	}

	public function update_group( $group_id, $args ) {
		global $wpdb;

		if ( ! empty( $args ) ) {

			$values = array();
			$wildcards = array();
			if ( ! empty( $args['group_name'] ) ) {
				$values['group_name'] = $args['group_name'];
				$wildcards[] = '%s';
			}

			if ( ! empty( $values ) ) {
				$wpdb->update(
					$this->blogs_groups_table,
					$values,
					array( 'ID' => $group_id ),
					$wildcards,
					array( '%d' )
				);
			}
			
		}
	}

}