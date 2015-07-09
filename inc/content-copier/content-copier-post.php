<?php

include_once( 'content-copier-post-type.php' );
class Multisite_Content_Copier_Post_Copier extends Multisite_Content_Copier_Post_Type_Copier {

	public function get_default_args() {
		$defaults = parent::get_default_args();
		$defaults['copy_terms'] = false;
		return $defaults;
	}

	public function copy_item( $item_id ) {
        $new_item_id = parent::copy_item( $item_id );

		// Copy terms?
		if ( $this->args['copy_terms'] ) {
			// Copy child terms
			$this->copy_terms( $item_id, $new_item_id );

		}

		return $new_item_id;
	}

}