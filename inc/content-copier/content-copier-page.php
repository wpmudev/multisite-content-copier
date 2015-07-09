<?php

include_once( 'content-copier-post-type.php' );
class Multisite_Content_Copier_Page_Copier extends Multisite_Content_Copier_Post_Type_Copier {	

	public function copy_item( $item_id ) {
		return parent::copy_item( $item_id );
	}

	
}