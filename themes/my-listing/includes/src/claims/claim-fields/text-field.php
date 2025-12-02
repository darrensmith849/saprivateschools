<?php

namespace MyListing\Src\Claims\Claim_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Text_Field extends Base_Claim_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		$this->validate_minlength();
		$this->validate_maxlength();
	}

	public function field_props() {
		$this->props['type'] = 'text';
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
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