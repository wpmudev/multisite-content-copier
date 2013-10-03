<?php

/**
 * We need this class if we want to handle settings efficiently
 * Settings will be loaded just when needed
 * 
 * You can create different classes for different settings groups
 * if your plugin is too big
 */

class Multisite_Content_Copier_Settings_Handler {

	static $instance;

	// Settings slug for DB
	private $settings_slug = 'multisite_content_copier_settings';

	// Settings for the plugin
	private $settings = array();

	/**
	 * Get the default settings
	 * 
	 * @return Array of settings
	 */
	public function get_default_settings() {
		return array(
			'a_setting' => 12,
			'a_style_setting' => false
		);
	}

	/**
	 * Return an instance of the class
	 * 
	 * @return Object
	 */
	public static function get_instance() {
		if ( self::$instance === null )
			self::$instance = new self();
            
        return self::$instance;
	}

	/**
	 * Get the plugin settings
	 * 
	 * @return Array of settings
	 */
	public function get_settings() {
		if ( empty( $this->settings ) )
			$this->init_settings();

		return $this->settings;
	}

	/**
	 * Update the settings
	 * 
	 * @param Array $new_settings
	 */
	public function update_settings( $new_settings ) {
		$this->settings = $new_settings;
		update_option( $this->settings_slug, $new_settings );
	}

	/**
	 * Initializes the plugin settings
	 * 
	 * @since 0.1
	 */
	private function init_settings() {
		$current_settings = get_option( $this->settings_slug );
		$this->settings = wp_parse_args( $current_settings, $this->get_default_settings() );
	}


	/**
	 * Get the settings slug used on DB
	 * 
	 * @return Array Plugin Settings
	 */
	public function get_settings_slug() {
		return $this->settings_slug;
	}




}