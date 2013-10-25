<?php

class Multisite_Content_Copier_Network_Blogs_Groups_Menu extends Multisite_Content_Copier_Admin_Page {
	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );

 		add_action( 'admin_init', array( &$this, 'validate_form' ) ); 
 	}

 	public function render_content() {

 		if ( 'groups' == $this->get_current_tab() ) {
 			$this->render_groups_screen();
 		}

 		if ( 'sites' == $this->get_current_tab() ) {
 			?><form method="post"><?php
 			require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-blogs-list.php' );
	 		$wp_list_table = new MCC_Sites_List_Table();
			$wp_list_table->prepare_items();
			$wp_list_table->display();
			?></form><?php
 		}

 		
 	}

 	public function render_groups_screen() {
 		require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-groups-list.php' );
 		$groups_table = new MCC_Groups_List_Table();
 		$groups_table->prepare_items();

 		$group_name = '';


 		if ( isset( $_GET['added'] ) ) {
 			?>
				<div class="updated"><p><?php _e( 'The group has been added', MULTISTE_CC_ADMIN_DIR ); ?></p></div>
 			<?php
 		}

 		?>
	    	<br class="clear">
			<div id="col-container">
				<div id="col-right">
					<div class="col-wrap">
						<div class="form-wrap">
							<form id="mcc-groups-table-form" action="" method="post">
								<?php $groups_table->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h3><?php _e( 'Add new group', MULTISTE_CC_ADMIN_DIR ); ?></h3>
							<form id="mcc-groups-table-form" action="" method="post">
								<?php wp_nonce_field( 'add-mcc-group' ); ?>
								<div class="form-field">
									<label for="group_name"><?php _e( 'Group Name', MULTISTE_CC_ADMIN_DIR ); ?></label>
									<input name="group_name" id="group_name" type="text" value="<?php echo $group_name; ?>" size="40" aria-required="true"><br/>
								</div>
								<p class="submit"><input type="submit" name="submit_new_group" id="submit_new_group" class="button button-primary" value="<?php _e( 'Add New Group', MULTISTE_CC_ADMIN_DIR ); ?>"></p>
							</form>
						</div>
					</div>
				</div>
			</div>
	    <?php
 	}

 	public function validate_form() {
 		if ( isset( $_POST['submit_new_group'] ) ) {

 			if ( ! check_admin_referer( 'add-mcc-group' ) )
 				return;

 			$group_name = stripslashes_deep( $_POST['group_name'] );

 			$model = mcc_get_model();
 			$model->add_new_blog_group( $group_name );

 			wp_redirect( add_query_arg( 'added', 'true', $this->get_permalink() ) );
 		}
 	}
}