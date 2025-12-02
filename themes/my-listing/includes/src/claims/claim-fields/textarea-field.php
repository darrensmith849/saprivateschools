<?php

namespace MyListing\Src\Claims\Claim_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Textarea_Field extends Base_Claim_Field {

	public function field_props() {
		$this->props['type'] = 'textarea';
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
	}

	public function get_posted_value() {
		$value = isset( $_POST[ $this->get_key() ] ) ? $_POST[ $this->get_key() ] : '';
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	public function validate() {
		$value = $this->the_posted_value();
		$this->validate_minlength();
		$this->validate_maxlength();
	}

	public function get_editor_options() {
		$this->get_label_option();
		$this->get_key_field();
		$this->get_description_option();
		$this->get_minlength_option();
		$this->get_maxlength_option();
		$this->get_required_option();
	}
}
