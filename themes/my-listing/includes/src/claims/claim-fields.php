<?php

namespace MyListing\Src\Claims;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_field_types() {
	$fields = apply_filters( 'mylisting/listing-types/claim-fields', [
        \MyListing\Src\Claims\Claim_Fields\Text_Field::class,
        \MyListing\Src\Claims\Claim_Fields\File_Field::class,
        \MyListing\Src\Claims\Claim_Fields\Number_Field::class,
        \MyListing\Src\Claims\Claim_Fields\Textarea_Field::class,
    ] );

	$field_types = [];
	foreach ( $fields as $field_class ) {
        if ( ! ( class_exists( $field_class ) && is_subclass_of( $field_class, \MyListing\Src\Claims\Claim_Fields\Base_Claim_Field::class ) ) ) {
            continue;
        }

        $field = new $field_class;
        $field_types[ $field->props['type'] ] = $field;
    }

	return $field_types;
}