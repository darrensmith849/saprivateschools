<?php
/**
 * Template for displaying Listing Package and Listing Package Subscription
 * settings in the Edit Product page in WP Admin.
 *
 * @since 2.1.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;
$listing_types = MyListing\get_listing_types();
$types = [];

foreach ( $listing_types as $type ) {
	$types[ $type->get_slug() ] = [ 
		'title' 		=> $type->get_name(), 
		'meta_value' 	=> get_post_meta( $post->ID, sprintf( '%s_limit', $type->get_slug() ), true ) 
	];
} ?>

<div class="options_group listing-package-options show_if_job_package <?php echo esc_attr( class_exists( '\WC_Subscriptions' ) ? 'show_if_job_package_subscription' : '' );?>">
	<?php

	if ( class_exists( '\WC_Subscriptions' ) ) {
		woocommerce_wp_select( [
			'id' => '_job_listing_package_subscription_type',
			'wrapper_class' => 'show_if_job_package_subscription',
			'label' => __( 'Subscription Type', 'my-listing' ),
			'description' => __( 'Choose how subscriptions affect this package', 'my-listing' ),
			'value' => get_post_meta( $post->ID, '_package_subscription_type', true ),
			'desc_tip' => true,
			'options' => [
				'listing' => __( 'Link the subscription to posted listings (renew posted listings every subscription term)', 'my-listing' ),
				'package' => __( 'Link the subscription to the package (renew listing limit every subscription term)', 'my-listing' ),
			],
		] );
	}

	woocommerce_wp_text_input( [
		'id'                => '_job_listing_limit',
		'label'             => __( 'Listing limit', 'my-listing' ),
		'description'       => __( 'The number of listings a user can post with this package.', 'my-listing' ),
		'value'             => ( $limit = get_post_meta( $post->ID, '_job_listing_limit', true ) ) ? $limit : '',
		'placeholder'       => __( 'Unlimited', 'my-listing' ),
		'type'              => 'number',
		'desc_tip'          => true,
		'custom_attributes' => [ 'min' => '', 'step' => '1' ],
	] );?>
	<div id="repeater">
		<?php foreach ( $types as $slug => $detail ) : 
			if( ! empty( $detail['meta_value'] ) ) : ?>
				<p class="form-field">
					<label for="<?php echo $slug ?>_limit"><?php echo $detail['title'] ?></label>
					<input class="short" type="number" name="<?php echo $slug ?>_limit" id="<?php echo $slug ?>" value="<?php echo $detail['meta_value'] ?>">
					<button class="remove-input"><i class="material-icons delete"></i></button>
				</p>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<div class="select-listing-type">
		<p class="form-field">
			<label for="select-field">Add listing limit by listing type</label>
			<select id="listing_type" name="listing_type" >
				<?php
				foreach ( $listing_types as $index => $type ) : ?>
					<option value="<?php echo $type->get_slug(); ?>"><?php echo $type->get_name(); ?></option>
				<?php endforeach; ?>
				<option value="no-value" selected>Default</option>
			</select>
		</p>
	</div>
	<?php

	woocommerce_wp_text_input( [
		'id'                => '_job_listing_duration',
		'label'             => __( 'Listing duration', 'my-listing' ),
		'description'       => __( 'The number of days that the listing will be active.', 'my-listing' ),
		'value'             => get_post_meta( $post->ID, '_job_listing_duration', true ),
		'placeholder'       => mylisting_get_setting( 'submission_default_duration' ),
		'desc_tip'          => true,
		'type'              => 'number',
		'custom_attributes' => [ 'min' => '', 'step' => '1' ],
	] );

	woocommerce_wp_checkbox( [
		'id'          => '_job_listing_featured',
		'label'       => __( 'Feature Listings?', 'my-listing' ),
		'description' => __( 'Feature this listing - it will be styled differently and sticky.', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_job_listing_featured', true ),
	] );

	woocommerce_wp_checkbox( [
		'id'          => '_listing_mark_verified',
		'label'       => __( 'Mark as verified?', 'my-listing' ),
		'description' => __( 'Listings with this package will have a verified badge show next to their title.', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_listing_mark_verified', true ),
	] );

	woocommerce_wp_checkbox( [
		'id'          => '_use_for_claims',
		'label'       => __( 'Use for Claim?', 'my-listing' ),
		'description' => __( 'Allow this package to be an option for claiming a listing.', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_use_for_claims', true ),
	] );


	woocommerce_wp_checkbox( [
		'id'          => '_hide_in_add_listing',
		'label'       => __( 'Don\'t use for add listing?', 'my-listing' ),
		'description' => __( 'Remove this package from add listing process, this will allow you to use package for claiming listings only', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_hide_in_add_listing', true ),
	] );

	woocommerce_wp_checkbox( [
		'id'          => '_disable_repeat_purchase',
		'label'       => __( 'Disable repeat purchase?', 'my-listing' ),
		'description' => __( 'If checked, this package can only be bought once per user. This can be useful for free listing packages, where you only want to allow the free package to be used once.', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_disable_repeat_purchase', true ),
	] ); 

	woocommerce_wp_checkbox( [
		'id'          => '_is_claimable',
		'label'       => __( 'Is claimable?', 'my-listing' ),
		'description' => __( 'Allow listings added with this package to still be claimable.', 'my-listing' ),
		'value'       => get_post_meta( $post->ID, '_is_claimable', true ),
	] );

	?>
	<?php printf(
			'<script type="text/javascript">var Listing_Types = %s;</script>',
			wp_json_encode( $types ) );	?>

	<script type="text/javascript">

		const repeater = document.getElementById('repeater');
		const addInputButton = document.getElementById('add-input');
		const selectField = document.getElementById('listing_type');
		const limitField = document.getElementById('_job_listing_limit');
		const removeButtons = document.querySelectorAll('.remove-input');
		 restrictLimitsEvent();

		function cutOffValuesOnDecMainLimit( event ){
			const limitFieldValue = Math.abs(limitField.value);
			const allInputs = repeater.querySelectorAll('input[type="number"]');
			var listingTypeLimits = 0;
			
			allInputs.forEach( input => {
				listingTypeLimits += Math.abs(input.value);
		 	});

		 	if( limitFieldValue < listingTypeLimits ){
		 		let flag = true
				let diff = listingTypeLimits - Math.abs(event.currentTarget.value);
		 		allInputs.forEach( input => {
		 			if( diff <= 0  ) return;
		 			let inputValue = Math.abs( input.value );
					input.value = ( inputValue < diff ) ? 0 : inputValue - diff ;
					diff = ( diff > inputValue ) ? diff - inputValue : 0;
			 	});
		 	}
		}

		function addInput( event ) {
			if( event.currentTarget.value === 'no-value' ||
				document.getElementById(event.currentTarget.value) !== null || 
				! VerifyBeforeAddingLimitField() ) return;

		  	const newInput = document.createElement('p');
		  	newInput.className ='form-field';
		  	newInput.innerHTML = `
		    	<label for="${event.currentTarget.value}_limit">${Listing_Types[event.currentTarget.value].title}</label>
		    	<input class="short" type="number" name="${event.currentTarget.value}_limit" id="${event.currentTarget.value}">
		    	<button class="remove-input"><i class="material-icons delete"></i></button>
		  	`;

		  	repeater.appendChild(newInput);
		  	// Add event listener to new "remove" button
		  	const removeButtons = document.querySelectorAll('.remove-input');
		  	removeButtons.forEach( button => {
		    button.addEventListener('click', removeInput);
		    restrictLimitsEvent();
		  });
		}

		function VerifyBeforeAddingLimitField(){
			const limitFieldValue = Math.abs(limitField.value);
			const allInputs = repeater.querySelectorAll('input[type="number"]');
			var listingTypeLimits = 0;
			
			allInputs.forEach( input => {
				listingTypeLimits += Math.abs(input.value);
		 	});

		 	if( listingTypeLimits >= limitFieldValue ){
		 		alert( `You have reached the limit for this package (${limitFieldValue}). Increase "Listing limit" to add more listing types` )
		 		return false;
		 	}

			return true;
		}

		function restrictLimitsEvent(){
			const allInputs = repeater.querySelectorAll('input[type="number"]');
			allInputs.forEach( input => {
				input.addEventListener('change', restrictLimit);
		 	});
		}

		function restrictLimit( event ){
			const limitFieldValue = Math.abs(limitField.value);
			const allInputs = repeater.querySelectorAll('input[type="number"]');
			var listingTypeLimits = 0;
			
			allInputs.forEach( input => {
				listingTypeLimits += Math.abs(input.value);
		 	});

		 	if( listingTypeLimits > limitFieldValue ){
		 		let diff = listingTypeLimits - limitFieldValue;
		 		event.currentTarget.value = event.currentTarget.value - diff;
		 	}
		}

		function removeInput(event) {
		  event.target.parentNode.remove();
		}

		selectField.addEventListener('change', addInput);
		removeButtons.forEach(button => {
		    button.addEventListener('click', removeInput);
		 });
		 limitField.addEventListener('change', cutOffValuesOnDecMainLimit);

		jQuery( function( $ ) {
			$( '.pricing' ).addClass( 'show_if_job_package' );
			$( '._tax_status_field' ).closest( 'div' ).addClass( 'show_if_job_package' );
			$( '#product-type' ).change( function(e) {
				$('#_job_listing_package_subscription_type').change();
			} ).change();
			<?php if ( class_exists( '\WC_Subscriptions' ) ) : ?>
				$('._tax_status_field').closest('div').addClass( 'show_if_job_package_subscription' );
				$('.show_if_subscription, .options_group.pricing').addClass( 'show_if_job_package_subscription' );

				$( '#product-type' ).change( function(e) {
					$type = $.inArray( $(this).val(), [ 'subscription', 'variable-subscription', 'job_package_subscription' ] );
					if ( $type !== -1 ) {
						$(".show_if_job_package_subscription").show();
					} else {
						$(".options_group.limit_subscription.show_if_job_package_subscription").hide();
					}
				} ).change();

				$('#_job_listing_package_subscription_type').change(function() {
					if ( $( '#product-type' ).val() === 'job_package' ) {
						$('#_job_listing_duration').closest('.form-field').show();
						return;
					}

					if ( $(this).val() === 'listing' ) {
						$('#_job_listing_duration').closest('.form-field').hide().val('');
					} else {
						$('#_job_listing_duration').closest('.form-field').show();
					}
				}).change();
				$( 'select#product-type' ).trigger('change');
			<?php endif; ?>
		} );
	</script>
</div>