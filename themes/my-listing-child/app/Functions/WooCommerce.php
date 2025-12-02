<?php

namespace NGD_THEME\Functions;

use Exception;
use MyListing\Src\Listing;
use JetBrains\PhpStorm\NoReturn;
use Automattic\WooCommerce\Blocks\Package;
use function MyListing\get_preview_card;

class WooCommerce {

	public function __construct( $run_hooks = false ) {
		if ( $run_hooks ) {
			$this->run_hooks();
		}
	}

	public function run_hooks(): void {
		add_filter( 'product_type_selector', [ $this, 'add_custom_product_type' ] );
		add_filter( 'woocommerce_product_class', [ $this, 'register_custom_product_type' ], 10, 2 );
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_custom_product_data_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_custom_product_data_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_pricing_table_custom_field' ] );
		add_shortcode( 'advertisement_popup', [ $this, 'render_advertisement_popup' ] );
		add_shortcode( 'sspi_promoted_schools', [ $this, 'render_promoted_schools' ] );

		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_product_custom_field' ] );
		add_filter( 'woocommerce_is_purchasable', [ $this, 'filter_is_purchasable' ], 10, 2 );
		add_action( "woocommerce_advertisement_product_add_to_cart", [ $this, 'add_to_cart_template' ] );


		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 4 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_custom_meta_to_order_item' ], 10, 4 );
		add_filter( 'woocommerce_cart_item_name', [ $this, 'display_cart_item_data' ], 10, 3 );
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 10, 1 );
		add_filter( 'woocommerce_cart_item_price', [ $this, 'override_mini_cart_price' ], 10, 3 );


		add_action( 'woocommerce_order_status_completed', [ $this, 'reserve_advertisement_slot' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'reserve_advertisement_slot' ] );


		add_action( 'wp_ajax_update_regions_slots', [ $this, 'update_regions_slots' ] );
		add_action( 'wp_ajax_nopriv_update_regions_slots', [ $this, 'update_regions_slots' ] );


		add_action( 'wp_ajax_update_slot_prices', [ $this, 'update_slot_prices' ] );
		add_action( 'wp_ajax_nopriv_update_slot_prices', [ $this, 'update_slot_prices' ] );

		add_action( 'wp_ajax_update_single_product_price', [ $this, 'update_single_product_price' ] );
		add_action( 'wp_ajax_nopriv_update_single_product_price', [ $this, 'update_single_product_price' ] );

