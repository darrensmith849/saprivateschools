<?php

namespace MyListing\Src\Claims;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Claim_Listing_Form extends \MyListing\Src\Forms\Base_Form {
	use \MyListing\Src\Traits\Instantiatable;

	public
		$step = 0,
		$listing,
		$claim_id,
		$listing_id;

	public function __construct() {
		$this->listing_id = ! empty( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$this->listing = \MyListing\Src\Listing::get( $this->listing_id );
		if ( ! ( $this->listing_id && $this->listing && $this->listing->type ) ) {
			return;
		}

		// get fields from listing type object
		$fields = $this->parse_fields( $this->listing->type->get_claim_fields() );

		$this->fields = $fields;

		$this->form_name = 'case27_paid_listing_submit_claim';

		// steps
		$this->steps = (array) [
			'login_register' => [
				'name'     => __( 'Login / Register', 'my-listing' ),
				'view'     => [ $this, 'login_register_view' ],
				'handler'  => [ $this, 'login_register_handler' ],
				'priority' => 1,
				// 'submit'   => __( 'Register Account &rarr;', 'my-listing' ),
			],
			'claim_package' => [
				'name'     => __( 'Choose a package', 'my-listing' ),
				'view'     => [ $this, 'claim_package_view' ],
				'handler'  => [ $this, 'claim_package_handler' ],
				'priority' => 2,
				'submit'   => __( 'Select Package &rarr;', 'my-listing' ),
			],
			'claim_listing' => [
				'name'     => __( 'Claim Listing', 'my-listing' ),
				'view'	=> false,
				'handler'     => [ $this, 'claim_listing_view' ],
				'priority' => 4,
				'submit'   => __( 'Submit Claim &rarr;', 'my-listing' ),
			],
		];

		// If claim already set, move to next step.
		if ( $this->fields && ! isset( $_GET['_claim_id'] ) && $this->listing->is_claimable() ) {
			$this->steps['submit']  = [
				'name'     => __( 'Submit Details', 'my-listing' ),
				'view'     => [ $this, 'submit' ],
				'handler'  => [ $this, 'submit_handler' ],
				'priority' => 3,
			];
		}

		uasort( $this->steps, [ $this, 'sort_by_priority' ] );

		// Get step.
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}
	}

	public function get_form_fields() {
		return $this->fields;
	}

	public function get_job_id() {
		return absint( $this->listing_id );
	}

	/**
	 * Login Register View
	 *
	 * @since 1.6
	 */
	public function login_register_view() {
		//
	}

	/**
	 * Login Register Handler. Also handle all request.
	 *
	 * @since 1.6
	 */
	public function login_register_handler() {
		mlog('claim handler');

		// Current Claim URL:
		$claim_url = add_query_arg( 'listing_id', $this->listing->get_id(), get_permalink() );

		// If not logged in, redirect user to login/register page.
		if ( ! is_user_logged_in() ) {
			return wp_safe_redirect( add_query_arg( [
				'redirect' => $claim_url,
				'notice' => 'login-required',
			], \MyListing\get_login_url() ) );
		}

		// User visiting claim page to claim a listing, check if they already submit claim.
		if ( ! isset( $_GET['_claim_id'] ) && is_user_logged_in() ) {
			$claims = get_posts( [
				'post_type' => 'claim',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_listing_id', 'value' => absint( $this->listing_id ) ],
					[ 'key' => '_user_id', 'value' => absint( get_current_user_id() ) ],
				],
			] );

			if ( $claims && isset( $claims[0] ) ) {
				wp_safe_redirect( esc_url_raw( add_query_arg( '_claim_id', absint( $claims[0] ), $claim_url ) ) );
				exit;
			}
		}

		// Go to next step.
		$this->next_step();

		// If claim already set, move to next step.
		if ( isset( $_GET['_claim_id'] ) || ! $this->listing->is_claimable() ) {
			$this->next_step();
			return;
		}

		if ( ! $this->listing->is_claimable() ) {
			return $this->add_error( _x( 'This listing cannot be claimed.', 'Claim listing form', 'my-listing' ) );
		}

