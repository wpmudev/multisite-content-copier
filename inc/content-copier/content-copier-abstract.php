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

}