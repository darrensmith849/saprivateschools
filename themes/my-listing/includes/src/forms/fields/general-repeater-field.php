<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class General_Repeater_Field extends Base_Field {

	public function get_posted_value() {

		$value = ! empty( $_POST[ $this->key ] ) ? (array) $_POST[ $this->key ] : [];

		$form_key = 'current_'.$this->key;
		$files = isset( $_POST[ $form_key ] ) ? (array) $_POST[ $form_key ] : [];
		$prepared_files = [];

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $url ) {
				if ( ! isset( $url['mylisting_accordion_photo'] ) ) {
					continue;
				}

				if ( is_array( $url['mylisting_accordion_photo'] ) ) {
					$url['mylisting_accordion_photo'] = reset($url['mylisting_accordion_photo']);
				}

				$prepared_files[ $key ] = $url['mylisting_accordion_photo'];
			}
		}
		
		$links = [];
			foreach ( $value as $index => $file_value ) {
				if ( empty( $file_value ) || ! is_array( $file_value ) ) {
					continue;
				}

				if ( isset( $prepared_files[ $index ] ) ) {
				$file = $prepared_files[ $index ];
				if ( is_array( $file ) ) {
					$file = reset( $file );
				}

				$file_value['mylisting_accordion_photo'] = $file;
				}

				$links[] = $file_value;
			}
		
		return array_filter( $links );
	}

	public function validate() {
		$value = $this->get_posted_value();

		foreach ( (array) $value as $file_guid ) {
			if (!isset($file_guid['mylisting_accordion_photo'])) {
				return;
			}
			$file_guid = $file_guid['mylisting_accordion_photo'];
			if ( is_numeric( $file_guid ) ) {
				continue;
			}


			// validate attachment urls
			$file_guid = esc_url( $file_guid, [ 'http', 'https' ] );
			if ( empty( $file_guid ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf(
					_x( 'Invalid attachment provided for %s.', 'Add listing form', 'my-listing' ),
					$this->props['label']
				) );
			}

			// validate image size
			$image_id = c27()->get_attachment_by_guid( $file_guid );
			$img_data = wp_get_attachment_metadata( $image_id );
			$img_size = $img_data['filesize']/1024;
			$img_name = get_the_title($image_id);
			$allowed_size = $this->props['image_size'];
			c27()->file_size_validation( $allowed_size, $img_size, $this->props['label'], $img_name );

			// validate attachment file types
			$file_guid = current( explode( '?', $file_guid ) );
			$file_info = wp_check_filetype( $file_guid );
			$allowed_mime_types = [
				"jpg" => "image/jpeg",
				"jpeg" => "image/jpeg",
				"jpe" => "image/jpeg",
				"gif" => "image/gif",
				"png" => "image/png",
				"bmp" => "image/bmp",
				"tiff|tif" => "image/tiff",
				"webp" => "image/webp",
				"ico" => "image/x-icon",
				"heic" => "image/heic"
			];

			if (
				! empty( $allowed_mime_types ) && $file_info
				&& ! in_array( $file_info['type'], $allowed_mime_types, true )
			) {
				// translators: Placeholder %1$s is the field label; %2$s is the file mime type; %3$s is the allowed mime-types.
				throw new \Exception( sprintf(
					_x( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'Add listing form', 'my-listing' ),
					$this->props['label'],
					$file_info['ext'],
					implode( ', ', array_keys( $allowed_mime_types ) )
				) );
			}
		}
	}

	public function field_props() {
		// for backwards compatibility
		$this->props['type'] = 'general-repeater';
		$this->props['allow_price'] = true;
		$this->props['currency'] = '';
		$this->props['allow_link'] = true;
		$this->props['allow_description'] = true;
		$this->props['allow_images'] = true;
		$this->props['image_size'] = '';
	}

	public function update() {
		$value = $this->get_posted_value();
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $value );
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->allowPrice();
		$this->allowLink();
		$this->allowDescription();
		$this->allowImages();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	public function allowPrice() { ?>
		<div class="form-group w50">
			<label>Enable price?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_price">
				<span class="switch-slider"></span>
			</label>
		</div>

		<div class="form-group w50">
			<label>Currency</label>
			<input type="text" v-model="field.currency" placeholder="E.g. $">
		</div>
		<?php
	}

	public function allowLink() { ?>
		<div class="form-group w50">
			<label>Enable link?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_link">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowDescription() { ?>
		<div class="form-group w50">
			<label>Enable description?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_description">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowImages() { ?>
		<div class="form-group w50">
			<label>Enable images?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_images">
				<span class="switch-slider"></span>
			</label>
		</div>
		<div class="form-group w50" v-show="field.allow_images === true">
			<label>Image upload size (KB)</label>
			<input type="number" v-model="field.image_size">
		</div>
		<?php
	}

	public function get_value() {
		$value = get_post_meta( $this->listing->get_id(), '_'.$this->key, true );
		return $value;
	}
}
