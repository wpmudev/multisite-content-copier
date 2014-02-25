<?php

include( 'content-copier-post-type.php' );
class Multisite_Content_Copier_Post_Copier extends Multisite_Content_Copier_Post_Type_Copier {

	public function get_default_args() {
		$defaults = parent::get_default_args();
		$defaults['copy_terms'] = false;
		return $defaults;
	}

	public function copy_item( $item_id ) {
		$results = parent::copy_item( $item_id );

		$new_item_id = $results['new_post_id'];
		$new_parent_item_id = $results['new_parent_post_id'];

		// Copy terms?
		if ( $this->args['copy_terms'] ) {
			
			if ( absint( $new_parent_item_id ) ) {
				// Copy parents terms
				$parent_post_id = $this->get_orig_post_parent( $item_id );
				if ( $parent_post_id )
					$this->copy_terms( $parent_post_id, $new_parent_item_id );	
			}

			// Copy child terms 
			$this->copy_terms( $item_id, $new_item_id );
			
		}

		return $results;
	}

}