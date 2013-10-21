<?php

class Multisite_Content_Copier_Network_Main_Menu extends Multisite_Content_Copier_Admin_Page {
 	
 	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );
 		$this->steps = array( 1,2,3,4, 5 );

 		add_action( 'admin_init', array( &$this, 'validate_form' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );

        add_action( 'wp_ajax_mcc_get_sites_search', array( &$this, 'get_sites_search' ) );
 	}

 	public function add_javascript() {
 		if ( get_current_screen()->id == $this->page_id . '-network' ) {

 			if ( 2 == $this->get_current_step() ) {
	    		wp_enqueue_script( 'bbu-templates-js', MULTISTE_CC_ASSETS_URL . 'js/search-blog.js', array( 'jquery' ) );
	    		wp_enqueue_script( 'jquery-ui-autocomplete' );
	 			wp_enqueue_style( 'bbu-jquery-ui-styles', MULTISTE_CC_ASSETS_URL . 'css/jquery-ui.css' );
	 		}
    	}
 	}

 	function get_sites_search() {
		global $wpdb, $current_site;

		if ( ! empty( $_POST['term'] ) ) 
			$term = $_REQUEST['term'];
		else
			echo json_encode( array() );

		$s = isset( $_REQUEST['term'] ) ? stripslashes( trim( $_REQUEST[ 'term' ] ) ) : '';
		$wild = '%';
		if ( false !== strpos($s, '*') ) {
			$wild = '%';
			$s = trim($s, '*');
		}

		$like_s = esc_sql( like_escape( $s ) );
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

		if ( is_subdomain_install() ) {
			$blog_s = $wild . $like_s . $wild;
			$query .= " AND  ( {$wpdb->blogs}.domain LIKE '$blog_s' ) LIMIT 10";
		}
		else {
			if ( $like_s != trim('/', $current_site->path) )
				$blog_s = $current_site->path . $like_s . $wild . '/';
			else
				$blog_s = $like_s;	

			$query .= " AND  ( {$wpdb->blogs}.path LIKE '$blog_s' ) LIMIT 10";
		}
				
		$results = $wpdb->get_results( $query );

		$returning = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$details = get_blog_details( $row->blog_id );
				$returning[] = array( 
					'blog_name' => $details->blogname,
					'path' => $row->path, 
					'blog_id' => $row->blog_id 
				);
			}
		}

		echo json_encode( $returning );

		die();
	}
	


	public function render_content() {
		$step = $this->get_current_step();
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

	private function get_current_step() {
		if ( ! isset( $_GET['step'] ) || isset( $_GET['step'] ) && ! in_array( absint( $_GET['step'] ), $this->steps ) )
			return 1;
		else
			return absint( $_GET['step'] );
	}

	private function render_step_1() {
		?>
			<h3><?php _e( 'Welcome to Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<p class="about-description">
				<?php _e( 'Please, select the option', MULTISTE_CC_LANG_DOMAIN ); ?>
			</p>
			<form action="" method="post" name="wizardform" id="wizardform">
				<?php wp_nonce_field( 'step_1' ); ?>
				<ul class="wizardoptions">
					<li><label><input type="radio" name="action" value="add-page" checked="checked"> <?php _e( 'Add a page', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
					<li><label><input type="radio" name="action" value="add-post"> <?php _e( 'Add a post', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
				</ul>
				<p class="submit">
					<input type="submit" name="submit_step_1" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php
	}

	private function render_step_2() {
		$action = $_GET['action'];
		?>
			<form action="" method="post">
				<input type="hidden" name="action" value="<?php echo $action; ?>">
				<?php wp_nonce_field( 'step_2' ); ?>
				<?php
					switch ( $action ) {
						case 'add-page': 
						case 'add-post': {
							$this->render_blog_selector();
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

	private function render_blog_selector() {
		?>
			
			<h3><?php _e( 'Select the blog where the page is', MULTISTE_CC_LANG_DOMAIN ); ?></h3><br/>
			<input name="blog_id" type="text" id="blog_id" class="small-text" placeholder="<?php _e( 'Blog ID', MULTISTE_CC_LANG_DOMAIN ); ?>"/>
            <div style="display:inline-block"class="ui-widget">
                <label for="search_for_blog"> <?php _e( 'Or search by blog path', MULTISTE_CC_LANG_DOMAIN ); ?> 
					<input type="text" id="search_for_blog" class="medium-text">
					<span class="description"><?php _e( 'For example, if the blog you are searching has an URL like http://ablog.mydomain.com, you can type "ablog"', MULTISTE_CC_LANG_DOMAIN ); ?></span>
                </label>
            </div>

		<?php
	}

	private function render_step_3() {
		$action = $_GET['action'];
		$content_blog_id = absint( $_GET['blog_id'] );
		?>
			<form action="" method="post">
				<input type="hidden" name="action" value="<?php echo $action; ?>">
				<input type="hidden" name="blog_id" value="<?php echo $content_blog_id; ?>">
				<?php wp_nonce_field( 'step_3' ); ?>
				<?php
					switch ( $action ) {
						case 'add-page': {
							$this->render_page_selector( $content_blog_id );
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

		?>
			<h3><?php _e( 'Choose the pages that you want to add', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<ul>
				<?php foreach ( $pages as $page ): ?>
					<li><label><input type="checkbox" name="pages[]" value="<?php echo $page->ID; ?>"> <?php echo $page->post_title; ?></label></li>
				<?php endforeach; ?>
			</ul>
			<hr/>
			<h3><?php _e( 'Additional options', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
			<ul>
				<li><label><input type="checkbox" name="settings[copy_images]"> <?php _e( 'Copy images to new upload folder', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
				<li><label><input type="checkbox" name="settings[update_date]"> <?php _e( 'Update the created date of the post', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
				<li><label><input type="checkbox" name="settings[copy_parents]"> <?php _e( 'Copy page/post parents', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
				<li><label><input type="checkbox" name="settings[copy_comments]"> <?php _e( 'Copy comments', MULTISTE_CC_LANG_DOMAIN ); ?></label></li>
			</ul>

		<?php

	} 


	private function render_step_4() {
		

		?>
			<form action="" method="post">
				<h3><?php _e( 'Select the destination blog ID', MULTISTE_CC_LANG_DOMAIN ); ?></h3>
				<?php wp_nonce_field( 'step_4' ); ?>
				Blog ID: <input type="text" name="dest_blog_id" value="">

				<p class="submit">
					<input type="submit" name="submit_step_4" class="button button-primary button-hero alignleft" value="<?php _e( 'Next Step &raquo;', MULTISTE_CC_LANG_DOMAIN ); ?>">
				</p>
			</form>
		<?php

	}

	public function render_step_5() {
		$pages = $_GET['pages'];
		$settings = $_GET['settings'];

		if ( 'false' == $settings )
			$settings = array();

		$settings['class'] = 'Multisite_Content_Copier_Page_Copier';
		$settings['post_ids'] = $pages;

		$src_blog_id = $_GET['blog_id'];
		$dest_blog_id = $_GET['dest_blog_id'];

		$model = mcc_get_model();
		$model->insert_queue_item( $src_blog_id, $dest_blog_id, $settings );

	}

	public function validate_form() {
		if ( isset( $_GET['page'] ) && $this->get_menu_slug() == $_GET['page'] ) {
 			$step = $this->get_current_step();

 			if ( 1 == $step && isset( $_POST['submit_step_1'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_1' ) )
 					return false;

 				if ( ! isset( $_POST['action'] ) )
 					mcc_add_error( 'wrong-action', __( 'Please, select an option', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( ! mcc_is_error() ) {
 					$url = add_query_arg(
	 					array(
	 						'step' => 2,
	 						'action' => $_POST['action']
	 					),
	 					$this->get_permalink()
	 				);
 					wp_redirect( $url );
 				}
 			}

 			if ( 2 == $step && isset( $_POST['submit_step_2'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_2' ) )
 					return false;

 				if ( ! isset( $_POST['blog_id'] ) || ! $blog_details = get_blog_details( absint( $_POST['blog_id'] ) ) )
 					mcc_add_error( 'blog-id', __( 'The Blog ID does not exist', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( ! mcc_is_error() ) {
 					$url = add_query_arg(
	 					array(
	 						'step' => 3,
	 						'action' => $_POST['action'],
	 						'blog_id' => absint( $_POST['blog_id'] )
	 					),
	 					$this->get_permalink()
	 				);
 					wp_redirect( $url );
 				}
 			}

 			if ( 3 == $step && isset( $_POST['submit_step_3'] ) ) {

 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_3' ) )
 					return false;

 				if ( ! isset( $_POST['blog_id'] ) || ! $blog_details = get_blog_details( absint( $_POST['blog_id'] ) ) )
 					mcc_add_error( 'blog-id', __( 'The Blog ID does not exist', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( empty( $_POST['pages'] ) )
 					mcc_add_error( 'select-page', __( 'You must select at least one page', MULTISTE_CC_LANG_DOMAIN ) );


 				if ( ! mcc_is_error() ) {
 					$url = add_query_arg(
	 					array(
	 						'step' => 4,
	 						'action' => $_POST['action'],
	 						'blog_id' => absint( $_POST['blog_id'] ),
	 						'pages' => $_POST['pages'],
	 						'settings' => isset( $_POST['settings'] ) ? $_POST['settings'] : 'false'
	 					),
	 					$this->get_permalink()
	 				);

 					wp_redirect( $url );
 				}
 			}

 			if ( 4 == $step && isset( $_POST['submit_step_4'] ) ) {
 				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'step_4' ) )
 					return false;

 				if ( ! isset( $_POST['dest_blog_id'] ) || ! $blog_details = get_blog_details( absint( $_POST['dest_blog_id'] ) ) )
 					mcc_add_error( 'blog-id', __( 'The Blog ID does not exist', MULTISTE_CC_LANG_DOMAIN ) );

 				if ( ! mcc_is_error() ) {
						$url = add_query_arg(
							array(
								'step' => 5,
								'dest_blog_id' => absint( $_POST['dest_blog_id'] )
							)
						);
						wp_redirect( $url );
					}
		 		}
 			}

 			
	}

}

