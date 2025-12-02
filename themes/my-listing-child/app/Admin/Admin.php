<?php

namespace NGD_THEME\Admin;

use NGD_THEME\Functions\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public function __construct( $run_hooks = false ) {
		if ( $run_hooks ) {
			$this->run_hooks();

		}
	}

	public function run_hooks(): void {
		//add_action( 'admin_init', [ $this,'makePromotionSlotsTable' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		wp_enqueue_style( 'ngd_theme_admin_styles', get_stylesheet_directory_uri() . '/assets/css/admin.css' );
		wp_enqueue_script( 'ngd_theme_admin_scripts', get_stylesheet_directory_uri() . '/assets/js/admin.js', [ 'jquery' ] );
	}


	public function makePromotionSlotsTable(): void {

		global $wpdb;

		$table_name  = $wpdb->prefix . 'promotion_slots';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			slot_type varchar(32) NOT NULL,
			position varchar(32) NOT NULL,
			age_group varchar(32) NOT NULL,
			month varchar(32) NOT NULL,
			year mediumint(9) NOT NULL,
			slot_priority varchar(255) NOT NULL,
			region_id mediumint(9) NULL,
			school_id mediumint(9) NOT NULL,
			order_id mediumint(9) NOT NULL,
			product_id mediumint(9) NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		ob_start();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$added_table = dbDelta( $sql );
		Functions::logMessage( $added_table );
		ob_end_clean();
	}
}