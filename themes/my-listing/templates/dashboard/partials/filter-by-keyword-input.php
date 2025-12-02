<?php
/**
 * Display the input to search by keyword.
 *
 * @since 2.9.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="col-md-2 search-my-listings">
	<form method="GET">
		<input placeholder="<?php echo esc_attr( _ex( 'Search your listings', 'User dashboard search input placeholder', 'my-listing' ) ) ?>" type="text" name="search" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : '' ?>">
		<button type="submit"><i class="mi search"></i></button>
	</form>
</div>
