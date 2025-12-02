<?php

namespace NGD_THEME\Front;

use NGD_THEME\Functions\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Front {

	public function __construct( $run_hooks = false ) {
		if ( $run_hooks ) {
			$this->run_hooks();
		}
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			new Sidebar( $run_hooks );
		}
	}

	public function run_hooks(): void {
		add_shortcode( 'abdul_test_theme', [ $this, 'render_abdul_test' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		wp_enqueue_style( 'sweet-alert',
			get_stylesheet_directory_uri() . '/assets/vendor/sweetalert2/dist/sweetalert2.min.css' );
		wp_enqueue_style( 'ngd_theme_styles', get_stylesheet_directory_uri() . '/assets/css/styles.css' );
		wp_enqueue_script( 'sweet-alert',
			get_stylesheet_directory_uri() . '/assets/vendor/sweetalert2/dist/sweetalert2.min.js', [ 'jquery' ] );
		wp_enqueue_script( 'ngd_theme_scripts', get_stylesheet_directory_uri() . '/assets/js/scripts.js',
			[ 'jquery' ] );

		// Localize the script with AJAX URL
		wp_localize_script( 'ngd_theme_scripts', 'ngd_ajax_object', [
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ngd_theme_nonce' )
		] );
	}

	public function render_abdul_test( $params ): bool|string {
		$options = [
			'param_1' => "",
		];
		$options = shortcode_atts( $options, $params );

		ob_start();
		echo 'Here';
		//Functions::ppa(mylisting()->stats()->get_user_stats( get_current_user_id() ));
		//Functions::ppa(mylisting()->stats()->get_user_stats( 1390 ));

		$nova_pioneer_stats = mylisting()->stats()->get_user_stats( 1390 );
		//echo do_shortcode( '[advertisement_popup]' );
		?>
		<h1>Abdul Test from Starter Theme</h1>
		<?php echo $options['param_1'] ?>
		<?php $this->update_focus_keywords() ?>
		<?php

		return ob_get_clean();
	}

	public function update_focus_keywords(): void {
		$posts          = get_posts( [
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'post_type'      => 'job_listing'
		] );
		$total_listings = count( $posts );
		foreach ( $posts as $p ) {
			$new_post_meta = [
				get_the_title( $p->ID ),
				'Primary School'
			];
			if ($p->ID == '241295') {
				echo 'Found School <br>';
				update_post_meta( $p->ID, 'rank_math_focus_keyword', get_the_title( $p->ID ) );
			}
		}
		Functions::ppa( 'Total Listings: ' . $total_listings );
	}

}