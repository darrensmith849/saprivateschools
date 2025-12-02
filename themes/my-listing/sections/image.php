<?php
	$data = c27()->merge_options([
			'image' => '',
			'style' => '1',
			'alt' => '',
		], $data);

	$lightbox = \Elementor\Plugin::$instance->kits_manager;
	$open = $lightbox->get_current_settings( 'global_image_lightbox' );
	$title = $lightbox->get_current_settings( 'lightbox_title_src' );
	$description = $lightbox->get_current_settings( 'lightbox_description_src' );
?>

<?php if ($data['image'] && isset($data['image']['url'])): ?>
	<a href="<?php echo esc_url( $data['image']['url'] ) ?>" data-elementor-open-lightbox="<?php echo $open ?: 'no' ?>" data-elementor-lightbox-title="Title" data-elementor-lightbox-description="Description">
		<img
		alt="<?php echo $data['image']['alt'] ? esc_attr( $data['image']['alt'] ) : '' ?>"
		src="<?php echo esc_url( $data['image']['url'] ) ?>"
		class="img-style-<?php echo esc_attr( $data['style'] ) ?>"
		>
	</a>
<?php endif ?>