<?php

class MCC_CoursePress_Integration {

	public $units_copied = array();

	public function __construct() {
		add_action( 'mcc_copy_posts', array( $this, 'maybe_copy_units' ), 10, 3 );
		add_filter( 'mcc_get_registered_cpts', array( $this, 'get_registered_cpts' ) );
	}

	public function get_registered_cpts( $post_types ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'coursepress/coursepress.php' ) ) {
			if ( isset( $post_types['course'] ) ) {
				$post_types['course']->label = __( 'Courses and Units', MULTISTE_CC_LANG_DOMAIN );
				unset( $post_types['unit'] );
			}
		}
		return $post_types;
	}

	function maybe_copy_units( $copied_posts, $source_blog_id, $args ) {
		if ( ! class_exists( 'CoursePress' ) )
			return;

		$this->copied_posts = $copied_posts;

		$courses = array();
		switch_to_blog( $source_blog_id );
		foreach ( $copied_posts as $source_post_id => $new_post_id ) {

			if ( 'course' === get_post_type( $source_post_id ) ) {
				try {
					$source_course = new Course( $source_post_id );
					if ( ! is_object( $source_course ) )
						throw new Exception( "Error Getting source course" );
						
				}
				catch ( Exception $e ) {
					continue;
				}

				$courses[ $source_post_id ] = array(
					'units' => wp_list_pluck( $source_course->get_units(), 'ID' ),
					'modules' => array()
				);

				// Get the modules for each unit
				foreach ( $courses[ $source_post_id]['units'] as $unit_id ) {
					$courses[ $source_post_id]['modules'][ $unit_id ] = wp_list_pluck( Unit_Module::get_modules( $unit_id ), 'ID' );
				}
				
				
			}
		}
		restore_current_blog();

		foreach ( $courses as $source_course_id => $course_atts ) {
			if ( ! empty( $course_atts['units'] ) ) {
				$args['copy_parents'] = false;
				$copier = mcc_get_copier( 'post', $source_blog_id, $course_atts['units'], $args );

				remove_action( 'mcc_copy_posts', array( $this, 'maybe_copy_units' ), 10, 3 );
				add_filter( 'mcc_copy_post_meta', array( $this, 'remap_unit_meta' ), 10, 5 );
				$copier->execute();
				remove_filter( 'mcc_copy_post_meta', array( $this, 'remap_unit_meta' ), 10, 5 );

				foreach ( $course_atts['modules'] as $unit_id => $modules_ids ) {
					$copier = mcc_get_copier( 'post', $source_blog_id, $modules_ids, $args );
					add_action( 'mcc_copy_posts', array( $this, 'remap_module_data' ), 10, 3 );
					$copier->execute();
					remove_action( 'mcc_copy_posts', array( $this, 'remap_module_data' ), 10, 3 );
				}

				add_action( 'mcc_copy_posts', array( $this, 'maybe_copy_units' ), 10, 3 );
			}
			
		}
		
	}

	public function remap_unit_meta( $source_blog_id, $unit_id, $new_unit_id, $meta_key, $meta_value ) {
		if ( ! $new_unit_id )
			return;

		$new_unit = get_post( $new_unit_id );
		if ( ! $new_unit )
			return;

		switch_to_blog( $source_blog_id );
		$source_unit = get_post( $unit_id );
		restore_current_blog();

		if ( ! is_object( $source_unit ) )
			return;
		
		if ( 'course_id' === $meta_key && isset( $this->copied_posts[ $source_unit->post_parent ] ) ) {
			remove_filter('content_save_pre', 'wp_filter_post_kses');
			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
			update_post_meta( $new_unit_id, 'course_id', $this->copied_posts[ $source_unit->post_parent ] );
			wp_update_post( array( 'post_parent' => $this->copied_posts[ $source_unit->post_parent ], 'ID' => $new_unit_id ) );
			add_filter('content_save_pre', 'wp_filter_post_kses');
			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		}

		$this->units_copied[ $unit_id ] = $new_unit_id;
	}

	public function remap_module_data( $posts_created, $src_blog_id, $args ) {
		foreach ( $posts_created as $src_module_id => $new_module_id ) {
			$src_module = get_blog_post( $src_blog_id, $src_module_id );
			if ( ! is_object( $src_module ) )
				continue;

			if ( ! isset( $this->units_copied[ $src_module->post_parent ] ) )
				continue;
			remove_filter('content_save_pre', 'wp_filter_post_kses');
			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
			wp_update_post( array( 'post_parent' => $this->units_copied[ $src_module->post_parent ], 'ID' => $new_module_id ) );
			add_filter('content_save_pre', 'wp_filter_post_kses');
			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		}
	}
}

new MCC_CoursePress_Integration();