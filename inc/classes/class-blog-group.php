<?php

class MCC_Blog_Group {

	public $ID = 0;
	public $group_name = '';
	public $group_slug = '';
	public $bcount = 0;

	public static function get_instance( $group ) {
		global $wpdb;

		if ( is_object( $group ) ) {
			$_group = new self( $group );
			$_group = mcc_sanitize_blog_group_fields( $_group );
			return $_group;
		}

		$group = absint( $group );
		if ( ! $group )
			return false;
		
		$model = mcc_get_model();
		$groups_table = $model->blogs_groups_table;


		$_group = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM $groups_table
				WHERE ID = %d
				OR group_slug = %s
				LIMIT 1",
				$group,
				$group
			)
		);	

		if ( ! $_group )
			return false;

		$_group = new self( $_group );

		$_group = mcc_sanitize_blog_group_fields( $_group );

		return $_group;

	}

	public function __construct( $group ) {
		foreach ( get_object_vars( $group ) as $key => $value )		
			$this->$key = $value;
	}

}

	
/**
 * Get a MCC Group by ID or name
 * @param  int|string $group ID or slug of the group
 * @return Object Group object
 */
function mcc_get_blog_group( $group ) {
	return MCC_Blog_Group::get_instance( $group );
}

/**
 * Assign blogs to a MCC Group
 * 
 * @param  int|array $blog_id  A blog ID or an array of blog IDs
 * @param  int $group The group ID or slug of the group
 */
function mcc_assign_blog_to_group( $blog_ids, $group ) {
	global $wpdb;

	if ( ! is_array( $blog_ids ) )
		$blog_ids = array( $blog_ids );

	$group = mcc_get_blog_group( $group );
	if ( ! $group )
		return;

	$model = mcc_get_model();
	$group_relationships_table = $model->blogs_groups_relationship_table;

	foreach ( $blog_ids as $blog_id ) {
		$blog_id = absint( $blog_id );

		if ( ! get_blog_details( $blog_id ) )
			continue;

		$result = $wpdb->insert(
			$group_relationships_table,
			array(
				'blog_group_id' => $group->ID,
				'blog_id' => $blog_id
			),
			array( '%d', '%d' )
		);

		mcc_refresh_blog_group_count( $group );

	}
}

/**
 * Remove blogs to a MCC Group
 * 
 * @param  int|array $blog_id  A blog ID or an array of blog IDs
 * @param  int $group The group ID or slug of the group
 */
function mcc_remove_blog_from_group( $blog_ids, $group ) {
	$model = mcc_get_model();

	if ( ! is_array( $blog_ids ) )
		$blog_ids = array( $blog_ids );

	$group = mcc_get_blog_group( $group );
	if ( ! $group )
		return;

	$group_relationships_table = $model->blogs_groups_relationship_table;

	foreach ( $blog_ids as $blog_id ) {
		global $wpdb;

		$wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM $group_relationships_table
				WHERE blog_id = %d AND blog_group_id = %d",
				$blog_id,
				$group->ID
			)
		);

		mcc_refresh_blog_group_count( $group );
	}
}

function mcc_refresh_blog_group_count( $group ) {
	global $wpdb;

	$group = mcc_get_blog_group( $group );

	if ( ! $group )
		return;

	$model = mcc_get_model();
	$group_relationships_table = $model->blogs_groups_relationship_table;
	$bcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( ID ) FROM $group_relationships_table WHERE blog_group_id = %d ", $group->ID ) );

	mcc_update_blog_group( $group->ID, array( 'bcount' => $bcount ) );
}


/**
 * Creates a new MCC Group
 * 
 * @param  string $group_name The group name
 * 
 * @return integer The group ID
 */
function mcc_insert_blog_group( $group_name ) {
	global $wpdb;

	$model = mcc_get_model();
	$groups_table = $model->blogs_groups_table;

	$wpdb->insert(
		$groups_table,
		array( 
			'group_name' => $group_name,
			'group_slug' => sanitize_title( $group_name ),
			'bcount' => 0
		),
		array( '%s', '%s', '%d' )
	);

	return $wpdb->insert_id;
}

