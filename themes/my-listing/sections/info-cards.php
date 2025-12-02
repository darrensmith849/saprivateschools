<?php
	$data = c27()->merge_options([
			'items' => [],
			'instance'	=> ''
		], $data);

?>

<section class="i-section services">
	<div class="container-fluid">
		<div class="row section-body">
			<?php foreach ($data['items'] as $index => $item ): ?>
				<div class="<?php echo esc_attr( $item['size'] ) ?>">
					<div class="service-item">
						<?php if ($item['icon']): ?>
							<div class="service-item-icon">
								<span class="<?php echo esc_attr( $item['icon'] ) ?>"></span>
							</div>
						<?php endif ?>

						<div class="service-item-info">
							<?php if ($item['title']): ?>
								<h2><?php echo esc_html( $item['title'] ) ?></h2>
							<?php endif ?>

							<?php $data['instance']->print_unescaped_setting( 'content', '27_items', $index ); ?>
						</div>
					</div>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</section>