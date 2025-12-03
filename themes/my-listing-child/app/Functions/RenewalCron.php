<?php

namespace NGD_THEME\Functions;

if ( ! defined( 'ABSPATH' ) ) exit;

use GuzzleHttp\Client;

class RenewalCron {

    public function __construct() {
        // Register the daily schedule if it doesn't exist
        if ( ! wp_next_scheduled( 'ngd_daily_renewal_check' ) ) {
            wp_schedule_event( time(), 'daily', 'ngd_daily_renewal_check' );
        }

        // Hook the function to the schedule
        add_action( 'ngd_daily_renewal_check', [ $this, 'process_renewals' ] );
    }

    public function process_renewals() {
        // --- CONFIGURATION ---
        $days_before_expire = 30; // How many days before expiry to invoice?
        $target_package_id  = 247687; // REPLACE THIS: The ID of your Paid Listing Package
        $brevo_api_key      = defined('NGD_BREVO_API_KEY') ? NGD_BREVO_API_KEY : ''; // REPLACE THIS
        // ---------------------

        // Find listings expiring in exactly 30 days
        $args = [
            'post_type'   => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query'  => [
                'relation' => 'AND',
                [
                    'key'     => '_package_id',
                    'value'   => $target_package_id,
                    'compare' => '='
                ],
                [
                    'key'     => '_job_expires',
                    'value'   => date( 'Y-m-d', strtotime( "+$days_before_expire days" ) ), 
                    'compare' => 'LIKE'
                ],
                [
                    // Prevent double invoicing
                    'key'     => '_current_year_invoice_sent',
                    'value'   => date('Y'),
                    'compare' => '!=' 
                ]
            ]
        ];

        $listings = get_posts( $args );

        foreach ( $listings as $listing ) {
            $this->generate_invoice( $listing, $brevo_api_key );
        }
    }

    private function generate_invoice( $listing, $api_key ) {
        $user_id = $listing->post_author;
        $user_email = get_the_author_meta( 'user_email', $user_id );
        
        if ( ! $user_email ) return;

        // Generate Reference: SCH-[UserID]-[Random]
        $unique_ref = 'SCH-' . $user_id . '-' . rand( 1000, 9999 );
        $amount = '4999.00'; 

        // Save to WordPress
        update_post_meta( $listing->ID, '_renewal_reference', $unique_ref );
        update_post_meta( $listing->ID, '_payment_status', 'DUE' );
        update_post_meta( $listing->ID, '_current_year_invoice_sent', date('Y') );

        // Send to Brevo
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
                        'PAYMENT_STATUS'  => 'DUE',
                        'INVOICE_AMOUNT'  => $amount,
                        'PAYMENT_REF'     => $unique_ref,
                        'RENEWAL_DATE'    => get_post_meta( $listing->ID, '_job_expires', true ),
                        'SCHOOL_NAME'     => $listing->post_title
                    ]
                ],
            ]);
        } catch ( \Exception $e ) {
            error_log( "Brevo Error: " . $e->getMessage() );
        }
    }
}
