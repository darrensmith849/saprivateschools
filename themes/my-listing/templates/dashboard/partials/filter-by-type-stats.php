<?php
/**
 * Display the "Filter by Listing Stats" dropdown.
 *
 * @since 2.10
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

if ( ! is_super_admin() ) {
	return false;
}
?>
<div class="col-md-2 sort-my-listings">
	<select class="custom-select filter-stats-select" required="required">
		<option value="<?php echo esc_url( $endpoint ) ?>" <?php selected( $active_state === '' ) ?>>
			<?php _ex( 'Stats Type', 'User dashboard', 'my-listing' ) ?>
		</option>

		<option value="<?php echo esc_url( add_query_arg( 'state_type', 'sitewide', $endpoint ) ) ?>"
			<?php selected( $active_state === 'sitewide' ) ?>
		>
			<?php echo esc_html__( 'Sitewide', 'my-listing' ); ?>
		</option>
		<option
				value="<?php echo esc_url( add_query_arg( 'state_type', 'account', $endpoint ) ) ?>"
			<?php selected( $active_state === 'account' ) ?>
		>
			<?php echo esc_html__( 'Account', 'my-listing' ); ?>
		</option>
	</select>
</div>
