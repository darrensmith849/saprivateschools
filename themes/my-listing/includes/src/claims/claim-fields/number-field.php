<?php

namespace MyListing\Src\Claims\Claim_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Number_Field extends Base_Claim_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	protected function get_minlength_option() { ?>
		<div class="form-group">
			<label>Minimum value</label>
			<input type="number" v-model="field.minlength">
		</div>
	<?php }

	protected function get_maxlength_option() { ?>
		<div class="form-group">
			<label>Maximum value</label>
			<input type="number" v-model="field.maxlength">
		</div>
	<?php }
	
	public function validate() {
		$value = $this->get_posted_value();

		// validate it's a number
		if ( ! is_numeric( $value ) ) {
			// translators: %s is the field label.
			throw new \Exception( sprintf( _x( '%s must be a number.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
		}

		$val  = (float) $value;
		$min  = is_numeric( $this->props['minlength'] ) ? (float) $this->props['minlength'] : false;
		$max  = is_numeric( $this->props['maxlength'] ) ? (float) $this->props['maxlength'] : false;
		$step = is_numeric( $this->props['step'] ) ? (float) $this->props['step'] : false;

		if ( $min !== false && $val < $min ) {
			// translators: %1$s is the field label; %2%s is the minimum allowed value.
			throw new \Exception( sprintf( _x( '%1$s can\'t be smaller than %2$s.', 'Add listing form', 'my-listing' ), $this->props['label'], $min ) );
		}

		if ( $max !== false && $val > $max ) {
			// translators: %1$s is the field label; %2%s is the maximum allowed value.
			throw new \Exception( sprintf( _x( '%1$s can\'t be bigger than %2$s.', 'Add listing form', 'my-listing' ), $this->props['label'], $max ) );
		}
	}

	public function field_props() {
		$this->props['type'] = 'number';
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
		$this->props['step'] = 1;
	}

	public function get_editor_options() {
		$this->get_label_option();
		$this->get_key_field();
		$this->get_description_option();
		$this->get_minlength_option();
		$this->get_maxlength_option();
		$this->get_step_field();
		$this->get_required_option();
	}
}