<?php

class Multisite_Content_Copier_Network_Permanent_Assignments_Menu extends Multisite_Content_Copier_Admin_Page {

	private $assignment;
	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );

 		add_action( 'admin_init', array( &$this, 'validate_form' ) ); 
 	}

 	public function render_content() {

 		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['assignment'] ) ) {
 			$assignment_id = absint( $_GET['assignment'] );
 			$model = mcc_get_model();
 			$this->assignment = $model->get_permanent_assignment( $assignment_id );

 			if ( ! $this->assignment ) {
 				_e( 'The assignment does not exist', MULTISTE_CC_LANG_DOMAIN );
 				return;
 			}
 			?>
				<form action="post" class="metabox-holder">
					<table class="form-table">
						<?php $this->render_row( __( 'Copy posts', MULTISTE_CC_LANG_DOMAIN ), array( &$this, 'render_copy_posts_row' ) ); ?>
						<?php $this->render_row( __( 'Copy pages', MULTISTE_CC_LANG_DOMAIN ), array( &$this, 'render_copy_pages_row' ) ); ?>
					</table>
				</form>
 			<?php 		

 		}
 		else {
 		?>
			<p>
				<?php _e( 'When you create a Permanent Assignment, the destination blogs will be updated every time the source blog suffers a change.
				This way you do not need to use the wizard every time you create a new post/page', MULTISTE_CC_LANG_DOMAIN );?>
			</p>
	 	<?php

	 		require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-permanent-assigments-list.php' );
	 		$table = new MCC_Permanent_Assignments_List_Table();
	 		$table->prepare_items();
	 		$table->display();
 		}
 		
 	}

 	public function render_copy_posts_row() {
 		$options = mcc_get_post_additional_settings();
 		?>
			<div id="mcc-posts-to-copy" class="postbox">
				<h3 class="hndle">
					<label><input type="checkbox" name="to_copy[]" id="mcc-posts" value="posts"> <?php _e( 'Posts' ); ?></label><br>
				</h3>
				<div class="inside">
					<ul>
						<?php foreach ( $options as $option_slug => $label ): ?>
							<li><label><input type="checkbox" name="post_settings[]" value="<?php echo $option_slug; ?>" > <?php echo $label; ?></label></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
 		<?php
 	}

 	public function render_copy_pages_row() {
 		$options = mcc_get_page_additional_settings();
 		?>
			<div id="mcc-pages-to-copy" class="postbox">
				<h3 class="hndle">
					<label><input type="checkbox" name="to_copy[]" id="mcc-pages" value="pages"> <?php _e( 'Pages' ); ?></label><br>
				</h3>
				<div class="inside">
					<ul>
						<?php foreach ( $options as $option_slug => $label ): ?>
							<li><label><input type="checkbox" name="page_settings[]" value="<?php echo $option_slug; ?>" > <?php echo $label; ?></label></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
 		<?php
 	}

 	public function render_activate_plugins_row() {
 		?>
			<div id="mcc-pages-to-copy" class="postbox">
				<h3 class="hndle">
					<label><input type="checkbox" name="to_copy[]" id="mcc-plugins" value="plugins"> <?php _e( 'Activate plugins' ); ?></label><br>
				</h3>
			</div>
 		<?php
 	}

 	
 	public function validate_form() {
 		
 	}
}