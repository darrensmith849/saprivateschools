<?php
/**
 * Template for rendering the "Listing Feed" custom Elementor widget.
 *
 * @var array  $listing_ids
 * @var bool   $invert_nav
 * @var bool   $hide_priority
 * @var string $template
 * @var string $autoplay
 * @var string $loop
 * @var string $listing_wrap
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<?php if ( ! $template || in_array( $template, [ 'grid', 'fluid-grid' ], true ) ): ?>
	<section class="i-section listing-feed <?php echo $hide_priority?'hide-priority':'' ?>">
		<div class="container-fluid">
			<div class="row section-body grid">
				<?php foreach ( $listing_ids as $listing_id ): ?>
					<?php 
					if ( get_post_type($listing_id) === 'job_listing' ) {
						printf(
							'<div class="%s">%s</div>',
							$listing_wrap,
							\MyListing\get_preview_card( $listing_id )
						);
					} elseif(get_post_type($listing_id) === 'post') {
						c27()->get_partial( 'post-preview', [
							'wrap_in' => $listing_wrap,
							'post_id' => $listing_id,
						] );
					}
					?>
				<?php endforeach ?>
			</div>
		</div>
	</section>
<?php endif ?>

<?php if ( $template === 'carousel' ): ?>
	<section class="i-section listing-feed-2 <?php echo $hide_priority?'hide-priority':'' ?>">
		<div class="container">
			<div class="row section-body">
				<div class="owl-carousel listing-feed-carousel c27-owl-nav" owl-mobile="<?php echo $owlM ?: 1 ?>" owl-tablet="<?php echo $owlT ?: 2 ?>" owl-desktop="<?php echo $owlD ?: 3 ?>" owl-speed="<?php echo $speed ?: 2.5 ?>" owl-loop="<?php echo $loop ? true : false ?>" owl-autoplay="<?php echo $autoplay ? true : false ?>" nav-style="<?php echo $invert_nav ? 'light':'' ?>" nav-mode="<?php echo $navMode ?: 'nav' ?>">
					<?php foreach ( $listing_ids as $listing_id ): ?>
						<div class="item">
							<?php if ( get_post_type($listing_id) === 'job_listing' ): ?>
							<?php echo \MyListing\get_preview_card( $listing_id ) ?>
							<?php elseif ( get_post_type($listing_id) === 'post' ): ?>
								<?php 
								c27()->get_partial( 'post-preview', [
									'wrap_in' => '',
									'post_id' => $listing_id,
								] );
								?>
							<?php endif ?>
						</div>
					<?php endforeach ?>

					<?php if ( count( $listing_ids ) <= 3 ): ?>
						<?php foreach ( range( 0, absint( count( $listing_ids ) - 4 ) ) as $i ): ?>
							<div class="item c27-blank-slide"></div>
						<?php endforeach ?>
					<?php endif ?>
				</div>
			</div>
		</div>
	</section>
<?php endif ?>
