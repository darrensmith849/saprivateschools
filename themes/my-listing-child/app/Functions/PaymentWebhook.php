<?php

namespace NGD_THEME\Functions;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_REST_Request;
use WP_REST_Response;
use GuzzleHttp\Client;

class PaymentWebhook {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'ngd/v1', '/payment_receiver', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_webhook( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        
        // CONFIGURATION
        $secret_key = 'T9S%OK&vK9]qsWU5hpMIbbR9ZTl7'; // MUST MATCH YOUR GOOGLE SCRIPT
        $brevo_api_key = defined('NGD_BREVO_API_KEY') ? NGD_BREVO_API_KEY : ''; 
        // -------------

        if ( ! isset( $params['secret'] ) || $params['secret'] !== $secret_key ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid Secret' ], 403 );
        }

        $reference_in = sanitize_text_field( $params['reference'] );

        // Find listing by Reference
        $args = [
            'post_type'  => 'job_listing',
            'meta_key'   => '_renewal_reference',
            'meta_value' => $reference_in,
            'post_status' => 'any',
            'posts_per_page' => 1
        ];
        
        $listings = get_posts( $args );

        if ( empty( $listings ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Reference not found' ], 404 );
        }

        $listing = $listings[0];
        $listing_id = $listing->ID;

        // Renew the Listing (Add 1 Year)
        $current_expiry = get_post_meta( $listing_id, '_job_expires', true );
        $today = date('Y-m-d');

        if ( empty($current_expiry) || $current_expiry < $today ) {
            $new_expiry = date( 'Y-m-d', strtotime( '+1 year' ) );
        } else {
            $new_expiry = date( 'Y-m-d', strtotime( $current_expiry . ' +1 year' ) );
        }

        update_post_meta( $listing_id, '_job_expires', $new_expiry );
        update_post_meta( $listing_id, '_payment_status', 'PAID' );
        update_post_meta( $listing_id, '_renewal_reference', '' ); // Clear ref so it can't be reused

        // Stop Brevo Chase
        $this->update_brevo_status( $listing->post_author, $brevo_api_key );

        return new WP_REST_Response( [ 
            'success' => true, 
            'message' => 'Listing renewed until ' . $new_expiry 
        ], 200 );
    }

    private function update_brevo_status( $user_id, $api_key ) {
        $user_email = get_the_author_meta( 'user_email', $user_id );
        if ( ! $user_email ) return;

        $client = new Client();
        try {
            $client->request('PUT', 'https://api.brevo.com/v3/contacts/' . urlencode($user_email), [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $api_key,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'attributes' => [
                        'PAYMENT_STATUS' => 'PAID',
                        'LAST_PAYMENT_DATE' => date('Y-m-d')
                    ]
                ],
            ]);
        } catch ( \Exception $e ) {
            error_log( "Brevo Webhook Error: " . $e->getMessage() );
        }
    }
}
