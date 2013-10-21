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
	public $schema_created_option_slug = 'multisite_content_copier_schema_created';

	// Tables names
	private $queue_table_name;

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

		$this->queue_table_name = $wpdb->base_prefix . 'mcc_queue';

		if ( ! get_site_option( $this->schema_created_option_slug, false ) ) {
			$this->create_schema();
			update_option( $this->schema_created_option_slug, true );
		}
	}

	/**
	 * Create the required DB schema
	 * 
	 * @since 0.1
	 */
	private function create_schema() {
		$this->create_queue_table();
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_queue_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->queue_table_name (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              src_blog_id bigint(20),
              dest_blog_id bigint(20),
              settings text DEFAULT '',
              PRIMARY KEY  (ID)
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

		$wpdb->query( "DROP TABLE $this->queue_table_name;" );
	}

	public function deactivate_model() {
		delete_site_option( $this->schema_created_option_slug );
	}

	public function get_queued_elements_for_blog( $blog_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $this->queue_table_name WHERE dest_blog_id = %d", $blog_id )
		);

		return $results;
	}

	public function insert_queue_item( $src_blog_id, $dest_blog_id, $settings ) {
		global $wpdb;

		$wpdb->insert(
			$this->queue_table_name,
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

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->queue_table_name WHERE ID = %d", $id ) );
	}



}