		add_action( 'wp_ajax_add_advertisement_product_to_cart', [ $this, 'add_advertisement_product_to_cart' ] );
		add_action( 'wp_ajax_nopriv_add_advertisement_product_to_cart', [
			$this,
			'add_advertisement_product_to_cart'
		] );

	}

	public function add_custom_product_type( $types ) {
		$types['advertisement_product'] = 'Advertisement Product';

		return $types;
	}

	public function register_custom_product_type( $classname, $product_type ) {
		if ( $product_type === 'advertisement_product' ) {
			$classname = 'NGD_THEME\Functions\AdvertisementProduct';
		}

		return $classname;
	}

	// Add a custom tab to the product data metabox
	public function add_custom_product_data_tab( $tabs ): array {
		$tabs['general']['class'][]        = 'show_if_advertisement_product';
		$tabs['inventory']['class'][]      = 'show_if_advertisement_product';
		$tabs['shipping']['class'][]       = 'hide_if_advertisement_product';
		$tabs['linked_product']['class'][] = 'hide_if_advertisement_product';
		$tabs['attribute']['class'][]      = 'hide_if_advertisement_product';
		$tabs['variations']['class'][]     = 'hide_if_advertisement_product';
		$tabs['advanced']['class'][]       = 'hide_if_advertisement_product';
		$tabs['pricing_table_options']     = [
			'label'    => __( 'Pricing Table', 'crewter' ),
			'target'   => 'pricing_table_product_data',
			'class'    => [ 'show_if_advertisement_product' ],
			'priority' => 60,
		];

		return $tabs;
	}

	// Add fields to the custom tab
	public function add_custom_product_data_fields(): void {
		ob_start();
		?>
		<div id="pricing_table_product_data" class="panel woocommerce_options_panel hidden" style="display: none;">
			<div class="options_group">
				<?php echo $this->generateDataTable(); ?>
			</div>
		</div>
		<?php

		echo ob_get_clean();
	}

	// Save the custom field data
	public function save_pricing_table_custom_field( $post_id ): void {
		$pricing_table = $_POST['sspi_advertisement_pricing'] ?? [];
		update_post_meta( $post_id, 'sspi_advertisement_pricing', $pricing_table );

		// Set Dummy Prices for testing purposes
		/*

				$months = $this->getSimpleMonths();
				$possible_slots = $this->getPossibleSlots();
				$advertising_pricing_temp = [];
				foreach ( $months as $month_key => $month ) {
					foreach ( $possible_slots['homepage'] as $key => $slot ) {
						$price_key                       = $month_key . '_' . $key;
						if ($month_key == 'base') {
							$price = 100;
						}
						else {
							$price = (rand(1, 100) <= 30) ? 100 : rand(15, 99);
						}
						$advertising_pricing_temp[$price_key] = $price;
					}
					foreach ( $possible_slots['regions'] as $key => $slot ) {
						$price_key                       = $month_key . '_' . $key;
						if ($month_key == 'base') {
							$price = 100;
						}
						else {
							$price = (rand(1, 100) <= 30) ? 100 : rand(15, 99);
						}
						$advertising_pricing_temp[$price_key] = $price;
					}
				}
				update_post_meta( $post_id, 'sspi_advertisement_pricing', $advertising_pricing_temp );*/
	}

	#[NoReturn] function update_regions_slots(): void {

		check_ajax_referer( 'ngd_theme_nonce', 'nonce' );

		$region_slugs = esc_attr__( $_REQUEST['region'] ?? '' );


		$term_slug = '';

		if ( count( explode( ',', $region_slugs ) ) > 0 ) {
			$term_slug = explode( ',', $region_slugs )[0];
		}

		// Retrieve the term by slug
		$term = get_term_by( 'slug', $term_slug, 'greater-region' );

		$region_id = $term ? $term->term_id : '';

		$response = [
			'status' => 'OK',
			'html'   => $this->render_promoted_schools( [
				'handling_ajax'   => true,
				'include_wrapper' => true,
				'region_id'       => $region_id
			] ),
		];

		// Return the HTML
		echo json_encode( $response );

		// Always die in functions hooked to wp_ajax_
		wp_die();
	}

	#[NoReturn] function update_slot_prices(): void {

		check_ajax_referer( 'ngd_theme_nonce', 'nonce' );

		$slot_type = esc_attr__( $_REQUEST['slot_type'] ?? '' );
		$region_id = esc_attr__( $_REQUEST['region'] ?? '' );
		$section   = esc_attr__( $_REQUEST['section'] ?? '' );
		$position  = esc_attr__( $_REQUEST['position'] ?? '' );
		$age_group = esc_attr__( $_REQUEST['age_group'] ?? '' );
		$school    = esc_attr__( $_REQUEST['select_school'] ?? '' );

		if ( $slot_type == 'homepage' ) {
			$position = $section;
		}

		//slot_priority
		$slot_priority = $this->get_slot_priority_text( $slot_type, $position, $age_group );

		// Get the data sent from the AJAX call
		$response = [
			'status' => 'OK',
			'html'   => $this->getSlotsPricingHtml( slot_priority: $slot_priority, region_id: $region_id, school_id: $school ),
			'post'   => $_POST
		];

		// Return the HTML
		echo json_encode( $response );

		// Always die in functions hooked to wp_ajax_
		wp_die();
	}

	#[NoReturn] function update_single_product_price(): void {

		check_ajax_referer( 'ngd_theme_nonce', 'nonce' );

		$month_year = $_POST['month_year'] ?? '';
		$region     = $_POST['region'] ?? '';
		$section    = $_POST['section'] ?? '';
		$position   = $_POST['position'] ?? '';
		$slot_type  = $_POST['slot_type'] ?? '';
		$age_group  = $_POST['age_group'] ?? '';
		$school_id  = esc_attr__( $_REQUEST['school_id'] ?? '' );

		$month = explode( '_', $month_year )[0];

		if ( $slot_type == 'homepage' ) {
			$position = $section;
		}

		$response = [
			'status' => 'ERROR',
		];

		$slot_priority = $slot_type . '_' . $position . '_' . $age_group;
		$price         = $this->get_slot_price( $month, $slot_priority, $school_id );

		$slot_priority_db = $slot_priority . '_' . $month_year;
		$registered_slot  = self::get_registered_slots(
			priority: $slot_priority_db,
			region_id: (int) $region );
		if ( ! empty( $registered_slot ) ) {
			$response['message'] = 'Slot Already Reserved!';
		}
		if ( empty( $price ) ) {
			$response['message'] = 'Slot not available!';
		}
		if ( $price > 0 && empty( $registered_slot ) ) {
			$response = [
				'status'     => 'OK',
				'price_html' => wc_price( $price ),
			];
		}

		// Return the HTML
		echo json_encode( $response );

		// Always die in functions hooked to wp_ajax_
		wp_die();
	}

	/**
	 * @throws Exception
	 */
	function add_advertisement_product_to_cart(): void {
		$product_id = self::get_advertisement_product_id();
		$return     = [
			'status'  => 'ERROR',
			'message' => __( 'An error occurred while trying to add the advertisement to the cart', '' )
		];
		if ( $product_id ) {

			$quantity = 1;

			$cart_item_data = $this->generate_cart_item_data( 'ajax' );
			if ( ! empty( $cart_item_data['errors'] ) ) {
				ob_start()
				?>
				<div class="advertisement-banner-errors-wrapper">
					<ul class="advertisement-banner-errors-list">
						<?php foreach ( $cart_item_data['errors'] as $error_message ) { ?>
							<li><?php echo $error_message; ?></li>
						<?php } ?>
					</ul>
				</div>
				<?php
				$errors_html = ob_get_clean();
				echo json_encode( [
					'status'  => 'ERROR',
					'message' => $errors_html,
				] );
				wp_die();
			}

			$cart_item_key = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				0,
				[],
				$cart_item_data );

			if ( $cart_item_key ) {
				echo json_encode( [
					'status'       => 'OK',
					'message'      => __( 'Advertisement added to the cart, you will be redirect shortly', 'saprivateschools' ),
					'item_key'     => $cart_item_key,
					'redirect_url' => wc_get_cart_url()
				] );
				wp_die();
			}
			else {
				echo json_encode( [
					'status'  => 'ERROR',
					'message' => __( 'An error occurred while adding the advertisement to the cart', 'saprivateschools' ),
				] );
			}
			exit;
		}
	}


	function render_advertisement_popup( $params ): string {
		$options = [
			'slot_type'     => 'homepage',
			'slot_priority' => "homepage_banner_primary",
			'region_id'     => $_REQUEST['region'] ?? 0,
			'button_text'   => 'See Rates'
		];
		$options = shortcode_atts( $options, $params );
		wp_enqueue_style( 'select-2' );

		ob_start();


		//slot_priority
		$slot_priority      = $this->get_slot_priority_text();
		$slots_pricing_html = $this->getSlotsPricingHtml( $slot_priority )
		?>
		<div class="advertisement-popup-content" style="display: none">
			<div class="advertisement-banner-wrapper">
				<div class="advertisement-banner">
					<div class="advertisement-banner-content">
						<div class="advertisement-banner-loader">
							<div class="advertisement-banner-loader-inner"></div>
						</div>
						<div class="advertisement-banner-details">
							<h2 class="advertisement-banner-heading">
								Featured Banner
							</h2>
							<div class="advertisement-banner-price">
								<strong>Price: </strong>
								<span class="advertisement-banner-price-range"><?php echo $slots_pricing_html['range_html'] ?></span>
							</div>
							<div class="advertisement-banner-text">
								Click the button to book a slot now.
							</div>
							<div class="advertisement-banner-features">
								<ul class="advertisement-banner-features-list">
									<li class="advertisement-banner-features-list-item">
										<i class="bb-icon-check"></i>
										<span>Non-Refundable</span>
									</li>
									<li class="advertisement-banner-features-list-item">
										<i class="bb-icon-check"></i>
										<span>Automatic Switching</span>
									</li>
									<li class="advertisement-banner-features-list-item">
										<i class="bb-icon-check"></i>
										<span>First come, first serve</span>
									</li>
									<li class="advertisement-banner-features-list-item">
										<i class="bb-icon-check"></i>
										<span>Analytics</span>
									</li>
									<li class="advertisement-banner-features-list-item">
										<i class="bb-icon-check"></i>
										<span>Up to 12 months prior</span>
									</li>
								</ul>
							</div>
							<?php echo $this->render_advertisement_product_options( $options['slot_priority'] ) ?>
						</div>
						<div class="advertisement-banner-buttons-wrapper">
							<div class="advertisement-banner-errors"></div>
							<div class="advertisement-banner-buttons">
								<?php echo $slots_pricing_html['buttons_html']; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<button class="advertisement-popup-opener buttons button-2"><?php echo $options['button_text']; ?></button>
		<?php
		return ob_get_clean();
	}

	function render_promoted_schools( $params ): string {
		$options = [
			'type'            => 'regions',
			'position'        => 'banner',
			'age_group'       => 'primary',
			'include_wrapper' => true,
			'region_id'       => false,
			'handling_ajax'   => false
		];
		$options = shortcode_atts( $options, $params );

		ob_start();
		$year       = date( 'Y' );
		$month      = strtolower( date( 'F' ) );
		$month_year = $month . '_' . $year;
		$region_id  = $this->get_term_id_from_url();

		if ( $options['handling_ajax'] ) {
			$region_id = $options['region_id'];
		}

		if ( $options['type'] == 'regions' && empty( $region_id ) ) {
			return '';
		}

		if ( $options['type'] == 'regions' && $region_id > 0 ) {
			$age_groups = [
				'pre',
				'primary',
				'high',
			];
			$schools    = [];
			foreach ( $age_groups as $age_group ) {
				$priority              = 'regions_position_1_' . $age_group . '_' . $month_year;
				$schools[ $age_group ] = $this->get_school_for_priority( $priority, $region_id, $age_group );
			}
			if ( $options['include_wrapper'] ) {
				echo '<div class="regions_promoted_schools_wrapper">';
			}
			?>
			<div class="row results-view grid">
				<?php foreach ( $schools as $key => $school_id ) {
					$listing = Listing::get( $school_id );
					?>
					<div class="col-md-4 col-sm-6 grid-item">
						<?php
						mylisting_locate_template( 'partials/listing-preview.php', [
							'listing' => $listing ? $listing->get_data() : null,
						] );
						?>
					</div>
				<?php } ?>
			</div>
			<?php
			if ( $options['include_wrapper'] ) {
				echo '</div>';
			}
		}
		else {
			$priority_homepage = 'homepage_' . $options['position'] . '_' . $options['age_group'] . '_' . $month_year;
			$school_id         = $this->get_school_for_priority( $priority_homepage, $region_id, $options['age_group'] );
			$school            = get_post( $school_id );
			if ( ! empty( $school ) ) {

				$cover_image = '';
				$job_cover   = get_post_meta( $school->ID, '_job_cover', 1 );
				if ( is_array( $job_cover ) && ! empty( $job_cover[0] ) ) {
					$cover_image = $job_cover[0];
				}
				if ( empty( $cover_image ) ) {
					$job_gallery = get_post_meta( $school->ID, '_job_gallery', 1 );
					if ( is_array( $job_gallery ) && ! empty( $job_gallery[0] ) ) {
						$cover_image = $job_gallery[0];
					}
				}


				$content        = $school->post_content;
				$content        = strip_tags( $content );
				$read_more_link = '<a href="' . get_permalink( $school->ID ) . '">Read More</a>';

				switch ( $options['position'] ) {
					case 'banner':
					{
						?>
						<div class="promoted-school-wrapper banner">
							<div class="promoted-school-container"
							     style="background-image: url('<?php echo $cover_image; ?>')">

								<span class="promoted-school-badge">Popular</span>
								<div class="promoted-school-content">
									<div class="promoted-school-title">
										<h2><?php echo $school->post_title; ?></h2>
									</div>
									<div class="promoted-school-stars">
										<i class="fa fa-star"></i>
										<i class="fa fa-star"></i>
										<i class="fa fa-star"></i>
										<i class="fa fa-star"></i>
										<i class="fa fa-star"></i>
									</div>
									<div class="promoted-school-excerpt">
										<?php
										echo substr( $content, 0, '150' );
										if ( strlen( $content ) > '150' ) {
											echo '... ' . $read_more_link;
										} ?>
									</div>
								</div>
							</div>
							<a class="promoted-school-link"
							   href="<?php echo get_permalink( $school->ID ); ?>"></a>
						</div>
						<?php
						break;
					}
					case 'spotlight_1':
					case 'spotlight_2':
					case 'spotlight_3':
					case 'spotlight_4':
					{
						?>
						<div class="promoted-school-wrapper spotlight">
							<a href="<?php echo get_permalink( $school->ID ); ?>">
								<div class="promoted-school-container">
									<div class="promoted-school-image">
										<?php
										if ( ! empty( $cover_image ) ) {
											?>
											<img src="<?php echo $cover_image; ?>"
											     alt="<?php echo $school->post_title; ?>">
											<?php
										} ?>
									</div>
									<div class="promoted-school-content">
										<div class="promoted-school-title">
											<h3><?php echo $school->post_title; ?></h3>
										</div>
										<div class="promoted-school-excerpt">
											<?php
											echo substr( $content, 0, '80' );
											if ( strlen( $content ) > '80' ) {
												echo '...';
											} ?>
										</div>
									</div>
								</div>
							</a>
						</div>
						<?php
						break;
					}
				}
			}
		}

		return ob_get_clean();
	}

	function display_product_custom_field(): void {
		global $post;
		// Check for the custom field value
		$product = wc_get_product( $post->ID );
		if ( $product->get_type() == 'advertisement_product' ) {

			$current_month = (int) date( 'n' );
			$slot_priority = esc_attr__( $_REQUEST['priority'] ?? 'homepage_banner_primary' );
			$month         = esc_attr__( $_REQUEST['month'] ?? $current_month );

			ob_start();
			?>
			<div class="single-advertisement-product-options">
				<?php echo $this->render_advertisement_product_options( $slot_priority ) ?>
				<?php echo $this->getSlotsPricingHtml( $slot_priority, 'select', $month, 0 )['buttons_html'] ?>

			</div>
			<?php
			echo ob_get_clean();
		}
	}

	function add_to_cart_template(): void {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	function validate_add_to_cart( $passed, $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if ( $product->get_type() == 'advertisement_product' ) {

			$year_month = $_POST['select_month'] ?? '';
			$slot_type  = $_POST['select_slot_type'] ?? '';
			$section    = $_POST['select_section'] ?? '';

			$region   = $_POST['select_region'] ?? '';
			$position = $_POST['select_position'] ?? '';

			$age_group = $_POST['select_age_group'] ?? '';
			$school_id = $_POST['select_school'] ?? '';

			$month = explode( '_', $year_month )[0] ?? '';
			$year  = explode( '_', $year_month )[1] ?? '';


			if ( $slot_type == 'homepage' ) {
				$position = $section;
			}
			else if ( empty( $region ) ) {
				wc_add_notice( __( 'Please select a region from the available regions' ), 'error' );
				$passed = false;
			}

			$slot_priority = $slot_type . '_' . $position . '_' . $age_group;


			$price = $this->get_slot_price( $month, $slot_priority, $school_id );

			$slot_priority_db = $slot_priority . '_' . $month . '_' . $year;
			$registered_slot  = self::get_registered_slots(
				priority: $slot_priority_db,
				region_id: (int) $region );
			if ( ! empty( $registered_slot ) ) {
				wc_add_notice( __( 'Slot Already Reserved for this period!' ), 'error' );
				$passed = false;
			}

			if ( empty( $price ) ) {
				wc_add_notice( __( 'Slot not available!' ), 'error' );
				$passed = false;
			}


			if ( empty( $month ) || empty( $year ) ) {
				wc_add_notice( __( 'Please select a valid month' ), 'error' );
				$passed = false;
			}

			if ( empty( $school_id ) ) {

				wc_add_notice( __( 'Please select your desired school' ), 'error' );
				$passed = false;
			}

			if ( empty( $age_group ) ) {

				wc_add_notice( __( 'Please select your desired age group' ), 'error' );
				$passed = false;
			}

			if ( empty( $position ) ) {

				wc_add_notice( __( 'Please select your desired position' ), 'error' );
				$passed = false;
			}

		}

		return $passed;
	}


	function add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

		$product = wc_get_product( $product_id );

		if ( $product->get_type() == 'advertisement_product' ) {

			if ( ! empty( $cart_item_data['added_via_ajax'] ) ) {
				return $cart_item_data;
			}

			$custom_meta = $this->generate_cart_item_data();

			foreach ( $custom_meta as $key => $value ) {
				// Add the item data
				$cart_item_data[ $key ] = $value;
			}
		}

		return $cart_item_data;
	}

	function save_custom_meta_to_order_item( $item, $cart_item_key, $values, $order ): void {

		if ( isset( $values['slot_type'] ) ) {
			$item->add_meta_data( 'Type', ucfirst( $values['slot_type'] ) );
			if ( $values['slot_type'] == 'regions' && ! empty( $values['region_id'] ) ) {
				$region_name = get_term( $values['region_id'] )->name;
				$item->add_meta_data( 'Region', $region_name );
			}
			if ( isset( $values['position'] ) ) {
				$item->add_meta_data( 'Position', ucfirst( str_replace( '_', ' ', $values['position'] ) ) );
			}
			if ( isset( $values['month'] ) && isset( $values['year'] ) ) {
				$time_period = ucfirst( $values['month'] ) . '-' . $values['year'];
				$item->add_meta_data( 'Time Period', $time_period );
			}
			if ( isset ( $values['age_group'] ) ) {
				$age_group_map = [
					'pre'     => 'Pre-School',
					'primary' => 'Primary School',
					'high'    => 'High School'
				];
				$age_group     = $age_group_map[ $cart_item['age_group'] ] ?? '';
				$item->add_meta_data( 'Age Group', $age_group );
			}
			if ( isset( $values['school_id'] ) ) {
				$item->add_meta_data( 'School', get_post( $values['school_id'] )->post_title );
			}

		}

		$custom_meta_keys = $this->get_custom_meta_keys();

		// Loop through the custom meta keys
		foreach ( $custom_meta_keys as $key ) {
			if ( isset( $values[ $key ] ) ) {
				$item->add_meta_data( '_' . $key, $values[ $key ] );
			}
		}
	}


	function display_cart_item_data( $name, $cart_item, $cart_item_key ): string {

		ob_start(); ?>
		<ul class="advertisement-slot-data">
			<li class="slot-type">
				<strong>Type: </strong>
				<span><?php echo $cart_item['slot_type'] ?? '' ?></span>
			</li>
			<?php if ( $cart_item['slot_type'] == 'regions' ) {
				$region_name = get_term( $cart_item['region_id'] )->name
				?>
				<li class="region">
					<strong>Region: </strong>
					<span><?php echo $region_name ?? '' ?></span>
				</li>
			<?php } ?>
			<li class="position">
				<strong>Position: </strong>
				<span><?php echo $cart_item['position'] ?? '' ?></span>
			</li>
			<li class="time_period">
				<strong>Time Period: </strong>
				<span><?php echo $cart_item['month'] . ' - ' . $cart_item['year'] ?></span>
			</li>
			<li class="age_group">
				<?php
				$age_group_map = [
					'pre'     => 'Pre-School',
					'primary' => 'Primary School',
					'high'    => 'High School'
				];
				?>
				<strong>Age Group: </strong>
				<span><?php echo $age_group_map[ $cart_item['age_group'] ] ?? '' ?></span>
			</li>
			<li class="school">
				<strong>School: </strong>
				<span><?php echo get_post( $cart_item['school_id'] )->post_title ?></span>
			</li>
		</ul>
		<?php

		$name .= ob_get_clean();

		return $name;
	}

	function before_calculate_totals( $cart_obj ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		// Iterate through each cart item
		foreach ( $cart_obj->get_cart() as $key => $value ) {
			if ( isset( $value['slot_price'] ) ) {
				$price = $value['slot_price'];
				$value['data']->set_price( ( $price ) );
			}
		}
	}

	public function override_mini_cart_price( $price, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['slot_price'] ) ) {
			$price = wc_price( $cart_item['slot_price'] );
		}

		return $price;
	}

	function filter_is_purchasable( $is_purchasable, $product ) {
		if ( is_archive() && $product->get_type() == 'advertisement_product' ) {
			return false;
		}

		return $is_purchasable;
	}


	public static function getGreaterRegions(): array {
		$all_regions = [];
		$regions_raw = get_terms(
			[
				'taxonomy'  => 'greater-region',
				'post_type' => 'job_listing'
			]
		);
		if ( ! empty( $regions_raw ) && is_array( $regions_raw ) ) {
			$all_regions = $regions_raw;
		}

		return $all_regions;
	}

	public static function get_registered_slots(
		$priority = '',
		$type = '',
		$region_id = 0,
		$year = 0,
		$month = 0
	): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'promotion_slots';

		$Q = "SELECT * FROM $table_name WHERE 1=1";

		// Include priority condition only if it's provided
		if ( ! empty( $priority ) ) {
			$Q .= $wpdb->prepare( " AND `slot_priority` = %s", $priority );
		}

		// Include region_id condition only if it's greater than 0
		if ( $region_id > 0 && ! str_contains( $priority, 'homepage' ) ) {
			$Q .= $wpdb->prepare( " AND `region_id` = %d", $region_id );
		}

		// Include type condition only if it's provided
		if ( ! empty( $type ) ) {
			$Q .= $wpdb->prepare( " AND `slot_type` = %s", $type );
		}

		// Include year and month conditions only if both are greater than 0
		if ( $year > 0 && $month > 0 ) {
			$Q .= $wpdb->prepare( " AND `year` = %d AND `month` = %d", $year, $month );
		}

		$slots = $wpdb->get_results( $Q, ARRAY_A );

		if ( ! empty( $slots ) && is_array( $slots ) ) {
			return $slots;
		}

		return [];
	}


	private function getSlotsPricingHtml(
		$slot_priority = '',
		$type = 'buttons',
		$selected_month = 'january',
		$region_id = 0,
		$school_id = 0
	): array {
		$return        = [];
		$current_month = (int) date( 'n' ); // Numeric representation of current month (1-12)
		$current_year  = (int) date( 'Y' );

		// Define the months array starting from the current month
		$months_full = [
			1  => [ 'key' => 'january', 'label' => 'January' ],
			2  => [ 'key' => 'february', 'label' => 'February' ],
			3  => [ 'key' => 'march', 'label' => 'March' ],
			4  => [ 'key' => 'april', 'label' => 'April' ],
			5  => [ 'key' => 'may', 'label' => 'May' ],
			6  => [ 'key' => 'june', 'label' => 'June' ],
			7  => [ 'key' => 'july', 'label' => 'July' ],
			8  => [ 'key' => 'august', 'label' => 'August' ],
			9  => [ 'key' => 'september', 'label' => 'September' ],
			10 => [ 'key' => 'october', 'label' => 'October' ],
			11 => [ 'key' => 'november', 'label' => 'November' ],
			12 => [ 'key' => 'december', 'label' => 'December' ],
		];

		$months        = [];
		$adjusted_year = $current_year;

		// Re-arrange the months array to start from the current month and include the year
		foreach ( range( $current_month, $current_month + 11 ) as $i ) {
			$month_index = ( $i - 1 ) % 12 + 1; // Ensure month_index wraps around (1-12)

			// If we've wrapped around to January (month_index 1) after December (month_index 12), increment the year
			if ( $month_index == 1 && $i > $current_month ) {
				$adjusted_year ++;
			}

			$months[ $month_index ] = [
				'key'   => $months_full[ $month_index ]['key'],
				'label' => $months_full[ $month_index ]['label'],
				'year'  => $adjusted_year,
			];
		}


		//$slots = self::get_registered_slots();
		ob_start();
		?>
		<?php

		$pricing_table = $this->getPossibleSlots( 1 );
		$min_price     = 0;
		$max_price     = 0;
		if ( ! empty( $pricing_table['pricing'] ) ) {

			$prices     = $pricing_table['pricing'];
			$base_price = (float) $prices[ 'base_' . $slot_priority ] ?? 0;
			if ( $type == 'select' ) { ?>
				<div class="advertisement-banner-month-selector advertisement-select">
				<label class="" for="advertisement-banner-month-select">Month:</label>
				<select name="select_month" class="advertisement-banner-month-select"
				id="advertisement-banner-month-select">
				<?php
			}

			foreach ( $months as $month ) {
				$available        = true;
				$premium          = false;
				$discount         = 0;
				$discount_text    = '';
				$slot_priority_db = $slot_priority . '_' . $month['key'] . '_' . $month['year'];
				$registered_slot  = self::get_registered_slots(
					priority: $slot_priority_db,
					region_id: $region_id );


				// Price without considering featured school
				$final_price_simple = $this->get_slot_price( month: $month['key'], slot_priority: $slot_priority );

				$final_price = $this->get_slot_price(
					month: $month['key'],
					slot_priority: $slot_priority,
					school_id: $school_id,
				);

				if ( $base_price == $final_price_simple ) {
					$premium = true;
				}
				else if ( $final_price < $base_price ) {
					$discount = round( ( $base_price - $final_price ) / $base_price * 100 );
					// two decimal places for discount percentage calculation
					$discount_text = number_format( $discount ) . '% off';
				}


				if ( $base_price == $final_price_simple && $final_price < $base_price ) {
					$discount      = 30; // 30% off for base price
					$discount_text = '30% off';
				}


				$btn_class = 'buttons button-2 advertisement-banner-button ' . $month['key'];

				if ( $premium ) {
					$btn_class .= ' premium';
				}
				if ( empty( $final_price ) ) {
					$btn_class .= ' sold_out price_not_set';
					$available = false;
				}
				if ( ! empty( $registered_slot ) ) {
					$btn_class .= ' sold_out';
					$available = false;
				}
				$wrapper_class = $month['key'] . ' ' . $month['key'] . '_' . $month['year'];

				$label = $month['label'] . '-' . substr( $month['year'], 2, 2 );

				$base_price_text  = 'R' . number_format( $base_price, 2 );
				$final_price_text = 'R' . number_format( $final_price, 2 );

				if ( $final_price > $max_price ) {
					$max_price = $final_price;
				}
				if ( $final_price < $min_price || empty( $min_price ) ) {
					$min_price = $final_price;
				}
				if ( $type == 'select' ) {
					$selected = ( $month['key'] == $selected_month ) ? "selected" : '';
					echo '<option value="' . $month['key'] . '_' . $month['year'] . ' " ' . $selected . '>';
					echo $label;
					echo '</option>';

				}
				else { ?>
					<div class="advertisement-banner-button-wrapper <?php echo $wrapper_class ?>">
						<button
								data-month="<?php echo $month['key'] ?>"
								data-year="<?php echo $month['year'] ?>"
								class="<?php echo $btn_class ?>">
							<?php if ( $discount > 0 ) { ?>
								<span class="advertisement-banner-button-badge"><?php echo $discount_text; ?></span>
							<?php } ?>
							<span class="advertisement-banner-button-month"><?php echo $label; ?></span>
							<span class="advertisement-banner-button-price">
                            <?php if ( $available ) { ?>
	                            <?php if ( $base_price != $final_price ) {
		                            echo '<span class="advertisement-price-initial">' . $base_price_text . '</span>';
	                            } ?>
	                            <span class="advertisement-price-final"><?php echo $final_price_text; ?></span>
                            <?php }
                            else { ?>
	                            <span class="advertisement-price-final">Sold Out!</span>
                            <?php } ?>
                        </span>
						</button>
					</div>

					<?php
				}

			}


			if ( $type == 'select' ) { ?>
				</select></div>
				<?php
			}
		}
		?>
		<?php

		$return['range_html']   = 'R' . number_format( $min_price );
		$return['range_html']   .= '-R' . number_format( $max_price );
		$return['buttons_html'] = ob_get_clean();

		return $return;
	}

	private function generateDataTable(): string {

		global $post;


		$months = $this->getSimpleMonths();

		$advertisement_pricing = (array) get_post_meta( $post->ID, 'sspi_advertisement_pricing', 1 );

		$possible_keys = $this->getPossibleSlots();


		ob_start();

		?>
		<div class="sspi_table_wrapper">
			<table class="sspi_table">
				<thead>
				<tr>
					<th>Section</th>
					<?php foreach ( $months as $month ) { ?>
						<th><?php echo $month; ?></th>
					<?php } ?>
				</tr>
				</thead>
				<tbody>
				<tr>
					<th colspan="13">Homepage</th>
				</tr>
				<?php foreach ( $possible_keys['homepage'] as $key => $item ) { ?>
					<tr>
						<td class="table-label"><?php echo $item; ?></td>
						<?php foreach ( $months as $month_key => $month ) {
							$name  = $month_key . '_' . $key;
							$value = $advertisement_pricing[ $name ] ?? '';
							$name  = 'sspi_advertisement_pricing[' . $name . ']';
							?>
							<td>
								<label>
									<input type="number" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
								</label>
							</td>
						<?php } ?>
					</tr>
				<?php } ?>
				<tr>
					<th colspan="13">Regions</th>
				</tr>
				<?php foreach ( $possible_keys['regions'] as $key => $item ) { ?>
					<tr>
						<td class="table-label"><?php echo $item; ?></td>
						<?php foreach ( $months as $month_key => $month ) {
							$name  = $month_key . '_' . $key;
							$value = $advertisement_pricing[ $name ] ?? '';
							$name  = 'sspi_advertisement_pricing[' . $name . ']';
							?>
							<td>
								<label>
									<input type="number" name="<?php echo $name ?>" value="<?php echo $value ?>">
								</label>
							</td>
						<?php } ?>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php

		return ob_get_clean();
	}

	private function getPossibleSlots( $with_prices = false ): array {

		$homepage = [
			'homepage_banner_pre'     => 'Banner (Pre)',
			'homepage_banner_primary' => 'Banner (Primary)',
			'homepage_banner_high'    => 'Banner (High)',
		];
		for ( $i = 1; $i <= 4; $i ++ ) {
			$homepage[ 'homepage_spotlight_' . $i . '_pre' ]     = 'Spotlight ' . $i . ' (Pre)';
			$homepage[ 'homepage_spotlight_' . $i . '_primary' ] = 'Spotlight ' . $i . ' (Primary)';
			$homepage[ 'homepage_spotlight_' . $i . '_high' ]    = 'Spotlight ' . $i . ' (High)';
		}
		$regions = [];
		for ( $i = 1; $i <= 1; $i ++ ) {
			$regions[ 'regions_position_' . $i . '_pre' ]     = 'Position ' . $i . ' (Pre)';
			$regions[ 'regions_position_' . $i . '_primary' ] = 'Position ' . $i . ' (Primary)';
			$regions[ 'regions_position_' . $i . '_high' ]    = 'Position ' . $i . ' (High)';
		}
		$return = [
			'homepage' => $homepage,
			'regions'  => $regions,
		];


		if ( $with_prices ) {
			$advertisement_product = self::get_advertisement_product_id();
			$pricing_table         = get_post_meta( $advertisement_product, 'sspi_advertisement_pricing', 1 );
			if ( ! empty( $pricing_table ) && is_array( $pricing_table ) ) {
				$months = $this->getSimpleMonths();
				foreach ( $months as $month_key => $month ) {
					foreach ( $homepage as $key => $value ) {
						$price_key                       = $month_key . '_' . $key;
						$return['pricing'][ $price_key ] = $pricing_table[ $price_key ] ?? 0;
					}
					foreach ( $regions as $key => $value ) {
						$price_key                       = $month_key . '_' . $key;
						$return['pricing'][ $price_key ] = $pricing_table[ $price_key ] ?? 0;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * @return array
	 */
	private function getSimpleMonths(): array {
		return [
			'base'      => 'Base',
			'january'   => 'Jan',
			'february'  => 'Feb',
			'march'     => 'Mar',
			'april'     => 'Apr',
			'may'       => 'May',
			'june'      => 'Jun',
			'july'      => 'Jul',
			'august'    => 'Aug',
			'september' => 'Sep',
			'october'   => 'Oct',
			'november'  => 'Nov',
			'december'  => 'Dec',
		];
	}

	private function render_advertisement_product_options( $slot_priority ): string {
		ob_start();

		$slot_array = explode( '_', $slot_priority );
		$type       = ! empty( $slot_array[0] ) ? $slot_array[0] : 'homepage';
		$position   = ! empty( $slot_array[0] ) ? $slot_array[0] : 'banner';
		$age_group  = ! empty( $slot_array[0] ) ? $slot_array[0] : 'primary';
		?>

		<div class="advertisement-banner-options">
			<div class="advertisement-banner-slot-selector advertisement-select">
				<label class="" for="advertisement-banner-slot-type-select">Type:</label>
				<?php
				$selected_homepage = $type == 'homepage' ? "selected" : "";
				$selected_region   = $type == 'region' ? "selected" : "";
				?>
				<select name="select_slot_type"
				        class="advertisement-banner-slot-type-select"
				        id="advertisement-banner-slot-type-select">
					<option value="homepage" <?php echo $selected_homepage ?>>Homepage</option>
					<option value="regions" <?php echo $selected_region ?>>Region</option>
				</select>
			</div>
			<div class="advertisement-banner-homepage-section-selector advertisement-select"
				<?php echo ( $type == 'region' ) ? 'style="display: none"' : ''; ?>>
				<label class="" for="advertisement-banner-homepage-section-select">Position:</label>
				<select name="select_section"
				        class="advertisement-banner-homepage-section-select"
				        id="advertisement-banner-homepage-section-select">
					<?php
					$positions = [
						'banner'      => 'Homepage Banner',
						'spotlight_1' => 'Spotlight 1',
						'spotlight_2' => 'Spotlight 2',
						'spotlight_3' => 'Spotlight 3',
						'spotlight_4' => 'Spotlight 4',
					]

					?>
					<?php foreach ( $positions as $key => $value ) {
						$selected = $key == $position ? "selected" : "";
						echo '<option value="' . $key . '"' . $selected . '>';
						echo $value;
						echo '</option>';
					} ?>
				</select>
			</div>
			<div class="advertisement-banner-region-selector advertisement-select"
				<?php echo ( $type == 'homepage' ) ? 'style="display: none"' : ''; ?>>
				<label class="" for="advertisement-banner-region-select">Region:</label>
				<select name="select_region" class="advertisement-banner-region-select"
				        id="advertisement-banner-region-select">
					<option value="">Select Region</option>
					<?php
					$all_regions = self::getGreaterRegions();
					foreach ( $all_regions as $region ) {
						echo '<option value="' . $region->term_id . '">';
						echo $region->name . ' (' . $region->count . ')';
						echo '</option>';
					} ?>
				</select>
			</div>
			<div class="advertisement-banner-position-selector advertisement-select"
				<?php echo ( $type == 'homepage' ) ? 'style="display: none"' : ''; ?>>
				<label class="" for="advertisement-banner-position-select">Position:</label>
				<select name="select_position"
				        class="advertisement-banner-position-select"
				        id="advertisement-banner-position-select">
					<?php
					$positions = [
						'position_1' => 'Position 1',
					]

					?>
					<?php foreach ( $positions as $key => $value ) {
						$selected = $key == $position ? "selected" : "";
						echo '<option value="' . $key . '"' . $selected . '>';
						echo $value;
						echo '</option>';
					} ?>
				</select>
			</div>
			<div class="advertisement-banner-category-selector advertisement-select">
				<label class="" for="advertisement-banner-category-select">Age Group:</label>
				<select name="select_age_group" class="advertisement-banner-category-select"
				        id="advertisement-banner-category-select">
					<?php
					$all_categories = get_terms( [
						'taxonomy'   => 'job_listing_category',
						'hide_empty' => false
					] );

					$counts      = [
						'pre'     => 0,
						'primary' => 0,
						'high'    => 0,
					];
					$all_schools = get_posts( [
						'numberposts' => - 1,
						'post_type'   => 'job_listing',
						'hide_empty'  => false,
						'author'      => get_current_user_id(),
						//'author'      => 1390,
					] );
					foreach ( $all_schools as $school ) {
						if ( $this->get_school_type( $school->ID ) == 'High School' ) {
							$counts['high'] ++;
						}
						if ( $this->get_school_type( $school->ID ) == 'Primary School' ) {
							$counts['primary'] ++;
						}
						if ( $this->get_school_type( $school->ID ) == 'Pre-School' ) {
							$counts['pre'] ++;
						}
					}
					if ( array_sum( $counts ) == 0 ) {
						echo '<option value="">No Age Group found</option>';
					}
					foreach ( $all_categories as $category ) {
						$this_age_group = str_replace( '-school', '', $category->slug );
						$selected       = ( $this_age_group == $age_group ) ? "selected" : '';
						if ( empty( $counts[ $this_age_group ] ) ) {
							$selected .= ' disabled';
						}
						echo '<option value="' . $this_age_group . '" ' . $selected . '>';
						echo $category->name . ' (' . $counts[ $this_age_group ] . ')';
						echo '</option>';
					} ?>
				</select>
			</div>
			<div class="advertisement-banner-school-selector advertisement-select">
				<label class="" for="advertisement-banner-school-select">Select School:</label>
				<select name="select_school" class="advertisement-banner-school-select"
				        id="advertisement-banner-school-select">
					<?php
					if ( empty( $all_schools ) ) {
						echo '<option data-school-type="empty" value="">No School found</option>';
					}
					else {
						echo '<option data-school-type="empty" value="">Select School</option>';
					}
					foreach ( $all_schools as $school ) {
						$school_type      = $this->get_school_type( $school->ID );
						$school_type_slug = '';
						switch ( $school_type ) {
							case 'High School':
								$school_type_slug = 'high';
								break;
							case'Primary School':
								$school_type_slug = 'primary';
								break;
							case 'Pre-School':
								$school_type_slug = 'pre';
								break;
						}


						echo '<option data-school-type="' . $school_type_slug . '" value="' . $school->ID . '">';
						echo $school->post_title . ' (' . $school_type . ')';
						echo '</option>';
					} ?>
				</select>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	private function get_slot_price(
		$month = 'january',
		$slot_priority = 'homepage_banner_primary',
		$school_id = 0
	): float {
		$product_id    = self::get_advertisement_product_id();
		$pricing_table = get_post_meta( $product_id, 'sspi_advertisement_pricing', 1 );
		if ( ! empty( $pricing_table ) && is_array( $pricing_table ) ) {
			$price_key = $month . '_' . $slot_priority;

			$final_price = (float) $pricing_table[ $price_key ] ?? 0.00;

			$is_featured = get_post_meta( $school_id, '_featured', true );

			if ( ! empty( $is_featured ) && $final_price > 0.00 ) {
				$discount    = $final_price * 0.3; // 30% discount for featured schools
				$final_price = $final_price - $discount;
			}

			return $final_price;
		}

		return 0.00;
	}

	private function get_slot_priority_text( $slot_type = 'homepage', $position = 'banner', $age_group = 'primary' ): string {


		$priority_text = '';
		if ( $slot_type === 'homepage' ) {
			$priority_text = 'homepage' . '_' . $position;
		}
		else if ( $slot_type === 'regions' ) {
			$priority_text = 'regions' . '_' . $position;
		}


		return $priority_text . '_' . trim( str_replace( '-', '_', $age_group ) );
	}

	public static function get_advertisement_product_id(): int {


		$args     = [
			'post_type'      => 'product', // Specify the post type as 'product'
			'posts_per_page' => - 1, // Retrieve all products
			'tax_query'      => [
				[
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'advertisement_product', // The slug of your custom product type
				],
			],
		];
		$products = get_posts( $args );

		$advertisement_product = null;

		if ( ! empty( $products ) ) {
			foreach ( $products as $product ) {
				$advertisement_product = $product;
				break; // Assuming only one product is published
			}
		}

		return $advertisement_product ? $advertisement_product->ID : 0;
	}

	private function get_school_type( $school_id = 0 ): string {
		$return = '';
		if ( $school_id > 0 ) {
			$directory_type = wp_get_post_terms( $school_id, 'job_listing_category' );
			$return         = $directory_type[0]->name ?? '';
		}

		return $return;
	}

	private function generate_cart_item_data( $method = 'post' ): array {

		$month_year = $_POST['select_month'] ?? '';
		$section    = $_POST['select_section'] ?? '';
		$region     = $_POST['select_region'] ?? '';
		$position   = $_POST['select_position'] ?? '';
		$slot_type  = $_POST['select_slot_type'] ?? '';
		$age_group  = $_POST['select_age_group'] ?? '';
		$school_id  = $_POST['select_school'] ?? '';

		$month = explode( '_', $month_year )[0] ?? '';
		$year  = explode( '_', $month_year )[1] ?? '';

		if ( $slot_type == 'homepage' ) {
			$position = $section;
		}

		$slot_priority = $slot_type . '_' . $position . '_' . $age_group;


		$price = $this->get_slot_price( $month, $slot_priority, $school_id );


		$custom_meta = [
			'slot_type'  => sanitize_text_field( $slot_type ),
			'position'   => sanitize_text_field( $position ),
			'age_group'  => sanitize_text_field( $age_group ),
			'month'      => sanitize_text_field( $month ),
			'year'       => sanitize_text_field( $year ),
			'school_id'  => sanitize_text_field( $school_id ),
			'priority'   => sanitize_text_field( $slot_priority . '_' . $month_year ),
			'slot_price' => $price,
		];

		if ( $slot_type == 'regions' ) {
			$custom_meta['region_id'] = sanitize_text_field( $region );
		}

		if ( $method == 'ajax' ) {

			$custom_meta['errors'] = [];

			if ( $slot_type == 'regions' && empty( (int) $region ) ) {
				$custom_meta['errors'][] = __( 'Please select a region from the available regions' );
			}

			if ( empty( (int) $school_id ) ) {
				$custom_meta['errors'][] = __( 'Please select your desired school' );
			}

			if ( empty( $age_group ) ) {
				$custom_meta['errors'][] = __( 'Please select your desired age group' );
			}

			if ( empty( $position ) ) {
				$custom_meta['errors'][] = __( 'Please select your desired position' );
			}


			$slot_priority_db = $slot_priority . '_' . $month . '_' . $year;
			$registered_slot  = self::get_registered_slots(
				priority: $slot_priority_db,
				region_id: (int) $region );
			if ( ! empty( $registered_slot ) ) {
				$custom_meta['errors'][] = __( 'Slot Already Reserved for this period!' );
			}

			if ( empty( $price ) ) {
				$custom_meta['errors'][] = __( 'Slot not available!' );
			}


			$custom_meta ['added_via_ajax'] = true;
		}

		return $custom_meta;
	}

	/**
	 * @throws Exception
	 */
	public function reserve_advertisement_slot( $order_id ): void {
		// Get the order object
		$order = wc_get_order( $order_id );
		if ( $order->get_status() == 'completed' ) {
			foreach ( $order->get_items() as $item_id => $item ) {

				$slot_type     = $item->get_meta( '_slot_type' );
				$position      = $item->get_meta( '_position' );
				$age_group     = $item->get_meta( '_age_group' );
				$month         = $item->get_meta( '_month' );
				$year          = $item->get_meta( '_year' );
				$slot_priority = $item->get_meta( '_priority' );
				$region_id     = $item->get_meta( '_region_id' );
				$school_id     = $item->get_meta( '_school_id' );
				$product_id    = $item['product_id'];


				if ( ! empty( $slot_priority ) ) {
					global $wpdb;

					$registered_slot = self::get_registered_slots(
						priority: $slot_priority,
						region_id: (int) $region_id );

					// If the slot does not already exist, insert it
					if ( empty( $registered_slot ) ) {
						$wpdb->insert(
							"{$wpdb->prefix}promotion_slots",
							[
								'slot_type'     => $slot_type,
								'position'      => $position,
								'age_group'     => $age_group,
								'month'         => $month,
								'year'          => $year,
								'slot_priority' => $slot_priority,
								'region_id'     => $region_id,
								'school_id'     => $school_id,
								'order_id'      => $order_id,
								'product_id'    => $product_id,
								'created_at'    => current_time( 'mysql' ),
							],
							[ '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s' ]
						);
					}
				}
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function get_custom_meta_keys(): array {
		return [
			'slot_type',
			'position',
			'age_group',
			'month',
			'year',
			'region_id',
			'school_id',
			'priority',
			'slot_price',
			'added_via_ajax',
		];
	}

	private function get_term_id_from_url(): bool|int {
		$current_url_path = $_SERVER['REQUEST_URI'];
		$current_url_path = trim( $current_url_path, '/' );
		$parts            = explode( '/', $current_url_path );

		$term_slug = '';

		if ( ! empty( $parts ) && in_array( 'greater-region', $parts ) ) {
			$term_slug = end( $parts );
		}


		if ( empty( $term_slug ) && ! empty( $_REQUEST['greater-region'] ) ) {
			$term_slug = esc_attr__( $_REQUEST['greater-region'] );
		}

		if ( count( explode( ',', $term_slug ) ) > 0 ) {
			$term_slug = explode( ',', $term_slug )[0];
		}

		//Functions::ppa( $term_slug );

		// Retrieve the term by slug
		$term = get_term_by( 'slug', $term_slug, 'greater-region' );

		return $term ? $term->term_id : false;
	}

	private function get_school_for_priority( $priority, $region_id, $age_group ) {

		if ( $region_id > 0 ) {
			$registered_slot = self::get_registered_slots( priority: $priority, region_id: $region_id );
		}
		else {
			$registered_slot = self::get_registered_slots( priority: $priority, type: 'homepage' );
		}
		$school = null;
		if ( ! empty( $registered_slot ) ) {
			$school = $registered_slot[0]['school_id'] ?? 0;
		}
		if ( empty( $school ) ) {
			$args   = [
				'post_type'      => 'job_listing',
				'posts_per_page' => 1,
				'orderby'        => 'rand',
				'meta_query'     => [
					[
						'key'   => '_featured',
						'value' => '1',
					],
				],
				'tax_query'      => [
					[
						'taxonomy' => 'job_listing_category',
						'field'    => 'slug',
						'terms'    => $age_group . '-school',
					],
				],
				'fields'         => 'ids'
			];
			$school = get_posts( $args )[0] ?? null;
		}

		return $school;
	}

}

