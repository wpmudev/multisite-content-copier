<?php

class Multisite_Content_Copier_Network_Blogs_Groups_Menu extends Multisite_Content_Copier_Admin_Page {
	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );

 		add_action( 'admin_init', array( &$this, 'validate_form' ) ); 
 	}

 	public function render_content() {

 		if ( 'groups' == $this->get_current_tab() ) {
 			if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['group'] ) ) {
 				$this->render_group_edit_screen();
 			}
 			else {
 				$this->render_groups_screen();
 			}
 		}

 		if ( 'sites' == $this->get_current_tab() ) {
 			require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-blogs-list.php' );
	 		$wp_list_table = new MCC_Sites_List_Table();
	 		$wp_list_table->prepare_items();
	 		?>
			<form id="mcc-blogs-groups-search-form" method="post">
				<?php $wp_list_table->search_box( __( 'Search sites', MULTISTE_CC_LANG_DOMAIN ), 'blog-search' ); ?>
			</form>
			
			<?php
 			?><form id="mcc-blogs-groups-table-form" method="post"><?php
 			
			
			$wp_list_table->display();
			?></form><?php
 		}

 		if ( 'nbt' == $this->get_current_tab() ) {
 			$settings = mcc_get_settings();
 			if ( ! $settings['blog_templates_integration'] )
 				return;

 			require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-nbt-groups-list.php' );
 			$wp_list_table = new MCC_NBT_Groups_List_Table();
 			$wp_list_table->prepare_items();
 			?>
 			<br/>
			<form action="<?php echo esc_url( add_query_arg( 'tab', 'nbt', $this->get_permalink() ) ); ?>" method="post" id="template-search">
				<?php $wp_list_table->search_box( __( 'Search by template name', MULTISTE_CC_LANG_DOMAIN ), 'template' ); ?>
				<input type="hidden" name="action" value="templates" />
			</form>

			<?php
				
			$wp_list_table->display();

 		}
 		
 	}

 	public function add_help_tabs() {
 		$screen = get_current_screen();

        $screen->add_help_tab( array(
	        'id'	=> 'mcc_groups_help_tab',
	        'title'	=> __( 'Site Groups', MULTISTE_CC_LANG_DOMAIN ),
	        'content'	=> '<h4>' . __( 'What\'s a Site Group?', MULTISTE_CC_LANG_DOMAIN ) . '</h4>',
	    ) );
 	}

 	public function render_group_edit_screen() {

 		$group_id = absint( $_GET['group'] );
 		$model = mcc_get_model();
 		$group = $model->get_blog_group( $group_id );

 		if ( empty( $group ) ) {
 			?>
				<p><?php _e( 'The Group does not exist', MULTISTE_CC_LANG_DOMAIN ); ?></p>
 			<?php
 		}
 		else {
 			if ( isset( $_GET['updated'] ) ) {
	 			?>
					<div class="updated"><p><?php _e( 'The Group has been updated.', MULTISTE_CC_LANG_DOMAIN ); ?> <a href="<?php echo esc_url( $this->get_permalink() ); ?>"><?php _e( 'Back to Groups list', MULTISTE_CC_LANG_DOMAIN ); ?></a></p></div>
	 			<?php
	 		}
	 		elseif ( mcc_is_error() ) {
	 			mcc_show_errors();
	 		}
 			?>
 				<form action="" method="post">
					<table class="form-table">
						<?php $this->render_row( __( 'Group name', MULTISTE_CC_LANG_DOMAIN ), array( &$this, 'render_group_name_field' ) ); ?>
					</table>
					<p class="submit">
						<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
						<?php wp_nonce_field( 'edit-mcc-group', '_wpnonce' ); ?>
						<?php submit_button( __( 'Save Changes', MULTISTE_CC_LANG_DOMAIN ), 'primary', 'submit_edit_group', false ); ?>
						<a href="<?php echo esc_url( $this->get_permalink() ); ?>" class="button-secondary"><?php _e( 'Cancel', MULTISTE_CC_LANG_DOMAIN ); ?></a>
					</p>
				</form>
	 		<?php
 		}

 		
 	}

 	public function render_group_name_field() {
 		$group_id = absint( $_GET['group'] );
 		$model = mcc_get_model();
 		$group = $model->get_blog_group( $group_id );
 		?>
			<input type="text" name="group_name" value="<?php echo esc_attr( $group->group_name ); ?>">
 		<?php
 	}

 	public function render_groups_screen() {
 		require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-groups-list.php' );
 		$groups_table = new MCC_Groups_List_Table();
 		$groups_table->prepare_items();

 		$group_name = '';


 		if ( isset( $_GET['added'] ) ) {
 			?>
				<div class="updated"><p><?php _e( 'The Group has been added', MULTISTE_CC_LANG_DOMAIN ); ?></p></div>
 			<?php
 		}
 		elseif ( mcc_is_error() ) {
 			mcc_show_errors();
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
							<h3><?php _e( 'Add new Group', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
							<form id="mcc-groups-table-form" action="" method="post">
								<?php wp_nonce_field( 'add-mcc-group' ); ?>
								<div class="form-field">
									<label for="group_name"><?php _e( 'Group Name', MULTISTE_CC_LANG_DOMAIN ); ?></label>
									<input name="group_name" id="group_name" type="text" value="<?php echo $group_name; ?>" size="40" aria-required="true"><br/>
								</div>
								<p class="submit"><input type="submit" name="submit_new_group" id="submit_new_group" class="button button-primary" value="<?php _e( 'Add New Group', MULTISTE_CC_LANG_DOMAIN ); ?>"></p>
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

 			if ( empty( $group_name ) )
 				mcc_add_error( 'empty-group-name', __( 'Group name cannot be empty', MULTISTE_CC_LANG_DOMAIN ) );

 			if ( ! mcc_is_error() ) {
	 			$model = mcc_get_model();
	 			$model->add_new_blog_group( $group_name );

	 			wp_redirect( add_query_arg( 'added', 'true', $this->get_permalink() ) );
	 			exit;
	 		}
 		}

 		if ( isset( $_POST['submit_edit_group'] ) ) {

 			if ( ! check_admin_referer( 'edit-mcc-group' ) )
 				return;

 			$group_id = absint( $_POST['group_id'] );
 			$group_name = stripslashes_deep( $_POST['group_name'] );

 			if ( empty( $group_name ) )
 				mcc_add_error( 'empty-group-name', __( 'Group name cannot be empty', MULTISTE_CC_LANG_DOMAIN ) );

 			
 			if ( ! mcc_is_error() ) {
	 			$model = mcc_get_model();

	 			$args = array(
	 				'group_name' => stripslashes_deep( $_POST['group_name'] )
	 			);
	 			$model->update_group( $group_id, $args );

	 			wp_redirect( 
	 				add_query_arg( 
	 					array( 
	 						'updated' => 'true', 
	 						'action' => 'edit',
	 						'group' => $group_id
	 					),
	 					$this->get_permalink() 
	 				) 
	 			);
	 			exit;
	 		}

 		}
 	}
}