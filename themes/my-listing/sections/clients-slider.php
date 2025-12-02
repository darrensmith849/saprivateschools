<?php
	$data = c27()->merge_options([
			'items' => [],
            'is_edit_mode' => false,
		], $data);
?>

<section class="i-section clients">
	<div class="container-fluid">
		<div class="row section-body">
			<div class="owl-carousel clients-feed-carousel c27-owl-nav">
				<?php foreach ((array) $data['items'] as $item): ?>
					<?php if (isset($item['client_logo']) && isset($item['client_logo']['url'])): ?>
						<div class="item">
							<a
								href="<?php echo isset($item['client_url']) && isset($item['client_url']['url']) ? esc_url( $item['client_url']['url'] ) : '#client' ?>"
								title="<?php echo isset($item['client_name']) ? esc_attr( $item['client_name'] ) : '' ?>"
								<?php echo isset($item['client_url']) && isset($item['client_url']['is_external']) && $item['client_url']['is_external'] ? 'target="_blank"' : '' ?>
								class="clients-logo">
								<div class="overlay"></div>
								<div class="logo-holder">
									<img
									src="<?php echo esc_url( $item['client_logo']['url'] ) ?>"
									alt="<?php echo $item['client_logo']['alt'] ? esc_attr( $item['client_logo']['alt'] ) : '' ?>"
									>
								</div>
							</a>
						</div>
					<?php endif ?>
				<?php endforeach ?>
			</div>
		</div>
	</div>
</section>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">case27_ready_script(jQuery);</script>
<?php endif ?>