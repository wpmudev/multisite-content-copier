<?php


class MCC_Wizard {

	private $baseurl;
	private $current_step;
	private $action;
	private $steps;

	public function __construct( $steps = array(), $url ) {

		if ( ! session_id() )
			session_start();

		$this->steps = $steps;
		$this->baseurl = $url;



		if ( isset( $_REQUEST['step'] ) && in_array( $_REQUEST['step'], $steps ) )
			$this->current_step = $_REQUEST['step'];
		else
			$this->current_step = isset( $steps[0] ) ? $steps[0] : '';

		$this->set_value( 'step',$this->current_step );

	}

	public function get_current_step() {
		return $this->current_step;
	}

	public function get_value( $key ) {
		if ( isset( $_SESSION['mcc_wizard'][ $key ] ) )
			return $_SESSION['mcc_wizard'][ $key ];	

		return '';
	}

	public function set_value( $key, $value ) {
		$_SESSION['mcc_wizard'][ $key ] = $value;
	}

	public function clean() {
		unset( $_SESSION['mcc_wizard'] );
	}

	public function is_last_step() {
		$_steps = $this->steps;
		if ( $this->get_current_step() == end( $_steps ) )
			return true;

		return false;
	}

	public function is_first_step() {
		$_steps = $this->steps;

		reset( $_steps );
		if ( $this->get_current_step() == current( $_steps ) )
			return true;

		return false;
	}

	public function get_action() {
		return $this->action;
	}

	public function go_to_step( $step ) {
		if ( in_array( $step, $this->steps ) ) {
			$this->set_value( 'step', $step );
			wp_redirect( add_query_arg( 'step', $step, $this->baseurl ) );
		}
	}

	public function debug() {
		var_dump( $_SESSION['mcc_wizard'] );
	}
}