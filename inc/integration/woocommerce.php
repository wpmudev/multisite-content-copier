<?php

add_action( 'mcc_term_copied', 'mcc_woocommerce_copy_term', 10, 3 );
function mcc_woocommerce_copy_term( $src_term_id, $dest_term, $source_blog_id ) {
	if ( class_exists( 'Woocommerce' ) ) {
		$dest_term_id = ! empty( $dest_term['term_id'] ) ? $dest_term['term_id'] : 0;
		if ( function_exists( 'get_woocommerce_term_meta' ) && $dest_term_id ) {
			switch_to_blog( $source_blog_id );
			$display_type  = get_woocommerce_term_meta( $src_term_id, 'display_type' );
			$attachment_id = get_woocommerce_term_meta( $src_term_id, 'thumbnail_id' );
			restore_current_blog();

			if ( $display_type ) {
				update_woocommerce_term_meta( $dest_term_id, 'display_type', $display_type );
			}
			if ( $attachment_id ) {
				$new_attachment_id = Multisite_Content_Copier_Copier::copy_single_image( $source_blog_id, $attachment_id );
				if ( $new_attachment_id ) {
					update_woocommerce_term_meta( $dest_term_id, 'thumbnail_id', $attachment_id );
				}
			}
		}

	}
}

add_action( 'mcc_copy_posts', 'mcc_woocommerce_copy_products_attributes', 10, 2 );
function mcc_woocommerce_copy_products_attributes( $posts, $source_blog_id ) {
	if ( ! class_exists( 'Woocommerce' ) ) {
		return;
	}

	global $wpdb;

	// Little hack for WooCommerce
	register_taxonomy( 'product_type', array() );

	foreach ( $posts as $source_post_id => $new_post_id ) {
		if ( get_post_type( $new_post_id ) != 'product' ) {
			continue;
		}

		$product_id     = $source_post_id;
		$new_product_id = $new_post_id;

		switch_to_blog( $source_blog_id );
		$product            = wc_get_product( $product_id );
		$product_attributes = $product->get_attributes();
		restore_current_blog();

		$new_product_attributes = $product_attributes;

		if ( ! empty( $product_attributes ) ) {
			$attribute_keys  = array_keys( $product_attributes );
			$attribute_total = sizeof( $attribute_keys );

			for ( $i = 0; $i < $attribute_total; $i ++ ) {
				$attribute = $product_attributes[ $attribute_keys[ $i ] ];

				if ( $attribute['is_taxonomy'] ) {

					$taxonomy = $attribute['name'];
					if ( ! taxonomy_exists( $taxonomy ) ) {
						// We need to hack the registered taxonomies temporary
						// if we want to insert terms during the process
						register_taxonomy( $taxonomy, 'product' );
					}

					$taxonomy = wc_sanitize_taxonomy_name( preg_replace( "/^pa\_/", '', $taxonomy ) );

					switch_to_blog( $source_blog_id );
					$source_attribute_taxonomy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $taxonomy ) );
					if ( ! $source_attribute_taxonomy ) {
						$attribute_label = wc_attribute_label( $taxonomy );
					} else {
						$attribute_label = $source_attribute_taxonomy->attribute_label;
					}

					$args            = array(
						'hide_empty' => false
					);
					$attribute_terms = get_terms( array( $attribute['name'] ), $args );
					restore_current_blog();

					// Let's check if the attribute taxonomy exists in the destination blog
					$destination_attribute_taxonomy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $taxonomy ) );
					if ( ! $destination_attribute_taxonomy && $source_attribute_taxonomy ) {
						// It does not exist, let's insert it
						$result = $wpdb->insert(
							$wpdb->prefix . 'woocommerce_attribute_taxonomies',
							array(
								'attribute_name'    => $taxonomy,
								'attribute_label'   => $attribute_label,
								'attribute_type'    => $source_attribute_taxonomy->attribute_type,
								'attribute_orderby' => $source_attribute_taxonomy->attribute_orderby,
								'attribute_public'  => $source_attribute_taxonomy->attribute_public
							),
							array( '%s', '%s', '%s', '%s', '%d' )
						);

						$new_attribute_taxonomy_id = false;
						if ( $result ) {
							$new_attribute_taxonomy_id = $wpdb->insert_id;

							// Insert the terms now
							$new_attribute_product_terms = array();
							foreach ( $attribute_terms as $term ) {
								if ( term_exists( $term->name, $term->taxonomy ) ) {
									$new_term = get_term_by( 'name', $term->name, $term->taxonomy, ARRAY_A );
								} else {
									$new_term = wp_insert_term( $term->name, $term->taxonomy, array( 'slug' => $term->slug ) );
								}

								if ( ! empty( $new_term ) && ! is_wp_error( $new_term ) ) {
									$new_attribute_product_terms[] = $new_term['term_id'];
								}


							}

							// And assign them to the new product
							wp_set_post_terms( $new_product_id, $new_attribute_product_terms, $attribute['name'], true );

						}

						if ( $new_attribute_taxonomy_id ) {
							$new_product_attributes[ $attribute_keys[ $i ] ] = $attribute;
						}
					} elseif ( $destination_attribute_taxonomy && $source_attribute_taxonomy ) {
						$new_attribute_product_terms = array();
						foreach ( $attribute_terms as $term ) {
							if ( term_exists( $term->name, $term->taxonomy ) ) {
								$new_term = get_term_by( 'name', $term->name, $term->taxonomy, ARRAY_A );
							} else {
								$new_term = wp_insert_term( $term->name, $term->taxonomy, array( 'slug' => $term->slug ) );
							}

							if ( ! empty( $new_term ) && ! is_wp_error( $new_term ) ) {
								$new_attribute_product_terms[] = $new_term['term_id'];
							}

							// And assign them to the new product
							wp_set_post_terms( $new_product_id, $new_attribute_product_terms, $attribute['name'], true );

						}

					} else {
						// It does exist, let's add it to our new product attributes
						$new_product_attributes[ $attribute_keys[ $i ] ] = $attribute;
					}
				} else {
					// Is not taxonomy, just add it as a product attribute
					$new_product_attributes[ $attribute_keys[ $i ] ] = $attribute;
				}

			}

			delete_transient( 'wc_attribute_taxonomies' );
			update_post_meta( $new_product_id, '_product_attributes', $new_product_attributes );
		}
	}
}

add_action( 'mcc_copy_attachment', 'mcc_woocommerce_remap_product_gallery', 10, 4 );
function mcc_woocommerce_remap_product_gallery( $new_attachment_id, $source_attachment_id, $new_post_id, $source_post_id ) {
	if ( ! class_exists( 'Woocommerce' ) ) {
		return;
	}

	if ( get_post_type( $new_post_id ) != 'product' )
		return;

	// Little hack for WooCommerce
	register_taxonomy( 'product_type', array() );

	$new_product = wc_get_product( $new_post_id );
	if ( ! $new_product )
		return;

	$gallery_ids = $new_product->get_gallery_attachment_ids();
	$found_keys = array_keys( $gallery_ids, $source_attachment_id );
	if ( ! empty( $found_keys ) ) {
		foreach ( $found_keys as $key ) {
			$gallery_ids[ $key ] = $new_attachment_id;
		}

		update_post_meta( $new_post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
	}

}
