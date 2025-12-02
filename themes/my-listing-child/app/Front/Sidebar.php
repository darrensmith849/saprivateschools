<?php

namespace NGD_THEME\Front;

use SSPI\Front\Users;
use NGD_THEME\Functions\Functions;
use function MyListing\Src\Promotions\get_package;
use function MyListing\Src\Promotions\get_listing_package;
use function MyListing\Src\Promotions\get_available_packages_for_current_user;

class Sidebar {


	public function __construct( $run_hooks = false ) {
		if ( $run_hooks ) {
			$this->run_hooks();
		}
	}

	public function run_hooks(): void {
		add_shortcode( 'wc_schools_sidebar', [ $this, 'render_wc_schools_sidebar' ] );
	}


	public function render_wc_schools_sidebar( $params ): bool|string {
		$options = [
			'param_1' => "",
		];
		$options = shortcode_atts( $options, $params );
		if ( ! is_user_logged_in() ) {
			return '';
		}

		ob_start();
		$item_classes = [
			'dashboard'            => 'wc_schools_nav_item_dashboard',
			'my_listings'          => 'wc_schools_nav_item_my_listings',
			'inbox'                => 'wc_schools_nav_item_inbox wc_schools_nav_item_premium',
			'auto_responders'      => 'wc_schools_nav_item_auto_responders wc_schools_nav_item_premium',
			'voicemail_deliveries' => 'wc_schools_nav_item_voicemail_deliveries wc_schools_nav_item_premium',
			'my_account'           => 'wc_schools_nav_item_my_account',
			'my_memberships'       => 'wc_schools_nav_item_my_memberships',
			'my_school_website'    => 'wc_schools_nav_item_my_school_website',
			'contact_support'      => 'wc_schools_nav_item_contact_support',
		];

		global $wp;
		$last_part = $wp->request;
		switch ( $last_part ) {
			case 'my-account':
				$item_classes['dashboard'] .= ' active';
				break;
			case'my-account/my-listings':
				$item_classes['my_listings'] .= ' active';
				break;
			case 'my-account/my-inquiries':
				$item_classes['inbox'] .= ' active';
				break;
			case 'my-account/auto-responders':
				$item_classes['auto_responders'] .= ' active';
				break;
			case 'voicemail-deliveries':
				$item_classes['voicemail_deliveries'] .= ' active';
				break;
		}

		$lock_sign = ( sspi_membership_status() == 'premium_member' ) ? '' : '<span class="premium_only_lock">🔒</span>';

		$packages = Users::get_user_packages();
		$listings = Users::get_user_listings();
        //Functions::ppa($listings)


		?>
        <div class="wc_schools_sidebar_wrapper <?php echo sspi_membership_status() ?>">
            <div class="wc_schools_sidebar_container">
			<span class="wc_schools_sidebar_toggle">
                <i class="bb-icon-sidebar bb-icon-l"></i>
			</span>
                <div class="wc_schools_sidebar_logo">
                    <a aria-label="<?php _x( 'Site logo', 'Site logo - SR', 'my-listing' ) ?>" href="<?php echo esc_url( home_url('/') ) ?>" class="static-logo">
                        <img src="<?php echo esc_url( c27()->get_site_logo() ) ?>"
                             alt="<?php echo esc_attr( c27()->get_site_logo_alt_text() ) ?>">
                    </a>
                </div>
                <div class="wc_schools_sidebar_header">
                    <div class="membership_status <?php echo sspi_membership_status() ?>">
						<?php echo sspi_membership_status() == 'premium_member' ? 'Premium Membership' : 'Free Membership'; ?>
                    </div>
                    <div class="schools_status">
						<?php
						if ( sspi_membership_status() == 'premium_member' ) {
							$posted_listing  = 0;
                            $days_left = 0;
                            foreach ($listings as $listing ) {
                                $expiry_date = get_post_meta( $listing->ID, '_job_expires', true );
                                if (!empty($expiry_date)) {
	                                $current_date = date( 'Y-m-d' );
	                                $diff          = abs( strtotime( $expiry_date ) - strtotime( $current_date ) );
	                                $days_left_this     = floor( $diff / ( 60 * 60 * 24 ) );
                                    if ( $days_left_this >= $days_left) {
                                        $days_left += $days_left_this;
                                    }
                                }
                                if ( $days_left > 365 ) {
	                                $days_left = 365;
                                }
                                $posted_listing++;
                            }

							echo '<strong>' . $days_left . ' days </strong> ' . 'remaining<br>';
							echo '<strong>' . $posted_listing . ' Listings </strong> ' . 'Published';
						}
						else {
							echo '<strong>' . count( $listings ) . ' Listings </strong> ' . 'Published';
						}

						?>
                    </div>
                </div>
                <div class="wc_schools_sidebar_sections">
                    <div class="wc_schools_sidebar_section wc_schools_sidebar_section_schools">
                        <h4 class="wc_schools_sidebar_section_heading">
                            School Section
                        </h4>
                        <ul class="wc_schools_nav_list">
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['dashboard']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ) ?>"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-activity bb-icon-l"></i>
                                    <span class="wc_schools_nav_link_label">Dashboard</span>
                                    <span class="wc_schools_nav_link_tooltip">Dashboard</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['my_listings']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ) ?>/my-listings"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-file-checklist"></i>
                                    <span class="wc_schools_nav_link_label">My Listings</span>
                                    <span class="wc_schools_nav_link_tooltip">My Listings</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['inbox']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ) ?>/my-inquiries"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-inbox"></i>
                                    <span class="wc_schools_nav_link_label">Inbox <?php echo $lock_sign ?></span>
                                    <span class="wc_schools_nav_link_tooltip">Inbox</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['auto_responders']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ) ?>/auto-responders"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-sliders-h"></i>
                                    <span class="wc_schools_nav_link_label">Auto Responders <?php echo $lock_sign ?></span>
                                    <span class="wc_schools_nav_link_tooltip">Auto Responders</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['voicemail_deliveries']; ?>">
                                <a href="<?php echo home_url( 'voicemail-deliveries' ) ?>" class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-phone-incoming"></i>
                                    <span class="wc_schools_nav_link_label">Voicemail Deliveries <?php echo $lock_sign ?></span>
                                    <span class="wc_schools_nav_link_tooltip">Voicemail Deliveries</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="wc_schools_sidebar_section wc_schools_sidebar_section_administration">
                        <h4 class="wc_schools_sidebar_section_heading">
                            Administration
                        </h4>
                        <ul class="wc_schools_nav_list">
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['my_account']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ); ?>"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-cog"></i>
                                    <span class="wc_schools_nav_link_label">My Account</span>
                                    <span class="wc_schools_nav_link_tooltip">My Account</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['my_memberships']; ?>">
                                <a href="<?php echo wc_get_page_permalink( 'myaccount' ) ?>/orders"
                                   class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-membership-card"></i>
                                    <span class="wc_schools_nav_link_label">My Memberships</span>
                                    <span class="wc_schools_nav_link_tooltip">My Memberships</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['my_school_website']; ?>">
                                <a href="<?php echo home_url( 'website-application' ); ?>" class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-globe-alt"></i>
                                    <span class="wc_schools_nav_link_label">
                                        My School Website
                                        <span class="wc_schools_nav_pill">New</span>
                                    </span>
                                    <span class="wc_schools_nav_link_tooltip">My School Website</span>
                                </a>
                            </li>
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['contact_support']; ?>">
                                <a href="<?php echo home_url( 'contact-us' ); ?>" class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-comments"></i>
                                    <span class="wc_schools_nav_link_label">Contact Support</span>
                                    <span class="wc_schools_nav_link_tooltip">Contact Support</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="wc_schools_sidebar_section wc_schools_sidebar_section_logout">
                        <ul class="wc_schools_nav_list">
                            <li class="wc_schools_nav_list_item <?php echo $item_classes['my_account']; ?>">
                                <a href="<?php echo wp_logout_url( home_url() ); ?>" class="wc_schools_nav_link">
                                    <i class="wc_schools_nav_link_icon bb-icon-sign-out"></i>
                                    <span class="wc_schools_nav_link_label">Log Out</span>
                                    <span class="wc_schools_nav_link_tooltip">Log Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="wc_schools_sidebar_footer">

                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}


}