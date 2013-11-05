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

	// This option will tell WP if the schema has been created
	// Instead of using the activation hook, we'll use this
	// TODO: Change slug
	public $schema_created_option_slug = 'mcc_schema_created';

	// Tables names
	private $queue_table;
	private $blogs_groups_table;
	private $blogs_groups_relationship_table;

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

		if ( ! get_site_option( $this->schema_created_option_slug, false ) ) {
			$this->create_schema();
			update_site_option( $this->schema_created_option_slug, true );
		}
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

		$wpdb->query( "DROP TABLE $this->queue_table;" );
	}

	public function deactivate_model() {
		delete_site_option( $this->schema_created_option_slug );
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

		$wpdb->insert(
			$this->queue_table,
			array( 
				'src_blog_id' => $src_blog_id,
				'dest_blog_id' => $dest_blog_id,
				'settings' => maybe_serialize( $settings )
			),
			array( '%d', '%d', '%s' )
		);
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

		$results = $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT bg.ID, bg.group_name FROM $this->blogs_groups_table bg
				LEFT JOIN $this->blogs_groups_relationship_table bgr ON bg.ID = bgr.blog_group_id
				WHERE bgr.blog_id = %d",
				$blog_id
			)
		);

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
			$wpdb->query( $wpdb->prepare( "UPDATE $this->blogs_groups_table SET bcount = bcount + 1 WHERE ID = %d", $group_id ) );

	}


}