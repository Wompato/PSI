<?php
/**
 * Template part for displaying a single post item in the "Load More" section
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package PSI Child Theme
 */

?>

<?php
// In load-more-item.php
$page_number = isset($args['page_number']) ? intval($args['page_number']) : 2; // Default to 2 if not set
?>

<div class="load-more-item">
    <div class="load-more-item-container<?php if (!has_post_thumbnail()) { echo ' no-img'; } ?>" style="background-image: <?php if (has_post_thumbnail()) { ?>url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>')<?php } ?>;">
            <?php
                $categories = get_the_category();
                if($categories[0]->name === 'Cover Story') {
                    $category = 'cs';
                } elseif($categories[0]->name === 'Press Release') {
                    $category = 'pr';
                }
            ?>
        <?php if (has_category()) : ?>
            <?php $categories = get_the_category(); ?>
            <p class="gb-headline dynamic-term-class gb-headline-text load-more-category <?php echo esc_attr($category); ?>">
                <a href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>" class="load-more-category__link"><?php echo esc_html($categories[0]->name); ?></a>
            </p>
        <?php endif; ?>
        <div class="load-more-post-content-container">
            <div class="gb-inside-container load-more-inside-container">
                <p class="gb-headline gb-headline-text load-more-headline-time">
                    <time class="entry-date published" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                </p>

                <h2 class="gb-headline gb-headline-text load-more-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h2>

                <div class="gb-headline gb-headline-text load-more-excerpt">
                    <?php echo wp_trim_words(get_the_content(), 10, ' ...'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php echo do_shortcode("[related_users ID=" . get_the_ID() . " page=" . $page_number . "]"); ?>
</div>
