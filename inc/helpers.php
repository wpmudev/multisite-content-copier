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