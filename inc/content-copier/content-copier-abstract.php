<?php

abstract class Multisite_Content_Copier_Abstract {
	protected $orig_blog_id;
	protected $dest_blog_id;
	protected $args;
	protected $items;

	public function __construct( $orig_blog_id, $items, $args ) {
		$this->args = wp_parse_args( $args, $this->get_default_args() );

		$this->orig_blog_id = absint( $orig_blog_id );
		$this->items = $items;
	}

	abstract public function get_default_args();
	abstract public function execute();
	abstract public function copy_item( $item_id );

	protected function log( $message ) {
		if ( defined( 'MCC_DEBUG' ) && MCC_DEBUG ) {
			$file = trailingslashit( MULTISITE_CC_LOG_DIR ) . 'mcc.log';
			if ( ! is_string( $message ) )
				$message = print_r( $message, true );

			if ( $file_handle = @fopen( $file, 'a' ) ) {
				fwrite( $file_handle, sprintf('[%s] ==> %s', date( "Y/m/d h:i:s", time() ), $message ) . PHP_EOL );
				fclose( $file_handle );
			}
		}
	}

}