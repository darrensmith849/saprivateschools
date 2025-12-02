<?php
/**
 * Dashboard `My Account` page template.
 *
 * @since 2.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( ! \MyListing\Src\User_Roles\user_can_add_listings() ) {
	return require locate_template( 'templates/dashboard/dashboard-alt.php' );
}

// Get logged-in user stats.
$stats = mylisting()->stats()->get_user_stats( get_current_user_id() );
$user_id = get_current_user_id();
$endpoint = wc_get_account_endpoint_url( 'dashboard' );
$active_state = '';
if ( ! empty( $_GET['state_type'] ) && is_super_admin() ) {
	$active_state = sanitize_text_field( $_GET['state_type'] );

	if ( $active_state === 'sitewide' ) {
		$user_id = '';
		// Get logged-in user stats.
		$stats = mylisting()->stats()->get_admin_stats();
	}
}

// Filter dashboard stats by listing.
if ( ! empty( $_GET['listing'] ) && ( $listing = \MyListing\Src\Listing::get( $_GET['listing'] ) ) && $listing->editable_by_current_user() ) {
	return require locate_template( 'templates/dashboard/stats/single-listing.php' );
}

$wrapper_class = 'col-md-9';
if ( is_super_admin() ) {
	$wrapper_class = 'col-md-7';
}
?>

<div class="row">
	<div class="<?php echo esc_attr( $wrapper_class ); ?> mlduo-welcome-message">
		<h1>
			<?php printf( _x( 'Hello, %s!', 'Dashboard welcome message', 'my-listing' ), apply_filters(
				'mylisting/dashboard/greeting/username',
				trim( $current_user->user_firstname )
					? $current_user->user_firstname
					: $current_user->user_login,
				$current_user
			) ) ?>
		</h1>
	</div>
	<?php require locate_template( 'templates/dashboard/partials/filter-by-type-stats.php' ) ?>
	<div class="col-md-3">
		<?php require locate_template( 'templates/dashboard/stats/select-listing.php' ) ?>
	</div>
</div>

<div class="row my-account-stat-box">
	<?php
	// Published listing count.
	if ( mylisting()->get( 'stats.show_published_listings' ) !== false ) {
		mylisting_locate_template( 'templates/dashboard/stats/card.php', [
			'icon' => 'icon-window',
			'value' => number_format_i18n( absint( $stats->get( 'listings.published' ) ) ),
			'description' => _x( 'Published Listings', 'Dashboard stats', 'my-listing' ),
			'background' => mylisting()->get( 'stats.color1' ),
			'classes' => 'stat-card-published-listings',
			'link' => add_query_arg( 'status', 'publish', wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) ),
		] );
	}

	// Pending listing count (pending_approval + pending_payment).
	if ( mylisting()->get( 'stats.show_pending_listings' ) !== false ) {
		mylisting_locate_template( 'templates/dashboard/stats/card.php', [
			'icon' => 'icon-pencil-ruler',
			'value' => number_format_i18n( absint( $stats->get( 'listings.pending' ) ) ),
			'description' => _x( 'Pending Listings', 'Dashboard stats', 'my-listing' ),
			'background' => mylisting()->get( 'stats.color2' ),
			'classes' => 'stat-card-pending-listings',
			'link' => add_query_arg( 'status', 'pending', wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) ),
		] );
	}

	// Promoted listing count.
	if ( mylisting()->get( 'stats.show_active_promotions' ) !== false ) {
		mylisting_locate_template( 'templates/dashboard/stats/card.php', [
			'icon' => 'icon-flash',
			'value' => number_format_i18n( absint( $stats->get( 'promotions.count' ) ) ),
			'description' => _x( 'Active Promotions', 'Dashboard stats', 'my-listing' ),
			'background' => mylisting()->get( 'stats.color3' ),
			'classes' => 'stat-card-active-promotions',
			'link' => wc_get_account_endpoint_url( \MyListing\promotions_endpoint_slug() ),
		] );
	}

	// Recent views card.
	if ( mylisting()->get( 'stats.show_visits_this_week' ) !== false ) {
		mylisting_locate_template( 'templates/dashboard/stats/card.php', [
			'icon' => 'mi graphic_eq',
			'value' => number_format_i18n( absint( $stats->get( 'visits.views.lastweek' ) ) ),
			'description' => _x( 'Visits this week', 'Dashboard stats', 'my-listing' ),
			'background' => mylisting()->get( 'stats.color4' ),
			'classes' => 'stat-card-visits',
		] );
	}
	?>
</div>

<div class="row">
	<div class="col-md-4">
		<?php if ( mylisting()->get( 'stats.show_views' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/views.php' ) ?>
		<?php endif ?>

		<?php if ( mylisting()->get( 'stats.show_uviews' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/unique-views.php' ) ?>
		<?php endif ?>

		<?php if ( mylisting()->get( 'stats.show_tracks' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/tracks-by-type.php' ) ?>
		<?php endif ?>

		<?php if ( mylisting()->get( 'stats.show_devices' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/devices.php' ) ?>
		<?php endif ?>

		<?php if ( mylisting()->get( 'stats.show_countries' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/countries.php' ) ?>
		<?php endif ?>
	</div>

	<div class="col-md-8">

		<?php if ( mylisting()->get( 'stats.enable_chart' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/visits-chart.php' ) ?>
		<?php endif ?>

		<?php if ( mylisting()->get( 'stats.show_referrers' ) !== false ): ?>
			<?php require locate_template( 'templates/dashboard/stats/widgets/referrers.php' ) ?>
		<?php endif ?>

		<div class="row custom-row">
			<?php if ( mylisting()->get( 'stats.show_platforms' ) !== false ): ?>
				<div class="col-md-6">
					<?php require locate_template( 'templates/dashboard/stats/widgets/platforms.php' ) ?>
				</div>
			<?php endif ?>

			<?php if ( mylisting()->get( 'stats.show_browsers' ) !== false ): ?>
				<div class="col-md-6">
					<?php require locate_template( 'templates/dashboard/stats/widgets/browsers.php' ) ?>
				</div>
			<?php endif ?>
		</div>
	</div>
</div>

<?php
// Support WooCommerce dashboard hooks.
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );