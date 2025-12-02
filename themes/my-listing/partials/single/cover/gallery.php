<?php
/**
 * Gallery template for single-listing page's cover section.
 *
 * @since 1.6.0
 */

// If there are no gallery images, check if there's a cover image, or a default cover image available.
// Use the empty template if listing gallery isn't available.
if ( ! ( $gallery = $listing->get_field( 'gallery' ) ) ) {
    return require locate_template( 'partials/single/cover/image.php' );
}

// Overlay options.
$overlay_opacity = c27()->get_setting( 'single_listing_cover_overlay_opacity', '0.5' );
$overlay_color   = c27()->get_setting( 'single_listing_cover_overlay_color', '#242429' );
$image_size      = count( $gallery ) === 1 ? 'full' : 'large';
?>

<section class="featured-section profile-cover featured-section-gallery profile-cover-gallery">
    <div class="header-gallery-carousel photoswipe-gallery owl-carousel zoom-gallery">
        <?php foreach ( $gallery as $gallery_image ): ?>
            <?php

            $img_ID = c27()->get_attachment_by_guid( $gallery_image );
            if (isset($img_ID)) {
                $img_alt = get_post_meta($img_ID, '_wp_attachment_image_alt', true);
                $img_title = get_the_title($img_ID);
                $img_caption = wp_get_attachment_caption($img_ID);
                $img_description = get_post($img_ID)->post_content;
            };

            ?>
        	<?php if ( $image = c27()->get_resized_image( $gallery_image, $image_size ) ): ?>

        		<a aria-label="<?php echo esc_attr( _ex( 'Header gallery image', 'Header gallery item - SR', 'my-listing' ) ) ?>" class="item photoswipe-item"
        			href="<?php echo esc_url( c27()->get_resized_image( $gallery_image, 'full' ) ? : $image ) ?>"
        			style="background-image: url(<?php echo esc_url( $image ) ?>);"
                    alt="<?php echo esc_attr( $img_alt ?? '' ) ?>" 
                    title="<?php echo esc_attr( $img_title ?? '' ) ?>" 
                    caption="<?php echo esc_attr( $img_caption ?? '' ) ?>" 
                    description="<?php echo esc_attr( $img_description ?? '' ) ?>"
        			>
        			<div class="overlay"
        				 style="background-color: <?php echo esc_attr( $overlay_color ); ?>;
                        		opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
                        >
                    </div>
        		</a>

        	<?php endif ?>
        <?php endforeach ?>
    </div>
<!-- Omit the closing </section> tag -->