		if ( ! empty( $this->fields ) && ! empty( $_REQUEST['listing_package'] ) ) {
			$claim_id = isset( $_GET['_claim_id'] ) ? $_GET['_claim_id'] : false;

			if ( empty( $claim_id ) ) {

				try {
					if ( ! \MyListing\Src\Paid_Listings\Util::validate_package( $_REQUEST['listing_package'], $this->listing->type->get_slug() ) ) {
						throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
					}

					// Package is valid.
					$package = get_post( $_REQUEST['listing_package'] );

					// Store selection in cookie.
					wc_setcookie( 'chosen_package_id', absint( $package->ID ) );

					// Go to next step.
					$this->next_step();
				} catch (\Exception $e) {
					return $this->add_error( $e->getMessage() );
				}
			}
		} elseif ( ! empty( $_REQUEST['listing_package'] ) ) {
			if ( ! \MyListing\Src\Paid_Listings\Util::validate_package( $_REQUEST['listing_package'], $this->listing->type->get_slug() ) ) {
				return $this->add_error( _x( 'Failed to create claim - not a valid package.', 'Claim listing form', 'my-listing' ) );
			}

			// package is valid
			$package = get_post( $_REQUEST['listing_package'] );

			// user package selected
			if ( $package->post_type === 'case27_user_package' && is_user_logged_in() ) {
				try {
					do_action( 'mylisting/payments/claim/use-available-package', $this->listing, \MyListing\Src\Package::get( $package ) );
				} catch ( \Exception $e ) {
					return $this->add_error( $e->getMessage() );
				}
			}

			// product selected
			if ( $package->post_type === 'product' ) {
				$product = wc_get_product( $package );
				if ( ! ( $product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) && get_post_meta( $product->get_id(), '_use_for_claims', true ) ) ) {
					return $this->add_error( _x( 'Invalid product selected.', 'Claim listing form', 'my-listing' ) );
				}

				$skip_checkout = apply_filters( 'mylisting\packages\free\skip-checkout', true ) === true;

				// If `skip-checkout` setting is enabled for free products,
				// create the user package and assign it to the listing.
				if ( $product->get_price() == 0 && $skip_checkout && $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
					do_action( 'mylisting/payments/claim/use-free-package', $this->listing, $product );
				} else {
					// proceed to checkout
					do_action( 'mylisting/payments/claim/product-selected', $this->listing, $product );
				}
			}
		}
	}

	/**
	 * Select Claim Package View.
	 *
	 * @since 1.6
	 */
	public function claim_package_view() {
		$listing = \MyListing\Src\Listing::get( $this->listing_id );
		if ( ! ( $listing && $listing->type && $listing->is_claimable() ) ) {
			echo '<div class="job-manager-error">' . __( 'This listing cannot be claimed.', 'my-listing' ) . '</div>';
			return;
		}

		$tree = \MyListing\Src\Paid_Listings\Util::get_package_tree_for_listing_type( $listing->type, 'claim-listing' );
		$tree = array_filter( $tree, function( $item ) {
			return $item['product']->use_for_claims();
		} );
		?>
		<section class="i-section c27-packages">
			<div class="container">
				<div class="row section-title">
					<h2 class="case27-primary-text"><?php _e( 'Choose a Package', 'my-listing' ) ?></h2>
					<p><?php _e( 'Pricing', 'my-listing' ) ?></p>
				</div>
				<form method="post" id="job_package_selection">
					<div class="job_listing_packages">
						<?php require locate_template( 'templates/add-listing/choose-package.php' ) ?>
					</div>
				</form>
			</div>
		</section>
		<?php
	}

	public function submit() {

		foreach ( $this->fields as $key => $field ) {
			// form has been submitted, value is retrieved from $_POST through `validate_fields` method.
			if ( isset( $field->props['value'] ) ) {
				continue;
			}

			$this->fields[ $key ]->props['value'] = $field->get_value();
		}

		mylisting_locate_template( 'templates/claim-form/submit-form.php', [
			'form' => $this->form_name,
			'job_id' => $this->get_job_id(),
			'action' => $this->get_action(),
			'fields' => $this->fields,
			'step'=> $this->get_step()
		] );
	}

	/**
	 * Handles the submission of form data.
	 *
	 * @throws \Exception On validation error.
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_job'] ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['listing_package'] ) ) {
			wc_setcookie( 'chosen_package_id', absint( $_REQUEST['listing_package'] ) );
		}

		$listing = \MyListing\Src\Listing::get( $this->listing_id );
		$type = $listing ? $listing->type : false;

		$this->listing_type = $type;

		// if field validation throws any errors, cancel submission
		$this->validate_fields();
		if ( ! empty( $this->errors ) ) {
			return;
		}
		
		// successful, show next step
		$this->step++;
	}

	public function update_claim_data( $claim_id ) {
		foreach ( $this->fields as $key => $field ) {
			$field->set_claim( $claim_id );
			$field->update();
		}
	}

	public function validate_fields() {
		// check if it's a valid listing type
		if ( ! $this->listing_type ) {
			return $this->add_error( _x( 'Invalid listing type.', 'Claim listing form', 'my-listing' ) );
		}

		if ( is_user_logged_in() && ! \MyListing\Src\User_Roles\user_can_add_listings() ) {
			return $this->add_error( _x( 'You cannot add or edit listings.', 'Claim listing form', 'my-listing' ) );
		}

		// field validation
		foreach ( $this->fields as $key => $field ) {
			// get posted value
			$value = $field->get_posted_value();

			$this->fields[ $key ]->props['value'] = $value;

			// validate values
			try {
				$field->check_validity();
			} catch ( \Exception $e ) {
				return $this->add_error( $e->getMessage() );
			}
		}

		// custom validation rules
		try {
			do_action( 'mylisting/claim/validate-fields', $this->fields );
		} catch( \Exception $e ) {
			return $this->add_error( $e->getMessage() );
		}
	}

	public function parse_fields( $fields ) {
		$fields_array = [];

		foreach ( $fields as $key => $field ) {
			$fields_array[ $field['slug'] ] = $field;
		}

		return $fields_array;
	}

	/**
	 * Claim Listing View. Display claim info.
	 *
	 * @since 1.6
	 */
	public function claim_listing_view() {

		$claim_id = isset( $_GET['_claim_id'] ) ? $_GET['_claim_id'] : false;

		if ( $this->fields && empty( $claim_id ) ) {

			try {
				if ( empty( $_COOKIE['chosen_package_id'] ) ) {
					throw new \Exception( _x( 'Couldn\'t process package.', 'Listing submission', 'my-listing' ) );
				}

				$package = get_post( $_COOKIE['chosen_package_id'] );

				if ( empty( $package ) ) {
					throw new \Exception( _x( 'Invalid package.', 'Listing submission', 'my-listing' ) );
				}

				// use available package
				if ( $package->post_type === 'case27_user_package' ) {
					do_action( 'mylisting/payments/claim/use-available-package', $this->listing, \MyListing\Src\Package::get( $package ) );
				}

				if ( $package->post_type === 'product' ) {
					$product = wc_get_product( $package );
					if ( ! ( $product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) && get_post_meta( $product->get_id(), '_use_for_claims', true ) ) ) {
						throw new \Exception( _x( 'Invalid product selected.', 'Claim listing form', 'my-listing' ) );
					}

					$skip_checkout = apply_filters( 'mylisting\packages\free\skip-checkout', true ) === true;

					// If `skip-checkout` setting is enabled for free products,
					// create the user package and assign it to the listing.
					if ( $product->get_price() == 0 && $skip_checkout && $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
						do_action( 'mylisting/payments/claim/use-free-package', $this->listing, $product );
					} else {
						// proceed to checkout
						do_action( 'mylisting/payments/claim/product-selected', $this->listing, $product );
					}
				}

				// Go to next step.
				$this->next_step();
			} catch( \Exception $e ) {
				return $this->add_error( $e->getMessage() );
			}
		} else {
			$claim_id = isset( $_GET['_claim_id'] ) ? $_GET['_claim_id'] : false;
			$listing = \MyListing\Src\Listing::get( $this->listing_id );

			// validate listing
			if ( ! ( $listing && $listing->type && $listing->is_claimable() ) ) {
				echo '<div class="job-manager-error">' . __( 'This listing cannot be claimed.', 'my-listing' ) . '</div>';
				return;
			}

			// validate claim
			$claim = get_post( $claim_id );
			if ( ! $claim || 'claim' !== $claim->post_type || absint( $listing->get_id() ) !== absint( $claim->_listing_id ) || absint( get_current_user_id() ) !== absint( $claim->_user_id ) ) {
				echo '<div class="job-manager-error">' . __( 'Invalid claim.', 'my-listing' ) . '</div>';
				return;
			}

			// valid, perform redirect
			$redirect_url = add_query_arg( 'claim-id', $claim->ID, wc_get_account_endpoint_url( _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ) ) );
			?>
			<script type="text/javascript">
			    window.location = <?php echo wp_json_encode( $redirect_url ) ?>;
			</script>
			<?php
			exit;
		}
	}
}