<?php

class Multisite_Content_Copier_Copier_Model {

	static $instance;


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
	protected function __construct() {}

	public function get_post_meta( $post_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post_id );

		return $wpdb->get_results( $query );

	}

	public function get_attachment_data( $filename ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s", '%' . $filename . '%' );

		return $wpdb->get_row( $query );
	}



}