<?php

class Multisite_Content_Copier_Network_Settings_Menu extends Multisite_Content_Copier_Admin_Page {

	private $settings;

	public function __construct( $menu_slug, $capability, $args ) {
 		parent::__construct( $menu_slug, $capability, $args );

 		$this->settings = mcc_get_settings();

 		add_action( 'admin_init', array( &$this, 'sanitize_settings' ) );
 	}

 	private function get_settings_fields() {
 		return apply_filters( 'mcc_admin_settings_fields', array(
 			'nbt-integration-field' => array(
 				'id' => 'nbt-integration-field',
 				'callback' => array( $this, 'render_nbt_integration_field' ),
 				'section' => 'nbt-integration',
 				'title' => __( 'Activate', MULTISTE_CC_LANG_DOMAIN )
 			),
 			'logs-field' => array(
 				'id' => 'logs-field',
 				'callback' => array( $this, 'render_activate_logs_field' ),
 				'section' => 'logs',
 				'title' => __( 'Activate logs', MULTISTE_CC_LANG_DOMAIN )
 			)
 		) );
 	}

 	private function get_settings_sections() {
 		return apply_filters( 'mcc_admin_settings_sections', array(
 			array(
 				'id' => 'nbt-integration',
 				'title' => __( 'New Blog Templates Integration', MULTISTE_CC_LANG_DOMAIN ),
 				'desc' => '<p>' . __( 'Checking this option allows the selecting of a New Blog Template Group as a destination for copied content.', MULTISTE_CC_LANG_DOMAIN ) . '</p>'
 			),
 			array(
 				'id' => 'logs',
 				'title' => __( 'Logs', MULTISTE_CC_LANG_DOMAIN ),
 				'desc' => false
 			)
 		) );	
 	}

 	public function render_content() {

 		if ( isset( $_GET['updated'] ) && $_GET['updated'] == 'true' )
 			Multisite_Content_Copier_Errors_Handler::show_updated_notice( __( 'Settings saved', MULTISTE_CC_LANG_DOMAIN ) );

 		mcc_show_errors();

 		?>
			<form action="" method="post">

				<?php foreach ( $this->get_settings_sections() as $section ): ?>
					<h3><?php echo esc_html( $section['title'] ); ?></h3>
					<?php if ( $section['desc'] ): ?>
						<?php echo $section['desc']; ?>
					<?php endif; ?>
					
					<table class="form-table">
						<?php foreach ( wp_list_filter( $this->get_settings_fields(), array( 'section' => $section['id'] ) ) as $field ): ?>
							<?php $this->render_row( $field['title'], $field['callback'] ); ?>
						<?php endforeach; ?>
					</table>

				<?php endforeach; ?>
				
				<?php wp_nonce_field( 'submit_mcc_settings', 'mcc_settings_nonce' ); ?>

				<?php submit_button( __( 'Save changes', MULTISTE_CC_LANG_DOMAIN ), 'primary', 'submit_mcc_settings' ); ?>
			</form>
 		<?php
 		
 	}

 	public function render_nbt_integration_field() {
 		?>
			<input type="checkbox" name="<?php echo mcc_get_settings_slug(); ?>[blog_templates_integration]" <?php checked( $this->settings['blog_templates_integration'] ); ?>>
 		<?php
 	}

 	public function render_activate_logs_field() {
 		?>
			<input type="checkbox" name="<?php echo mcc_get_settings_slug(); ?>[logs]" <?php checked( $this->settings['logs'] ); ?> />
			<span class="description">
				<?php
					if ( @fopen( MULTISITE_CC_LOG_DIR . 'test-log.log', 'a' ) ) {
						printf( __( 'Log directory (%s) is writable.', MULTISTE_CC_LANG_DOMAIN ), MULTISITE_CC_LOG_DIR );
					} else {
						printf( '<span style="color:red">' . __( 'Log directory (<code>%s</code>) is not writable. To allow logging, make this writable or define a custom <code>MULTISITE_CC_LOG_DIR</code>.', MULTISTE_CC_LANG_DOMAIN ) . '</span>', MULTISITE_CC_LOG_DIR );
					}
				?>
			</span>
		<?php
 	}


 	

 	public function sanitize_settings() {

 		if ( isset( $_GET['page'] ) && $_GET['page'] == $this->get_menu_slug() && isset( $_POST['submit_mcc_settings'] ) ) {

 			if ( ! check_admin_referer( 'submit_mcc_settings', 'mcc_settings_nonce' ) )
 				return;

 			$input = isset( $_POST[mcc_get_settings_slug()] ) ? $_POST[mcc_get_settings_slug()] : array();
 			$current_settings = mcc_get_settings();

 			$fields = $this->get_settings_fields();

 			if ( isset( $fields['nbt-integration-field'] ) ) {
	 			if ( isset( $input['blog_templates_integration'] ) ) {
	 				if ( ! mcc_is_nbt_active() ) {
	 					mcc_add_error( 'nbt-not-active', __( 'You need first to network activate the New Blog Templates plugin', MULTISTE_CC_LANG_DOMAIN ) );
	 				}
	 				else {
	 					$current_settings['blog_templates_integration'] = true;
	 					$nbt_model = mcc_get_nbt_model();
	 					$nbt_model->create_nbt_relationships_table();
	 				}
	 				
	 			}
	 			else {
	 				$current_settings['blog_templates_integration'] = false;
	 			}
	 		}

 			if ( isset( $fields['logs-field'] ) ) {
 				if ( isset( $input['logs'] ) ) {
 					$current_settings['logs'] = true; 				
	 			}
	 			else {
	 				$current_settings['logs'] = false;
	 			}	
 			}
 			

 			$current_settings = apply_filters( 'mcc_sanitize_settings', $current_settings, $input );


 			if ( ! mcc_is_error() ) {

 				mcc_update_settings( $current_settings );

	 			$redirect_to = add_query_arg( 'updated', 'true', $this->get_permalink() );

	 			/**
				 * Filters the redirection URL when the settings are saved
				 * 
				 * @param String $redirect_to
				 */
	 			$redirect_to = apply_filters( 'mcc_update_settings_screen_redirect_url', $redirect_to );

	 			if ( ! empty( $redirect_to ) )
	 				wp_safe_redirect( $redirect_to );	
 			}
 			
 			
 		}

 	}
}