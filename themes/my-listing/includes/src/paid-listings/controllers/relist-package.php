<?php
/**
 * Allow relist the listing package.
 *
 * @since 1.0
 */

namespace MyListing\Src\Paid_Listings\Controllers;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Relist_Package {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_filter( 'mylisting/submission-steps', [ $this, 'submission_steps' ], 150 );
		add_filter( 'mylisting/user-listings/actions', [ $this, 'display_relist_action' ], 40 );

		add_action( 'mylisting/payments/relist/use-without-package', [ $this, 'use_without_package' ], 10 );
	}

	public function submission_steps( $steps ) {
		$actions = [ 'relist' ];
		if ( empty( $_GET['action'] ) || ! in_array( $_GET['action'], $actions ) ) {
			return $steps;
		}

		return [ 'relist-package' => [
			'name'     => _x( 'Choose a package', 'Switch package', 'my-listing' ),
			'view'     => [ $this, 'relist_free_view' ],
			'handler'  => [ $this, 'relist_free_handler' ],
			'priority' => 6,
		] ];

		return $steps;
	}

	public function relist_free_view() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$actions = [ 'relist' ];
		if ( ! is_user_logged_in() || empty( $_GET['action'] ) || ! in_array( $_GET['action'], $actions ) ) {
			echo '<div class="job-manager-error">' . __( 'Invalid request.', 'my-listing' ) . '</div>';
			return;
		}

		if ( empty( $_GET['listing'] ) ) {
			echo '<div class="job-manager-error">' . __( 'Invalid request.', 'my-listing' ) . '</div>';
			return;
		}

		$listing = \MyListing\Src\Listing::get( $_GET['listing'] );
		if ( ! ( $listing && $listing->type && $listing->editable_by_current_user() ) ) {
			echo '<div class="job-manager-error">' . __( 'Something went wrong.', 'my-listing' ) . '</div>';
			return;
		}

		do_action( 'mylisting/payments/relist/use-without-package', $listing );

		// Redirect to user dashboard.
		$message = _x( 'Listing has been successfully relisted.', 'Switch Package', 'my-listing' );

		// valid, perform redirect
		wc_add_notice( $message, 'success' );
		$redirect_url = wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() );
		?>
		<script type="text/javascript">
		    window.location = <?php echo wp_json_encode( $redirect_url ) ?>;
		</script>
		<?php
		exit;
	}

	public function display_relist_action( $listing ) {
		if ( ! ( $listing->type && in_array( $listing->get_status(), [ 'expired' ] ) ) ) {
			return;
		}

		if ( ! ( $add_listing_page = c27()->get_setting( 'general_add_listing_page' ) ) ) {
			return;
		}

		if ( apply_filters( 'mylisting/display-relist-action', true, $listing ) === false ) {
			return;
		}

		$switch_url = add_query_arg( [
			'action' => 'relist',
			'listing' => $listing->get_id(),
		], $add_listing_page );

		printf(
			'<li class="cts-listing-action-relist">
				<a href="%s" class="listing-action-switch">%s</a>
			</li>',
			esc_url( $switch_url ),
		    _x( 'Relist', 'User listings dashboard', 'my-listing' )
		);
	}

	public function use_without_package( $listing ) {
		$package = \MyListing\Src\Package::create( [
			'user_id'        => get_current_user_id(),
			'order_id'       => false,
		] );

		if ( ! $package ) {
			throw new \Exception( _x( 'Couldn\'t create package.', 'Listing submission', 'my-listing' ) );
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => 'publish',
		] );

		$package->assign_to_listing( $listing->get_id() );
	}
}
