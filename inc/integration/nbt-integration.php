<?php

class MCC_NBT_Integrator {

	private $show_notice_slug = 'show_nbt_integration_notice';

	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );

		add_action( 'deactivate_blogtemplates/blogtemplates.php', array( &$this, 'deactivate_nbt_integration' ) );
		add_action( 'activate_blogtemplates/blogtemplates.php', array( &$this, 'maybe_show_nbt_integration_notice' ) );

		add_action( 'delete_blog', array( &$this, 'delete_blog_relationships' ), 10, 1 );

		add_action( 'switch_theme', array( &$this, 'delete_blog_relationships_on_switch_theme' ), 10, 1 );

	}

	public function init() {
		if ( isset( $_GET['dismiss_nbt_int_notice'] ) && 'true' == $_GET['dismiss_nbt_int_notice'] )
			delete_site_option( $this->show_notice_slug );

		if ( get_site_option( $this->show_notice_slug ) ) {
			add_action( 'network_admin_notices', array( &$this, 'show_nbt_integration_notice' ) );
		}

		$settings = mcc_get_settings();
		if ( $settings['blog_templates_integration'] ) {
			add_action( 'blog_templates-copy-after_copying', array( &$this, 'add_relationship' ), 10, 2 );
			add_action( 'blog_templates_delete_template', array( &$this, 'delete_template_relationships' ), 10, 1 );
		}
	}

	public function deactivate_nbt_integration() {
		$settings = mcc_get_settings();
		$settings['blog_templates_integration'] = false;
		update_site_option( $this->show_notice_slug, false );
		mcc_update_settings( $settings );
	}

	public function maybe_show_nbt_integration_notice() {
		$settings = mcc_get_settings();
		if ( ! $settings['blog_templates_integration'] )
			update_site_option( $this->show_notice_slug, true );
			
	}

	public function show_nbt_integration_notice() {
		$settings = mcc_get_settings();
		if ( ! $settings['blog_templates_integration'] ) {
			$link = add_query_arg( 'page', 'mcc_settings_page', network_admin_url( 'admin.php' ) );
			$dismiss_link = add_query_arg( 'dismiss_nbt_int_notice', 'true', network_admin_url() );
			$message = sprintf( __( 'The New Blog Templates plugin is activated. You may want to <a href="%s">activate integration with Multisite Content Copier</a> <a href="%s" class="button">Dismiss</a>', MULTISTE_CC_LANG_DOMAIN ), $link, $dismiss_link );
			Multisite_Content_Copier_Errors_Handler::show_error_notice( $message );
		}
	}

	public function add_relationship( $template, $blog_id ) {
		$nbt_model = mcc_get_nbt_model();
		$nbt_model->insert_relationship( $blog_id, $template['ID'] );
	}

	public function delete_template_relationships( $template_id ) {
		$nbt_model = mcc_get_nbt_model();
		$nbt_model->delete_relationships( 'template_id', $template_id );
	}

	public function delete_blog_relationships( $blog_id ) {
		$nbt_model = mcc_get_nbt_model();
		$nbt_model->delete_relationships( 'blog_id', $blog_id );
	}

	public function delete_blog_relationships_on_switch_theme( $new_theme ) {
		$nbt_model = mcc_get_nbt_model();
		$nbt_model->delete_relationships( 'blog_id', get_current_blog_id() );
	}
}