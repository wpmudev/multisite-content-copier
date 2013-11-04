<?php

require_once( MULTISTE_CC_INCLUDES_DIR . 'wizard.php' );


class Multisite_Content_Copier_Network_Main_Menu extends Multisite_Content_Copier_Admin_Page {
 	
 	private $wizard;

 	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );

 		add_action( 'admin_init', array( &$this, 'init_wizard' ), 10 );
 		add_action( 'admin_init', array( &$this, 'validate_form' ) );
 		

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );

       
        add_action( 'wp_ajax_mcc_retrieve_single_post_data', array( &$this, 'retrieve_single_post_data' ) );
        add_action( 'wp_ajax_mcc_retrieve_single_blog_data', array( &$this, 'retrieve_single_blog_data' ) );
 	}


 	public function init_wizard() {
 		$this->wizard = new MCC_Wizard(
 			array( '1','2','3','4','5', '6' ),
 			$this->get_permalink()
 		);


 		if ( isset( $_GET['mcc_action'] ) && 'mcc_submit_metabox' == $_GET['mcc_action'] && wp_verify_nonce( $_GET['_wpnonce'], 'mcc_submit_meta_box' ) ) {
 			// The user has submitted the meta box in the post editor

 			$content_blog_id = absint( $_GET['content_blog'] );
 			if ( ! get_blog_details( $content_blog_id ) )
 				return false;

 			$src_post_id = absint( $_GET['post_id'] );
 			if ( ! $post = get_blog_post( $content_blog_id, $src_post_id ) )
 				return false;

 			// Post type
 			if ( 'post' == $post->post_type ) {
 				$action = 'add-post';
 				$additional_options = mcc_get_post_additional_settings();
 			}
 			elseif ( 'page' == $post->post_type ) {
 				$action = 'add-page';
 				$additional_options = mcc_get_page_additional_settings();
 			}
 			else {
 				return false;
 			}

 			// Additional settings
 			$settings = array();
 			foreach ( $additional_options as $option_slug => $label ) {
 				if ( isset( $_GET[ $option_slug ] ) )
 					$settings[ $option_slug ] = true;
 			}

 			// Group or all?
 			if ( ! in_array( $_GET['dest_blog_type'], array( 'group', 'all' ) ) )
 				return false;

 			/// Resetting the wizard and redirecting
 			$this->wizard->clean();
 			$this->wizard = new MCC_Wizard(
	 			array( '1','2','3','4','5', '6' ),
	 			$this->get_permalink()
	 		);

 			$dest_blog_type = $_GET['dest_blog_type'];
 			$model = mcc_get_model();
 			if ( $dest_blog_type == 'group' && ! $model->is_group( absint( $_GET['group'] ) ) ) {
				return false;
			}
			elseif ( $dest_blog_type == 'group' ) {
				// If is a group, we'll need the blogs IDs
				$group =  absint( $_GET['group'] );
				$blogs = $model->get_blogs_from_group( $group );

				if ( empty( $blogs ) )
					return false;

				$ids = array();
				foreach ( $blogs as $blog ) {
					$ids[] = $blog->blog_id;
				}

				$this->wizard->set_value( 'dest_blogs_ids', $ids );
			}
			
			

 			$this->wizard->set_value( 'mcc_action', $action );
 			$this->wizard->set_value( 'content_blog_id', $content_blog_id );
 			$this->wizard->set_value( 'dest_blog_type', $dest_blog_type );
 			$this->wizard->set_value( 'settings', $settings );
 			$this->wizard->set_value( 'posts_ids', array( $src_post_id ) );

 			$this->wizard->go_to_step( '5' );

 		}
 	}

 	

 	public function add_javascript() {
 		if ( get_current_screen()->id == $this->page_id . '-network' ) {

 			if ( 2 == $this->wizard->get_current_step() || 3 == $this->wizard->get_current_step() || 4 == $this->wizard->get_current_step() ) {
	    		wp_enqueue_script( 'mcc-wizard-js', MULTISTE_CC_ASSETS_URL . 'js/wizard.js', array( 'jquery' ) );
	    		wp_enqueue_script( 'mcc-autocomplete', MULTISTE_CC_ASSETS_URL . 'js/autocomplete.js', array( 'jquery' ) );
	    		wp_enqueue_script( 'jquery-ui-autocomplete' );
	 			wp_enqueue_style( 'mcc-jquery-ui-styles', MULTISTE_CC_ASSETS_URL . 'css/jquery-ui.css' );
	 		}
	 		if ( 5 == $this->wizard->get_current_step() ) {
	 			wp_enqueue_script( 'jquery-ui-progressbar', MULTISTE_CC_ASSETS_URL . 'jquery-ui/jquery.ui.progressbar.js', array( 'jquery-ui-core', 'jquery-ui-widget' ) );
	 		}
    	}
 	}

 	public function add_styles() {
 		if ( get_current_screen()->id == $this->page_id . '-network' ) {
 			wp_enqueue_style( 'mcc-wizard-css', MULTISTE_CC_ASSETS_URL . 'css/wizard.css' );

 			if ( 5 == $this->wizard->get_current_step() ) {
				wp_enqueue_style( 'jquery-ui-batchcreate', MULTISTE_CC_ASSETS_URL . 'jquery-ui/jquery-ui-1.10.3.custom.min.css', array() );
	 		}
    	}

 	}


	

	public function retrieve_single_post_data() {
		$blog_id = absint( $_POST['blog_id'] );
		$post_id = absint( $_POST['post_id'] );

		$post = get_blog_post( $blog_id, $post_id );

		$returning = '';
		if ( ! empty( $post ) ) {
			$returning .= $this->get_row_list( 'post', $post->ID, $post->post_title );
		}

		echo $returning;

		die();
			
	}

	public function retrieve_single_blog_data() {
		$blog_id = absint( $_POST['blog_id'] );

		$details = get_blog_details( $blog_id );

		$returning = '';
		if ( ! empty( $details ) ) {
			$returning .= $this->get_row_list( 'blog', $blog_id, $details->blogname );
		}

		echo $returning;

		die();

	}
	


	public function render_content() {
		$step = $this->wizard->get_current_step();
		$this->render_before_step();

		call_user_func( array( &$this, 'render_step_' . $step ) );
		$this->render_after_step();
	}

	public function render_before_step() {
		mcc_show_errors();
		?>
			<div class="welcome-panel">
				<div class="welcome-panel-content">
		<?php
	}

	public function render_after_step() {
		?>
				</div>
			</div>
		<?php
	}


	private function render_step_1() {

		$current_action = $this->wizard->get_value( 'mcc_action' );
		?>
			<h3><?php _e( 'Welcome to Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<p class="about-description">
				<?php _e( 'Please, select the option', MULTISTE_CC_LANG_DOMAIN ); ?>
			</p>
			<form action="" method="post" name="wizardform" id="wizardform">
				<?php wp_nonce_field( 'step_1' ); ?>
				<ul class="wizardoptions">
					<li><label><input type="radio" name="mcc_action" value="add-page" <?php checked( $current_action == 'add-page' || empty( $current_action ) ); ?>> <?php _e( 'Copy pages', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
					<li><label><input type="radio" name="mcc_action" value="add-post" <?php checked( $current_action == 'add-post' ); ?>> <?php _e( 'Copy posts', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
					<li><label><input type="radio" name="mcc_action" value="activate-plugin" <?php checked( $current_action == 'activate-plugin' ); ?>> <?php _e( 'Activate plugins', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
				</ul>
				<p class="submit">
					<input type="submit" name="submit_step_1" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php
	}

	private function render_step_2() {
		$current_action = $this->wizard->get_value( 'mcc_action' );
		?>
			<form action="" method="post">
				<?php wp_nonce_field( 'step_2' ); ?>
				<?php
					switch ( $current_action ) {
						case 'add-page': 
						case 'add-post': {
							$this->render_blog_selector( $current_action );
							break;
						}
						
					}
				?>
				<p class="submit">
					<input type="submit" name="submit_step_2" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php		
	}

	private function render_blog_selector( $current_action ) {
		?>
			<?php if ( 'add-page' == $current_action ): ?>
				<h3><?php _e( 'Select the blog where the page is', MULTISTE_CC_LANG_DOMAIN ); ?></h3><br/>
			<?php elseif( 'add-post' == $current_action ): ?>
				<h3><?php _e( 'Select the blog where the post is', MULTISTE_CC_LANG_DOMAIN ); ?></h3><br/>
			<?php endif; ?>
			<input name="blog_id" type="text" id="blog_id" class="small-text" placeholder="<?php _e( 'Blog ID', MULTISTE_CC_LANG_DOMAIN ); ?>"/>
            <div style="display:inline-block"class="ui-widget">
                <label for="search_for_blog"> <?php _e( 'Or search by blog path', MULTISTE_CC_LANG_DOMAIN ); ?> 
					<input type="text" id="autocomplete"  data-type="sites" class="medium-text">
					<span class="description"><?php _e( 'For example, if the blog you are searching has an URL like http://ablog.mydomain.com, you can type "ablog"', MULTISTE_CC_LANG_DOMAIN ); ?></span>
                </label>
            </div>

		<?php
	}

	private function render_step_3() {
		$current_action = $this->wizard->get_value( 'mcc_action' );
		$content_blog_id = $this->wizard->get_value( 'content_blog_id' );
		?>
			<form action="" method="post">
				<?php wp_nonce_field( 'step_3' ); ?>
				<?php
					switch ( $current_action ) {
						case 'add-page': {
							$this->render_page_selector( $content_blog_id );
							break;
						}
						case 'add-post': {
							$this->render_post_selector( $content_blog_id );
							break;
						}
						case 'activate-plugin': {
							$this->render_plugin_selector();
							break;
						}

					}
				?>
				<p class="submit">
					<input type="submit" name="submit_step_3" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php	
	}

	private function render_page_selector( $blog_id ) {
		switch_to_blog( $blog_id );
		$pages = get_pages();
		restore_current_blog();

		$current_selected_pages = $this->wizard->get_value( 'posts_ids' );
		if ( ! is_array( $current_selected_pages ) )
			$current_selected_pages = array();

		$current_selected_settings = $this->wizard->get_value( 'settings' );
		if ( ! is_array( $current_selected_settings ) )
			$current_selected_settings = array();
		?>
			<h3><?php _e( 'Choose the pages that you want to add', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<ul>
				<?php foreach ( $pages as $page ): ?>
					<li><label><input type="checkbox" name="pages[]" value="<?php echo $page->ID; ?>" <?php checked( in_array( $page->ID, $current_selected_pages ) ); ?>> <?php echo $page->post_title; ?></label></li>
				<?php endforeach; ?>
			</ul>
			<hr/>
			<h3><?php _e( 'Additional options', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<?php $options = mcc_get_page_additional_settings(); ?>
			<ul>
				<?php foreach( $options as $option_slug => $label ): ?>
					<li><label><input type="checkbox" name="settings[<?php echo $option_slug; ?>]" <?php checked( array_key_exists( $option_slug, $current_selected_settings ) ); ?>> <?php echo $label; ?></label></li>
				<?php endforeach; ?>
			</ul>

		<?php

	} 

	private function render_post_selector( $blog_id ) {

		$current_selected_settings = $this->wizard->get_value( 'settings' );
		if ( ! is_array( $current_selected_settings ) )
			$current_selected_settings = array();

		$current_posts_ids = $this->wizard->get_value( 'posts_ids' );
		$posts_list = '';
		if ( ! is_array( $current_posts_ids ) ) {
			$current_posts_ids = array();
		}
		else {
			foreach ( $current_posts_ids as $post_id ) {
				$post = get_blog_post( $this->wizard->get_value( 'content_blog_id' ), $post_id );
				$posts_list .= $this->get_post_row( $post->ID, $post->post_title );
			}
		}

		?>
			<h3><?php _e( 'Add posts by ID or search by name', MULTISTE_CC_LANG_DOMAIN ); ?></h3><br/>
			<input name="post_id" type="text" id="post_id" class="small-text" style="float: left; margin-right: 10px;" placeholder="<?php _e( 'Post ID', MULTISTE_CC_LANG_DOMAIN ); ?>"/>
			<input type="hidden" id="src_blog_id" name="src_blog_id" value="<?php echo $blog_id; ?>">
            <div style="display:inline-block"class="ui-widget">
                <label for="search_for_post"> <?php _e( 'Or search by post title', MULTISTE_CC_LANG_DOMAIN ); ?> 
					<input type="text" id="autocomplete" data-type="posts" class="medium-text">
					<span class="spinner"></span> <input type="button" class="button-secondary" name="add-post" id="add-post" value="<?php _e( 'Add post', MULTISTE_CC_LANG_DOMAIN ); ?>"></input> 
                </label>
            </div>
            <div class="clear"></div>
            <ul id="posts-list">
            	<?php echo $posts_list; ?>
            </ul>

            <h3><?php _e( 'Additional options', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
            <?php $options = mcc_get_post_additional_settings(); ?>
			<ul>
				<?php foreach( $options as $option_slug => $label ): ?>
					<li><label><input type="checkbox" name="settings[<?php echo $option_slug; ?>]" <?php checked( array_key_exists( $option_slug, $current_selected_settings ) ); ?>> <?php echo $label; ?></label></li>
				<?php endforeach; ?>
			</ul>
		<?php
	}

	private function render_plugin_selector() {
		$current_selected_plugins = $this->wizard->get_value( 'plugins' );

		require_once( MULTISTE_CC_ADMIN_DIR . 'tables/network-plugins-list.php' );
		$table = new MCC_Plugins_List_Table();
		$table->prepare_items( $current_selected_plugins );
		
		if ( ! is_array( $current_selected_plugins ) )
			$current_selected_plugins = array();

		?>
			<h3><?php _e( 'Select the plugins you want to activate', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<p><?php _e( 'Network only or already network activated plugins are not displayed in the list', MULTISTE_CC_LANG_DOMAIN ); ?>

		<?php $table->display();
	}


	private function render_step_4() {
		?>
			<form action="" method="post">
				<h3><?php _e( 'Select the destination blog/s', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
				<?php wp_nonce_field( 'step_4' ); ?>

				<?php if ( $this->wizard->get_value( 'mcc_action' ) !== 'activate-plugin' ): ?>
					<p>
						<label>
							<input type="radio" name="dest_blog_type" value="all" <?php checked( $this->wizard->get_value( 'dest_blog_type' ), 'all' ); ?>> 
							<?php _e( 'All of them', MULTISTE_CC_LANG_DOMAIN ); ?>
						</label>
					</p>
				<?php endif; ?>
				<p>
					<label>
						<input type="radio" name="dest_blog_type" value="list" <?php checked( $this->wizard->get_value( 'dest_blog_type' ), 'list' ); ?>>
						<?php _e( 'Select by blog ID', MULTISTE_CC_LANG_DOMAIN ); ?>
					</label>
				</p>
				<div id="blogs-list-wrap">
					<input name="blog_id" type="text" id="blog_id" class="small-text" style="float: left; margin-right: 10px;" placeholder="<?php _e( 'Blog ID', MULTISTE_CC_LANG_DOMAIN ); ?>"/>
		            <div style="display:inline-block"class="ui-widget">
		                <label for="search_for_blog"> <?php _e( 'Or search by blog path', MULTISTE_CC_LANG_DOMAIN ); ?> 
							<input type="text" id="autocomplete" data-type="sites" class="medium-text">
							<span class="spinner"></span> <input type="button" class="button-secondary" name="add-blog" id="add-blog" value="<?php _e( 'Add blog', MULTISTE_CC_LANG_DOMAIN ); ?>"></input>
		                </label>
		            </div>
					<div class="clear"></div>
		            <div id="blogs-list">
		            	<?php 
		            		$blogs_ids = $this->wizard->get_value( 'dest_blogs_ids' );
		            		if ( ! empty( $blogs_ids ) && is_array( $blogs_ids ) ) {
		            			foreach ( $blogs_ids as $blog_id ) {
		            				$blog_details = get_blog_details( $blog_id );
		            				if ( ! empty( $blog_details ) )
		            					echo $this->get_row_list( 'blog', $blog_id, $blog_details->blogname );
		            			}
		            		}	
		            	?>

		            </div>
		        </div>

		        <p>
					<label>
						<input type="radio" name="dest_blog_type" value="group" <?php checked( $this->wizard->get_value( 'dest_blog_type' ), 'group' ); ?>>
						<?php _e( 'Select by blogs group', MULTISTE_CC_LANG_DOMAIN ); ?>
					</label>
				</p>
				<select name="group">
					<?php mcc_get_groups_dropdown(); ?>
				</select>

				
				

				<p class="submit">
					<input type="submit" name="submit_step_4" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php

	}

	public function get_row_list( $slug, $id, $title ) {
		ob_start();
		?>
			<li id="<?php echo $slug; ?>-<?php echo $id; ?>">
				<span class="id-box"><?php echo $id; ?></span> <span class="title-box"><?php echo $title; ?></span> <span class="remove-box"><a class="mcc-remove-<?php echo $slug; ?>" href="" data-<?php echo $slug; ?>-id="<?php echo $id; ?>">X</a></span>
				<input type="hidden" name="<?php echo $slug; ?>s_ids[]" value="<?php echo $id; ?>"></input>
			</li>
		<?php
		return ob_get_clean();
	}

	public function render_step_5() {		

		$src_blog_id = $this->wizard->get_value( 'content_blog_id' );
		$dest_blog_type = $this->wizard->get_value( 'dest_blog_type' );
		$settings = $this->wizard->get_value( 'settings' );
		$dest_blogs_ids = $this->wizard->get_value( 'dest_blogs_ids' );

		if ( empty( $settings ) )
			$settings = array();

		$action = $this->wizard->get_value( 'mcc_action' );

		if ( 'add-page' == $action ) {
			$posts_ids = $this->wizard->get_value( 'posts_ids' );
			$settings['class'] = 'Multisite_Content_Copier_Page_Copier';
			$settings['post_ids'] = $posts_ids;
		}
		if ( 'add-post' == $action ) {
			$posts_ids = $this->wizard->get_value( 'posts_ids' );
			$settings['class'] = 'Multisite_Content_Copier_Post_Copier';
			$settings['post_ids'] = $posts_ids;
		}
		if ( 'activate-plugin' == $action ) {
			$plugins_ids = $this->wizard->get_value( 'plugins' );
			$settings['class'] = 'Multisite_Content_Copier_Plugins_Activator';
			$settings['plugins'] = $plugins_ids;
			$src_blog_id = 0;
		}
		
		if ( 'all' != $dest_blog_type ) {
			$model = mcc_get_model();
			foreach ( $dest_blogs_ids as $dest_blog_id ) {
				if ( $dest_blog_id != $src_blog_id )
					$model->insert_queue_item( $src_blog_id, $dest_blog_id, $settings );	
			}

			$this->wizard->clean();

			?>
			<p>
				<?php _e( 'All good', MULTISTE_CC_LANG_DOMAIN ); ?>
			</p>
			<?php
		}
		else {
			$this->wizard->set_value( 'settings', $settings );
			$blogs_count = get_blog_count();
			?>
				<div class="processing_result">
				</div>
			<?php
			$this->render_progressbar_js( $blogs_count );

			?>
			<p>
				<?php _e( 'Enqueueing all blogs, please do not close or refresh this window', MULTISTE_CC_LANG_DOMAIN ); ?>
			</p>
			<?php
		}
		
	}

	public function render_step_6() {
		$this->wizard->clean();
		?>
		<p>
			<?php _e( 'All good', MULTISTE_CC_LANG_DOMAIN ); ?>
		</p>
		<?php
	}

	private function render_progressbar_js( $items_count ) {
		$settings = $this->wizard->get_value( 'settings' );
		?>
		<script type="text/javascript" >
			jQuery(function($) {

				var rt_count = 0;
				var interval = 1;
				var rt_total = <?php echo $items_count; ?>;
				var label = 0;

				$('.processing_result')
					.html('<div id="progressbar" style="margin-top:20px"><div class="progress-label">' + label +'%</div></div>')

				$('#progressbar').progressbar({
					"value": 0,
					complete: function( event, ui ) {
						window.location = <?php echo '"' . $this->wizard->get_step_url( '6' ) . '"' ?>;
					}
				});

				// Initialize processing
				process_item();

				function process_item () {
					if ( rt_count >= rt_total ) 
						return false;

					$.post(
						ajaxurl, 
						{
							"action": "mcc_insert_all_blogs_queue",
							'offset': rt_count,
							'interval': interval,
							'content_blog_id': <?php echo $this->wizard->get_value( 'content_blog_id' ); ?>,
							'settings': <?php echo json_encode( $settings ); ?>,
							dataType: 'json'
						}, 
						function(response) {
							rt_count = rt_count + interval;
							label = Math.ceil( (rt_count / rt_total) * 100 );
							if ( label > 100 )
								label = 100;

							$( '#progressbar' ).progressbar( 'value', label );
							$( '.progress-label' ).text( label + '%' );
							process_item();
						}
					);
				}
			});
		</script>
		<?php
	}


	public function validate_form() {
		if ( isset( $_GET['page'] ) && $this->get_menu_slug() == $_GET['page'] ) {
 			$step = $this->wizard->get_current_step();

 			if ( 1 == $step && isset( $_POST['submit_step_1'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_1' ) )
 					return false;

 				if ( ! isset( $_POST['mcc_action'] ) )
 					mcc_add_error( 'wrong-action', __( 'Please, select an option', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( ! mcc_is_error() ) {
 					$this->wizard->set_value( 'mcc_action', $_POST['mcc_action'] );
 					if ( 'activate-plugin' == $_POST['mcc_action'] )
 						$this->wizard->go_to_step( '3' );
 					else 
 						$this->wizard->go_to_step( '2' );

 					return;
 				}
 			}

 			if ( 2 == $step && isset( $_POST['submit_step_2'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_2' ) )
 					return false;

 				if ( ! isset( $_POST['blog_id'] ) || ! $blog_details = get_blog_details( absint( $_POST['blog_id'] ) ) )
 					mcc_add_error( 'blog-id', __( 'The Blog ID does not exist', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( ! mcc_is_error() ) {
 					$this->wizard->set_value( 'content_blog_id', $_POST['blog_id'] );
 					$this->wizard->go_to_step( '3' );
 				}
 			}

 			if ( 3 == $step && isset( $_POST['submit_step_3'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_3' ) )
 					return false;

 				if ( 'add-post' == $this->wizard->get_value( 'mcc_action' ) && empty( $_POST['posts_ids'] ) )
 					mcc_add_error( 'select-post', __( 'You must add at least one post', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( 'add-page' == $this->wizard->get_value( 'mcc_action' ) && empty( $_POST['pages'] ) )
 					mcc_add_error( 'select-page', __( 'You must select at least one page', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( 'activate-plugin' == $this->wizard->get_value( 'mcc_action' ) && empty( $_POST['plugins'] ) )
 					mcc_add_error( 'select-plugin', __( 'You must select at least one plugin', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
 					$settings = array();
 					foreach ( $_POST['settings'] as $setting => $value ) {
 						$settings[ $setting ] = true;
 					}
 					$this->wizard->set_value( 'settings', $settings );
 				}
 					

 				if ( ! mcc_is_error() ) {

 					if ( 'add-page' == $this->wizard->get_value( 'mcc_action' ) ) {
	 					$posts_ids = array();
	 					foreach ( $_POST['pages'] as $page_id ) {
	 						$posts_ids[] = absint( $page_id );
	 					} 
	 					$this->wizard->set_value( 'posts_ids', $posts_ids );
	 				}

	 				if ( 'add-post' == $this->wizard->get_value( 'mcc_action' ) ) {
	 					$posts_ids = array();
	 					foreach ( $_POST['posts_ids'] as $post_id ) {
	 						$posts_ids[] = absint( $post_id );
	 					} 
	 					$this->wizard->set_value( 'posts_ids', $posts_ids );
	 				}

	 				if ( 'activate-plugin' == $this->wizard->get_value( 'mcc_action' ) ) {
	 					$plugins = $_POST['plugins'];
	 					$this->wizard->set_value( 'plugins', $plugins );
	 				}

 					
 					$this->wizard->go_to_step( '4' );
 				}
 			}

 			if ( 4 == $step && isset( $_POST['submit_step_4'] ) ) {
 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_4' ) )
 					return false;

 				$type = '';
 				if ( ! isset( $_POST['dest_blog_type'] ) || ! in_array( $_POST['dest_blog_type'], array( 'all', 'list', 'group' ) ) ) {
 					mcc_add_error( 'blog-type', __( 'Please, select an option', MULTISTE_CC_LANG_DOMAIN ) );
 				}
 				else {
 					$type = $_POST['dest_blog_type'];
 				}

 				$this->wizard->set_value( 'dest_blog_type', $type );

 				// saving just in case the error deletes all the data
 				if ( isset( $_POST['blogs_ids'] ) && is_array( $_POST['blogs_ids'] ) ) {
 					$blogs_ids = array();
 					$src_blog_id = $this->wizard->get_value( 'content_blog_id' );
 					foreach ( $_POST['blogs_ids'] as $blog_id ) {
 						if ( ! in_array( $blog_id, $blogs_ids ) && $src_blog_id != $blog_id )
 							$blogs_ids[] = $blog_id;
 					}
 					$this->wizard->set_value( 'dest_blogs_ids', $blogs_ids );
 				}
 				
 				if ( 'list' == $type ) {
 					if ( ! isset( $_POST['blogs_ids'] ) || ! is_array( $_POST['blogs_ids'] ) ) {
 						mcc_add_error( 'blog-id', __( 'You have not selected any blog', MULTISTE_CC_LANG_DOMAIN ) );
 						return;
 					}
 					$this->wizard->set_value( 'dest_blogs_ids', $_POST['blogs_ids'] );
 				}

 				if ( 'group' == $type ) {
 					if ( empty( $_POST['group'] ) ) {
 						mcc_add_error( 'blog-group', __( 'You have not selected any group', MULTISTE_CC_LANG_DOMAIN ) );
 						return;
 					}

 					$group = absint ( $_POST['group'] );

 					$model = mcc_get_model();
 					if ( ! $model->is_group( $group ) )
 						mcc_add_error( 'wrong-group', __( 'You have selected a wrong group', MULTISTE_CC_LANG_DOMAIN ) );

 					$blogs = $model->get_blogs_from_group( $group );

 					if ( empty( $blogs ) ) {
 						mcc_add_error( 'wrong-group', __( 'There are no blogs attached to that group.', MULTISTE_CC_LANG_DOMAIN ) );
 					}
 					else {
 						$ids = array();
 						foreach ( $blogs as $blog ) {
 							$ids[] = $blog->blog_id;
 						}
 						$this->wizard->set_value( 'dest_blogs_ids', $ids );
 					}

 				}

 				if ( ! mcc_is_error() ) {
					$this->wizard->go_to_step( '5' );
		 		}
 			}

 		}

 			
	}

}

