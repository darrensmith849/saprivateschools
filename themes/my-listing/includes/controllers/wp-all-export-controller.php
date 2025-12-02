<?php

namespace MyListing\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wp_All_Export_Controller extends Base_Controller {

	protected function is_active() {
		$all_export_active = defined('PMXE_VERSION');
		return $all_export_active;
	}

	protected function hooks() {
		$this->filter( 'wp_all_export_csv_rows', '@export_parse_function', 99, 3 );
	}

	protected function export_parse_function( $post, $export_options, $export_id ) {

		if ( empty( $post ) ) {
			return $post;
		}
		
		foreach ($post as $index => $post_data ) {
			$post_id = '';
			
			if ( isset( $post_data['ID'] ) ) {
				$post_id = $post_data['ID'];
			} else if ( isset( $post_data['id'] ) ) {
				$post_id = $post_data['ID'];
			}
			if ( empty( $post_id ) ) {
				continue;
			}
			
			if ( get_post_type( $post_id ) !== 'job_listing' ) {
				continue;
			}

			$listing = \MyListing\Src\Listing::get( $post_id );
			if ( isset( $post_id ) && isset( $post_data['_location'] ) ) {
				$locations = $listing->get_field( 'location' );
				if ( ! $locations ) {
					continue;
				}

				// Update the location field with the comma-separated list of locations.
				$location_values = [];
				foreach ($locations as $key => $location ) {
					if ( $location['address'] && $location['lat'] && $location['lng'] ) {
						$location_values[] = sprintf(
							'%s,%s,%s',
							esc_sql( $location['address'] ),
							(float) $location['lat'],
							(float) $location['lng'],
						);
					}
				}
				$post[$index]['_location'] = maybe_serialize( $location_values );
			}

			if ( isset( $post_id ) && isset( $post_data['_work_hours'] ) ) {
				$work_hours_field = $listing->get_field( 'work_hours' );
				if ( ! $work_hours_field ) {
					continue;
				}

				// Update the location field with the comma-separated list of values.
				$work_hours_values = [];
				foreach ($work_hours_field as $key => $location) {
					$work_hour_str = implode(',', array_map(function ($key, $value) {
						return "$key:$value";
					}, array_keys($location), $location));

					$work_hours_values[] = $work_hour_str;
				}

				$post[$index]['_work_hours'] = maybe_serialize( $work_hours_values );
			}
		}

		return $post;
	}
}