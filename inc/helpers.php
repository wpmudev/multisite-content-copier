<?php

function mcc_get_model() {
	return Multisite_Content_Copier_Model::get_instance();
}

function mcc_get_copier_model() {
	return Multisite_Content_Copier_Copier_Model::get_instance();
}

function mcc_get_settings_handler() {
	return Multisite_Content_Copier_Settings_Handler::get_instance();
}

function mcc_get_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_settings();
}

function mcc_get_settings_slug() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_settings_slug();
}

function mcc_get_default_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_default_settings();
}

function mcc_update_settings( $new_settings ) {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->update_settings( $new_settings );
}

function mcc_add_error( $id, $message ) {
	Multisite_Content_Copier_Errors_Handler::add_error( $id, $message );
}

function mcc_is_error() {
	return Multisite_Content_Copier_Errors_Handler::is_error();
}

function mcc_show_errors() {
	Multisite_Content_Copier_Errors_Handler::show_errors_notice();
}

function mcc_get_queue_for_blog( $blog_id = 0 ) {
	$model = mcc_get_model();

	if ( ! $blog_id )
		$blog_id = get_current_blog_id();

	$results = $model->get_queued_elements_for_blog( $blog_id );

	for ( $i = 0; $i < count( $results ); $i++ ) {
		$results[ $i ]->settings = maybe_unserialize( $results[ $i ]->settings );
	}

	return $results;
}

function mcc_get_groups_dropdown( $selected = '' ) {
	$model = mcc_get_model();
	$groups = $model->get_blogs_groups();
	?>
		<option value=""><?php _e( 'Select a group', MULTISTE_CC_LANG_DOMAIN ); ?></option>
	    <?php foreach ( $groups as $group ): ?>
	    	<option value="<?php echo $group['ID']; ?>"><?php echo $group['group_name']; ?></option>
		<?php endforeach; ?>
	<?php
}

function mcc_get_additional_settings( $type ) {
	return call_user_func( 'mcc_get_' . $type . '_additional_settings' );
}

function mcc_get_post_additional_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_additional_settings( 'post' );
}

function mcc_get_page_additional_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_additional_settings( 'page' );
}

function mcc_get_cpt_additional_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_additional_settings( 'cpt' );
}

function mcc_get_user_additional_settings() {
	$settings_handler = mcc_get_settings_handler();
	return $settings_handler->get_additional_settings( 'user' );
}

function mcc_get_nbt_model() {
	return Multisite_Content_Copier_NBT_Model::get_instance();
}

function mcc_get_nbt_groups_dropdown( $selected = '' ) {
	if ( ! function_exists( 'nbt_get_model' ) )
		$groups = array();
	else {
		$model = nbt_get_model();
		$groups = $model->get_templates();
	}

	?>
		<option value=""><?php _e( 'Select a group', MULTISTE_CC_LANG_DOMAIN ); ?></option>
	    <?php foreach ( $groups as $group ): ?>
	    	<option value="<?php echo $group['ID']; ?>"><?php echo $group['name']; ?></option>
		<?php endforeach; ?>
	<?php
}

function mcc_get_registered_cpts() {
	// Get all post types
	$args = array(
		'publicly_queryable' => true
	); 
	$post_types = get_post_types( $args, 'object' );
	unset( $post_types['attachment'] );
	unset( $post_types['post'] );

	/**
	 * Filters the registered Custom Post TYpes for a given blog
	 * 
	 * @param $post_types Registered Post Types
	 * @param Blog ID
	 */
	return apply_filters( 'mcc_get_registered_cpts', $post_types, get_current_blog_id() );
}


function mcc_get_action_copier_class( $action ) {
	switch ( $action ) {
		case 'add-post':
			$class = 'Multisite_Content_Copier_Post_Copier';
			break;
		case 'add-cpt': {
			$class = 'Multisite_Content_Copier_CPT_Copier';
			break;
		}
		case 'activate-plugin': {
			$class = 'Multisite_Content_Copier_Plugins_Activator';
			break;
		}
		case 'add-user': {
			$class = 'Multisite_Content_Copier_User_Copier';
			break;
		}
		default:
			$class = 'Multisite_Content_Copier_Page_Copier';
			break;
	}
	return $class;
}

function mcc_basic_roles_dropdown( $selected = false ) {
	global $wp_roles;

	$basic_roles_slugs = array( 'administrator', 'author', 'contributor', 'editor', 'subscriber' );

	$selected = $selected && in_array( $selected, $basic_roles_slugs ) ? $selected : 'subscriber';

	$basic_roles = array();
	foreach ( $basic_roles_slugs as $role_slug ) {
		?>
			<option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( $selected, $role_slug ); ?>><?php echo translate_user_role( $wp_roles->role_names[ $role_slug ] ); ?></option>
		<?php
	}

}

function mcc_is_nbt_active() {
	return is_plugin_active_for_network( 'blogtemplates/blogtemplates.php' );
}
