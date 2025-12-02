<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Compare_Listings_Endpoint {

    public function __construct() {

        add_action( 'mylisting_ajax_compare_listings', [ $this, 'handle' ] );
        add_action( 'mylisting_ajax_nopriv_compare_listings', [ $this, 'handle' ] );
    }

    public function handle() {
        mylisting_check_ajax_referrer();

        $post_id = '';
        if ( empty( $_REQUEST['listing_ids'] ) ) {
            return;
        } else {
            $post_ids = array_map( 'absint', (array) $_REQUEST['listing_ids'] );
        }

        $result = [];
        foreach ( $post_ids as $post_id ) {
        	$listing = \MyListing\Src\Listing::get( $post_id );
            if ( ! $listing || $listing->get_status() !== 'publish' ) {
                continue;
            }

            $result[ $post_id ][ 'title' ] = [
                'label' => '',
                'value' => '',
                'type'  => '',
            ];
            if ( $logo = $listing->get_logo() ) {
                $result[ $post_id ]['title']['value'] .= sprintf(
                    '<img src="%s">',
                    esc_url( $logo )
                );
            }

            $result[ $post_id ]['title']['value'] .= sprintf( '<strong>%s</strong>', $listing->get_title() );

            foreach ( $listing->get_fields() as $field ) {
                if ( ! $field->get_prop('show_in_compare') ) {
                    continue;
                }

                if ( $field->get_key() === 'job_title' || $field->get_key() === 'job_logo' ) {
                    continue;
                }

                $value = $field->get_string_value();
                if ( ! ( is_string( $value ) || is_numeric( $value ) ) ) {
                    $value = '';
                }

                if ( $field->get_type() === 'file' ) {
                    $value = '';
                    foreach ( (array) $field->get_value() as $single_file ) {
                        $url = c27()->get_resized_image( $single_file, 'full' );
                        if ( $url ) {
                            $value .= sprintf(
                                '<a href="%s" target="_blank">%s</a><br>',
                                esc_url( $url ),
                                _x( 'View attachment', 'comparison modal', 'my-listing' )
                            );
                        }
                    }
                }

                if ( $field->get_key() === 'job_location' ) {
                	$value = '';
                	foreach ( (array) $field->get_value() as $key => $val ) {
                		if ( $val['address'] ) {
                			$value .= '<p>'.$val['address'].'</p>';
                		}

                	}
                }

                if ( $field->get_type() === 'work-hours' ) {
                	$value = '<p>'._x('Current status: ', 'Compare modal', 'my-listing') . (!empty($value) ? $value : __('N/A', 'my-listing')) . '</p>';
                	$work_hours = $listing->get_field( 'work_hours' );
                	$schedule = new \MyListing\Src\Work_Hours( $work_hours );
                	foreach ( $schedule->get_schedule() as $weekday ) {
                		$value .= sprintf(
                			'<span>%1$s</span>: %2$s</br>',
                			esc_html( $weekday['day_l10n'] ),
                			$schedule->get_day_schedule( $weekday['day'] )
                		);
                	}
                }

                if ( $field->get_type() === 'select-products' || $field->get_type() === 'select-product' || $field->get_type() === 'related-listing' ) {
                	$value = '';
                	$ids = $field->get_value();
                	if ( ! empty( $ids ) && is_array( $ids ) ) {
                		$items = count( $ids );
                		$value .= '<p>'. get_the_title( $ids[0] );
                		if ( $items > 1 ) {
                			$more = $items - 1;
                			$value .= '<span class="more-items">+'. $more .'</span>';
                		}
                		$value .= '</p>';
                	} elseif ( ! empty( $ids ) && is_numeric( $ids )  ) {
                		$id = (int) $ids;
                		$value .= '<p>'. get_the_title( $id ) .'</p>';
                	}
                }

                // format date
                if ( $field->get_type() === 'date' && ! empty( $field->get_value() ) ) {
                	$input = $field->get_value();
                	$date = strtotime($input);
                	$format = $field->props['format'] === 'date' ? get_option( 'date_format' ) : get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                	$value = date( $format, $date );
                }

                $result[ $post_id ][ $field->get_key() ] = [
                    'label' => $field->get_label(),
                    'value' => $value,
                    'type'  => $field->get_type(),
                ];
            }
        }

		$html = '<table border="1" class="compare-table" cellpadding="5" cellspacing="5">';
        foreach ( $result[ $post_id ] as $field_key => $field ) {
        	$is_empty = true;
            
        	foreach ( $result as $postdata ) {
        		if ( isset( $postdata[ $field_key ], $postdata[ $field_key ]['value'] ) && !empty( $postdata[ $field_key ]['value'] ) ) {
        			$is_empty = false;
        			break;
        		}
        	}

        	if ( $is_empty ) {
        		continue;
        	}

        	$html .= '<tr class="compare-row"><th  class="compare-head">'.$field['label'].'</th>';

        	foreach ( $result as $postdata ) {
        		$val = isset( $postdata[ $field_key ], $postdata[ $field_key ]['value'] ) ? $postdata[ $field_key ]['value'] : '';
        		$html .= '<td class="compare-cell">'.$val.'</td>';
        	}

        	$html .= '</tr>';
        }

		$html .= '</table>';

        return wp_send_json( [
            'success' => true,
            'html' => $html,
        ]);
    }
}