/**
 * Deletes a MCC Blog Group
 * 
 * @param  int|String $group Group ID or Group slug
 */
function mcc_delete_blog_group( $group ) {
	global $wpdb;

	$group = mcc_get_blog_group( $group );
	if ( ! $group )
		return;

	$blog_ids = mcc_get_blog_group_blogs( $group );
	mcc_remove_blog_from_group( $blog_ids, $group );

	$model = mcc_get_model();
	$groups_table = $model->blogs_groups_table;

	$wpdb->query( $wpdb->prepare( "DELETE FROM $groups_table WHERE ID = %d", $group->ID ) );
}

/**
 * Gets the blogs IDs attached to a group
 * 
 * @param  int|String $group Group ID or Group slug
 * @return array        List of Blog IDs
 */
function mcc_get_blog_group_blogs( $group ) {
	global $wpdb;

	$group = mcc_get_blog_group( $group );

	if ( ! $group )
		return array();

	$model = mcc_get_model();
	$group_relationships_table = $model->blogs_groups_relationship_table;

	$results = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $group_relationships_table WHERE blog_group_id = %d", $group->ID ) );

	if ( ! $results )
		return array();

	return array_map( 'absint', $results );
}

/**
 * Get a list of blog groups based on an array of parameters
 * 
 * @param  array  $args
 		blog_id => int Return only groups that are attached to this blog ID
 *
 * @return array       Array of groups
 */
function mcc_get_blog_groups( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'blog_id' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$model = mcc_get_model();
	$groups_table = $model->blogs_groups_table;

	$query = "SELECT bg.ID, bg.group_name, bg.group_slug, bg.bcount FROM $groups_table bg ";

	$blog_id = absint( $blog_id );
	if ( $blog_id ) {
		$group_relationships_table = $model->blogs_groups_relationship_table;
		$query .= $wpdb->prepare(
			"LEFT JOIN $group_relationships_table bgr ON bg.ID = bgr.blog_group_id
			WHERE bgr.blog_id = %d",
			$blog_id
		);	
	}
	
	$results = $wpdb->get_results( $query );

	if ( ! $results )
		return array();

	return array_map( 'mcc_get_blog_group', $results );
}

/**
 * Updates a Blog Group
 * 
 * @param  int|String $group Group ID or Group slug
 * @param  array  $args  Array of fields to update
 * @return Boolean True on success
 */
function mcc_update_blog_group( $group, $args = array() ) {
	global $wpdb;

	$_group = mcc_get_blog_group( $group );
	if ( ! $_group )
		return false;

	$fields = array( 'group_name' => '%s', 'group_slug' => '%s', 'bcount' => '%d' );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( empty( $update ) )
		return false;

	$model = mcc_get_model();
	$groups_table = $model->blogs_groups_table;
	
	$result = $wpdb->update(
		$groups_table,
		$update,
		array( 'ID' => $_group->ID ),
		$update_wildcards,
		array( '%d' )
	);

	if ( ! $result )
		return false;

	return true;
}

function mcc_get_groups_dropdown( $selected = '' ) {
	$groups = mcc_get_blog_groups();
	?>
		<option value=""><?php _e( 'Select a group', MULTISTE_CC_LANG_DOMAIN ); ?></option>
	    <?php foreach ( $groups as $group ): ?>
	    	<option value="<?php echo $group->ID; ?>"><?php echo $group->group_name; ?></option>
		<?php endforeach; ?>
	<?php
}


function mcc_sanitize_blog_group_fields( $group ) {
	$int_fields = array( 'ID', 'bcount' );

	foreach ( get_object_vars( $group ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		$group->$name = $value;
	}

	$group = apply_filters( 'mcc_sanitize_blog_group_fields', $group );

	return $group;
}

