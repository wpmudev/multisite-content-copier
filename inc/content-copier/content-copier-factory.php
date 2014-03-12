<?php

class Multisite_Content_Copier_Factory {

	public static function get_copier( $type, $orig_blog_id, $items, $args = array() ) {
		$filename = MULTISTE_CC_INCLUDES_DIR . 'content-copier/content-copier-' . strtolower( $type ) . '.php';
		
		if ( ! is_file( $filename ) )
			return false;

		$classname = 'Multisite_Content_Copier_' . ucfirst( strtolower( $type ) ) . '_Copier';

		include_once( MULTISTE_CC_INCLUDES_DIR . 'content-copier/content-copier-abstract.php' );
		include_once( $filename );

		if ( ! class_exists( $classname ) )
			return false;

		return new $classname( $orig_blog_id, $items, $args );
	}

}