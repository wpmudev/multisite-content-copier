<?php

add_action( 'mcc_term_copied', 'mcc_woocommerce_copy_term', 10, 3 );
function mcc_woocommerce_copy_term( $src_term_id, $dest_term, $source_blog_id  ) {
	if ( class_exists( 'Woocommerce' ) ) {
		$dest_term_id = ! empty( $dest_term['term_id'] ) ? $dest_term['term_id'] : 0;
		if ( function_exists( 'get_woocommerce_term_meta' ) && $dest_term_id ) {
			switch_to_blog( $source_blog_id);
			$display_type = get_woocommerce_term_meta( $src_term_id, 'display_type' );
			$attachment_id = get_woocommerce_term_meta( $src_term_id, 'thumbnail_id' );
			restore_current_blog();

			if ( $display_type ) {
				update_woocommerce_term_meta( $dest_term_id, 'display_type', $display_type );
			}
			if ( $attachment_id ) {
				$new_attachment_id = Multisite_Content_Copier_Copier::copy_single_image( $source_blog_id, $attachment_id );
				if ( $new_attachment_id )
					update_woocommerce_term_meta( $dest_term_id, 'thumbnail_id', $attachment_id );
			}
		}
		
	}
}
