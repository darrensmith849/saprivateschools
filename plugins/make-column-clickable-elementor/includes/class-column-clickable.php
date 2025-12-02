<?php
/**
 * Main Class
 *
 * @author   Fernando_Acosta
 * @since    1.0.0
 * @package  make-column-clickable-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Make_Column_Clickable_Setup' ) ) :

	/**
	 * The main Make_Column_Clickable_Setup class
	 */
	class Make_Column_Clickable_Setup {
		public function __construct() {
			add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );

			add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this, 'widget_extensions' ],
				10, 2 );
			add_action( 'elementor/frontend/column/before_render', [ $this, 'before_render_options' ], 10 );

			add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'widget_extensions' ],
				10, 2 );
			add_action( 'elementor/frontend/section/before_render', [ $this, 'before_render_options' ], 10 );

			add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'widget_extensions' ],
				10, 2 );
			add_action( 'elementor/frontend/container/before_render', [ $this, 'before_render_options' ], 10 );
		}


		/**
		 * After layout callback
		 *
		 * @param  object  $element
		 * @param  array  $args
		 *
		 * @return void
		 */
		public function widget_extensions( $element, $args ) {
			$element_name = $element->get_name();
			$label = $element_name === 'container' ? __( 'Container Link', 'make-column-clickable-elementor' ) : __( 'Column Link', 'make-column-clickable-elementor' );
			
			$element->start_controls_section(
				'_section_column_clickable',
				[
					'label' => $label,
					'tab'   => Elementor\Controls_Manager::TAB_LAYOUT,
				]
			);

			$element->add_control(
				'column_link',
				[
					'label'       => $label,
					'type'        => Elementor\Controls_Manager::URL,
					'dynamic'     => [
						'active' => true,
					],
					'placeholder' => __( 'https://your-link.com', 'elementor' ),
				]
			);

			$element->end_controls_section();
		}


		public function before_render_options( $element ) {
			$settings = $element->get_settings_for_display();

			if ( isset( $settings['column_link'], $settings['column_link']['url'] ) && ! empty( $settings['column_link']['url'] ) ) {
				wp_enqueue_script( 'make-column-clickable-elementor' );

				$url = esc_url( $settings['column_link']['url'] );

				// start of WPML
				do_action( 'wpml_register_single_string', 'Make Column Clickable Elementor',
					'Link - ' . $url, $url );
				$url = apply_filters( 'wpml_translate_single_string',
					$url, 'Make Column Clickable Elementor',
					'Link - ' . $url );
				// end of WPML

				$element->add_render_attribute( '_wrapper', 'class', 'make-column-clickable-elementor' );
				$element->add_render_attribute( '_wrapper', 'style', 'cursor: pointer;' );
				$element->add_render_attribute( '_wrapper', 'data-column-clickable', esc_attr( $url ) );
				$element->add_render_attribute( '_wrapper', 'data-column-clickable-blank',
					$settings['column_link']['is_external'] ? '_blank' : '_self' );
			}
		}


		public function frontend_scripts() {
			wp_register_script( 'make-column-clickable-elementor',
				plugins_url( 'assets/js/make-column-clickable.js', plugin_dir_path( __FILE__ ) ), [ 'jquery' ],
				Make_Column_Clickable_Elementor::VERSION, true );
		}
	}

endif;

new Make_Column_Clickable_Setup();
