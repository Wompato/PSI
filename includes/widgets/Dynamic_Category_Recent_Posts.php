<?php

namespace PSI\Widgets;

class Dynamic_Category_Recent_Posts extends \WP_Widget {
    public function __construct() {
        parent::__construct(
            'dynamic_category_recent_posts',
            'Dynamic Category Recent Posts',
            array('description' => 'Display recent posts in the current category.')
        );
    }

    public function widget($args, $instance) {
        if (is_single()) {
            $current_category = get_the_category();
            $category = !empty($current_category) ? $current_category[0] : null;

            if ($category) {
                $category_id = $category->term_id;
                $category_name = esc_html($category->name);

                echo $args['before_widget'];
                echo $args['before_title'] . 'Recent Posts in ' . $category_name . $args['after_title'];

                $recent_posts_args = [
                    'cat' => $category_id,
                    'post__not_in' => array(get_the_ID()),
                    'posts_per_page' => 5,
                ];
                $recent_posts_query = new \WP_Query($recent_posts_args);

                if ($recent_posts_query->have_posts()) {
                    echo '<ul>';
                    while ($recent_posts_query->have_posts()) {
                        $recent_posts_query->the_post();
                        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No recent posts found.</p>';
                }

                wp_reset_postdata();
                echo $args['after_widget'];
            }
        }
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Recent Posts';
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
