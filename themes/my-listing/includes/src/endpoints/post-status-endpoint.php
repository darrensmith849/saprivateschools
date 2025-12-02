<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Post_Status_Endpoint {

	public function __construct() {
		add_action( 'mylisting_ajax_post_status', [ $this, 'handle' ] );
	}

	public function handle() {
		mylisting_check_ajax_referrer();

		$listing_id = $_POST['listing_id'] ?? null;
		$listing = \MyListing\Src\Listing::get( $listing_id );

		if ( ! ( $listing && $listing->get_author_id() === get_current_user_id() ) ) {
			return;
		}

		$post_status = $listing->get_status();

		$details = array_filter( [
			'ID' 			=> $listing->get_id(),
			'post_status'   => $post_status == 'publish' ? 'unpublish' : 'publish'
		] );

		wp_update_post( $details );

		clean_post_cache( $listing->get_id() );

		$redirect_url = add_query_arg( [
			'action' => 'status',
			'job_id' => $listing->get_id(),
			'_wpnonce'	=> wp_create_nonce('mylisting_dashboard_actions')
		], wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) );

		return wp_send_json( [
			'success' => true,
			'redirect' => $redirect_url
		] );
	}
}