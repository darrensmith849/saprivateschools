<?php
    // Post preview. Use within the loop.
    $defaults = [
        'wrap_in' => '',
        'post_id' => NULL,
    ];
    $data = array_merge($defaults, $data);

    if ($data['post_id'] === NULL) {
        return;
    }

    $categories = array_filter((array) get_the_terms($data['post_id'], 'category'));

    $image = c27()->featured_image($data['post_id'], 'large');

    if ( ! $image ) $image = c27()->get_setting('blog_default_post_image');

    if (is_array($image) && isset($image['sizes'])) {
        $image = $image['sizes']['large'];
    }
?>

<div class="<?php echo $data['wrap_in'] ? esc_attr( $data['wrap_in'] ) : '' ?>">
    <?php wp_print_styles('mylisting-blog-feed-widget'); ?>
    <div class="single-blog-feed">
        <div class="sbf-container">
            <div class="lf-head">
                <div class="lf-head-btn event-date">
                    <span class="e-month"><?php echo get_the_date('M', $data['post_id']) ?></span>
                    <span class="e-day"><?php echo get_the_date('d', $data['post_id']) ?></span>
                </div>
                <?php if (is_sticky($data['post_id'])): ?>
                    <div class="lf-head-btn">
                        <i class="icon icon-pin-2"></i>
                    </div>
                <?php endif ?>
            </div>
            <div class="sbf-thumb">
                <a aria-label="<?php echo esc_attr( _ex( 'Link to blog article', 'Blog feed link - SR', 'my-listing' ) ) ?>" href="<?php echo esc_url( get_permalink($data['post_id']) ) ?>">
                    <div class="overlay"></div>
                    <?php if ($image): ?>
                        <div class="sbf-background" style="background-image: url('<?php echo esc_url( $image ) ?>')"></div>
                    <?php endif ?>
                </a>
            </div>
            <div class="sbf-title">
                <a href="<?php echo esc_url( get_permalink($data['post_id']) ) ?>" class="case27-primary-text"><?php echo get_the_title($data['post_id']) ?></a>
                <p><?php echo wp_trim_words( get_the_excerpt($data['post_id']), 91 ) ?></p>
            </div>

            <div class="listing-details">
                <ul class="c27-listing-preview-category-list">
                    <?php if ( ! is_wp_error( $categories ) && count( $categories ) ):
                        $category_count = count( $categories );

                        $first_category = array_shift($categories);
                        $first_ctg = new MyListing\Src\Term( $first_category );
                        $category_names = array_map(function($category) {
                            return $category->name;
                        }, $categories);
                        $categories_string = join(', ', $category_names);
                        ?>

                        <li>
                            <a href="<?php echo esc_url( $first_ctg->get_link() ) ?>">
                                <span class="cat-icon" style="background-color: <?php echo esc_attr( $first_ctg->get_color() ) ?>;">
                                    <?php echo $first_ctg->get_icon([ 'background' => false ]) ?>
                                </span>
                                <span class="category-name"><?php echo esc_html( $first_ctg->get_name() ) ?></span>
                            </a>
                        </li>

                        <?php if (count($categories)): ?>
                            <li data-toggle="tooltip" data-placement="top" data-original-title="<?php echo esc_attr( $categories_string ) ?>" data-html="true">
                                <div class="categories-dropdown dropdown c27-more-categories">
                                    <a href="#other-categories">
                                        <span class="cat-icon cat-more">+<?php echo $category_count - 1 ?></span>
                                    </a>
                                </div>
                            </li>
                        <?php endif ?>
                        <?php endif ?>
                    </ul>
                </div>
            </div>
    </div>
</div>