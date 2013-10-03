<?php

/**
 * A Helper function to handle errors
 * 
 * I think that WP Error Class is not enough sometimes
 * This is an easy to use one
 * 
 */

// TODO: Change the class name
class Multisite_Content_Copier_Errors_Handler {

	private static $errors = array();

	/**
	 * Show an exception
	 * 
	 * Just recommended for exceptions when building the plugin
	 * 
	 * @param String $message Error message
	 */
	public static function show_exception( $message ) {

		if ( ( ! is_multisite() && current_user_can( 'manage_options' ) ) || ( is_multisite() && is_super_admin() ) ) {
			$plugin_data = get_plugin_data( MULTISTE_CC_PLUGIN_FILE_DIR );
			?>
				<div class="origin-plugin-exception" style="padding:20px 10px; margin:20px; background:#ACACAC">
					<p><?php printf( __(  '<b>%s plugin error</b>: %s', MULTISTE_CC_LANG_DOMAIN ), $plugin_data['Name'], $message ); ?>
				</div>
			<?php
		}
		
	}

	/**
	 * Add a new error
	 * 
	 * @param String $id Error ID
	 * @param String $message  Error message
	 */
	public static function add_error( $id, $message ) {
		self::$errors[ $id ] = $message;
	}

	/**
	 * Remove an error from the errors list
	 * 
	 * @param String $id Error ID
	 */
	public static function remove_error( $id ) {
		unset( self::$errors[ $id ] );
	}

	/**
	 * Removes all errors
	 */
	public static function reset_errors() {
		self::$errors = array();
	}

	/**
	 * Checks if there is an error saved
	 * 
	 * @return Boolean. True if there are errors
	 */
	public static function is_error() {
		return ! empty( self::$errors );
	}

	/**
	 * Get the errors list
	 * 
	 * @return Array Errors list
	 */
	public static function get_errors() {
		return self::$errors;
	}

	/**
	 * Get an error or the first one if an ID is not provided or it does not exist
	 * 
	 * @param String $id Error ID
	 * 
	 * @return String Error Message.
	 */
	public static function get_error( $id = false ) {
		$errors = self::$errors;

		if ( empty( $id ) || ! isset( $errors[ $id ] ) )
			return array_shift( $errors );
		else 
			return $errors[ $id ];
	}

	/**
	 * Shows an updated notice
	 * 
	 * @param String $message Notice message
	 */
	public static function show_updated_notice( $message ) {
		?>
			<div class="updated"><p><?php echo $message; ?></p></div>
		<?php
	}

	/**
	 * Shows a unique error message
	 * 
	 * @param String $message Error message
	 */
	public static function show_error_notice( $message ) {
		?>
			<div class="error"><p><?php echo $message; ?></p></div>
		<?php
	}

	/**
	 * Show the errors list
	 * 
	 * @param Array $errors. A list of errors. If not provided, the function will get
	 * the current errors list
	 */
	public static function show_errors_notice( $errors = false ) {
		if ( self::is_error() )
			$the_errors = self::$errors;
		elseif (  is_array( $errors ) && ! empty( $errors ) )
			$the_errors = $errors;
		else
			return;

		?>
			<div class="error"><ul>
				<?php foreach ( $the_errors as $error ): ?>
					<li style="list-style;none"><?php echo $error; ?></li>
				<?php endforeach; ?>
			</ul></div>
		<?php
	}

}

