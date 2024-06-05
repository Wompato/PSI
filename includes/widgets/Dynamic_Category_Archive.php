<?php

namespace PSI\Widgets;

class Dynamic_Category_Archive extends \WP_Widget {
    public function __construct() {
        parent::__construct(
            'dynamic_category_archive',
            'Dynamic Category Archive',
            array('description' => 'Display an archive by year for posts in the current category.')
        );

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function widget($args, $instance) {
        if (is_single()) {
            $current_category = get_the_category();
            $category = !empty($current_category) ? $current_category[0] : null;

            if ($category) {
                global $wpdb;

                $category_id = $category->term_id;
                $category_name = esc_html($category->name);

                echo $args['before_widget'];
                echo $args['before_title'] . $category_name . ' Archive' . $args['after_title'];

                // Custom SQL query to get distinct years
                $query = $wpdb->prepare("
                    SELECT DISTINCT YEAR(post_date) AS year
                    FROM {$wpdb->posts} p
                    JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                    JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                    WHERE tt.term_id = %d
                    AND p.post_status = 'publish'
                    ORDER BY year DESC
                ", $category_id);

                $years = $wpdb->get_results($query, ARRAY_A);

                if (!empty($years)) {
                    // Enqueue the script when the widget is displayed
                    wp_enqueue_script('psi-category-archive-script');

                    echo '<select id="custom-year-select" onchange="redirectToFilteredPosts(this)" data-base-url="' . esc_url(get_category_link($category_id)) . '" data-category-id="' . esc_attr($category_id) . '">';
                    foreach ($years as $year) {
                        echo '<option value="' . esc_attr($year['year']) . '">' . esc_html($year['year']) . '</option>';
                    }
                    echo '</select>';
                } else {
                    echo '<p>No posts found in this category.</p>';
                }

                echo $args['after_widget'];
            }
        }
    }

    public function enqueue_scripts() {
        wp_register_script('psi-category-archive-script', get_stylesheet_directory_uri() . '/js/widgets/psi-category-archive.js', array('jquery'), '1.0', true);
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Category Archive';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}