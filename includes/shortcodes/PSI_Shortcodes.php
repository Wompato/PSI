<?php

namespace PSI\Shortcodes;

use PSI\Users\PSI_User;

class PSI_Shortcodes {

    private static $instance = null;

    private function __construct() {
        add_shortcode('related_users', array($this, 'related_users_shortcode'));
        add_shortcode('load_more_posts', array($this, 'load_more_posts_shortcode'));
        add_shortcode('edit_article_link', array($this, 'edit_article_link_shortcode'));
        add_shortcode('featured_caption', array($this, 'db_featured_image_caption'));
        add_shortcode('project_form_link', array($this, 'project_form_link'));
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function related_users_shortcode($atts) {
        // Extract shortcode attributes, including 'ID' and 'class' if provided
        $atts = shortcode_atts(array(
            'ID' => get_the_ID(), // Default to the current post's ID if not provided
            'class' => 'normal', // Default class is 'normal'
            'widget' => false,
            'page' => '',
        ), $atts);
    
        // Get the post ID from the 'ID' attribute
        $post_id = intval($atts['ID']);
       
        // Get the related users from the ACF relationship field
        $related_users = get_field('related_staff', $post_id);
    
        // Determine the appropriate CSS class based on the 'class' attribute
        $carousel_class = 'related-staff-carousel';
        if ($atts['class'] === 'large') {
            $carousel_class = 'related-staff-carousel-large';
        } elseif($atts['class'] === 'medium') {
            $carousel_class = 'related-staff-carousel-medium';
        }
        
        // Output the user information
        if (!empty($related_users)) {
            
            $output_class = $carousel_class;

            if (isset($atts['page'])) {
                $page_number = intval($atts['page']);
                $output_class .= ' page' . $page_number;
            }

            if ($atts['widget']) {
                $output = '<h2 class="related-staff-heading">RELATED STAFF</h2>';
                $output .= '<ul class="' . esc_attr($output_class) . '">';
            } else {
                $output = '<ul class="' . esc_attr($output_class) . '">';
            }
            
            foreach ($related_users as $user) {
                $user_id = $user->ID;
                $user_slug = get_field('user_slug', 'user_' . $user_id);
                $user_permalink = home_url() . '/staff/profile/' . $user_slug;
                
                
                $profile_images = get_field('profile_pictures', 'user_' .$user_id);

                if($profile_images){
                    $profile_img = $profile_images['icon_picture'] ? $profile_images['icon_picture'] : null;

                    if($profile_img){
                        $profile_img_url = $profile_img["sizes"]['thumbnail'];
                        $profile_img_alt = $profile_img['alt'];
                    } else {
                        $profile_img = $profile_images["primary_picture"] ? $profile_images["primary_picture"] : null;
                        if($profile_img){
                            $profile_img_url = $profile_img["sizes"]['thumbnail'];
                            $profile_img_alt = $profile_img['alt'];
                        }
                        
                    }     
                } 

                if(!$profile_img) {
                    $default_image = get_field('default_user_picture', 'option');
                    $profile_img_url = $default_image["sizes"]['thumbnail'];
                    $profile_img_alt = $default_image['alt'];
                }
    
                $output .= '<li class="related-user">';
                $output .= '<img class="related-staff__image" src="' . $profile_img_url . '" alt="' . esc_attr($profile_img_alt) . '">';
                $output .= '<a class="related-staff__name" href="' . $user_permalink . '">' . esc_html($user->display_name) . '</a>';
                $output .= '</li>';
            }
            $output .= '</ul>';
            return $output;
        } 
        
    }

    public function load_more_posts_shortcode($atts) {
        // Shortcode attributes with default values
        $atts = shortcode_atts(
            array(
                'post_type'      => 'post',
                'posts_per_page' => 6,
                'category'       => '',
                'paged'          => 1, 
            ),
            $atts,
            'load_more_posts'
        );
    
        // Enqueue your script that handles the AJAX request
        wp_enqueue_script('load-more-posts', get_stylesheet_directory_uri() . '/js/loadMorePosts.js', array('jquery'), '1.0', true);
    
        // Localize the script with shortcode attributes
        wp_localize_script('load-more-posts', 'load_more_params', array(
            'post_type'      => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'category'       => $atts['category'],
            'paged'          => intval($atts['paged']),
        ));
    
        // Shortcode output
        ob_start();
        ?>
        <div class="load-more-posts">
            <!-- Advanced Search Form -->
            
            <form id="advanced-search-form">
                <div id="search-header">
                    Advanced Search
                    <i id="toggleIcon" class="fa-solid fa-angle-down" aria-hidden="true"></i>
                </div>
                <div class="search-inputs">
                    <div>
                        <label for="search_keyword">Search By Keyword:</label>
                        <input type="text" name="search_keyword" placeholder="Keyword..." class="clearable">
                        <div class="search-dates">
                            <span>Search By Date:</span>
                            <div>
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" placeholder="Start Date" class="clearable">
                            </div>
                            <div>
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" placeholder="End Date" class="clearable">
                            </div>
                        </div>  
                    </div>
                    <button type="submit">Search</button>
                    <button type="button" id="clearButton">Clear</button>
                </div>
            </form>

           

            <div id="load-more-posts-container">
                <!-- Posts will be appended here -->
            </div>
            <div class="loader-container"></div>
            <button id="load-more-posts-button">Load More<i class="fa-solid fa-angle-right"></i></button>
        </div>
        <?php
        return ob_get_clean();

    }

    public function edit_article_link_shortcode() {
        // Check if the user is logged in
        if (is_user_logged_in()) {
            // Check if the user has editor or higher capabilities or the edit_staff_member role
            if (current_user_can('edit_others_posts') || in_array('edit_staff_member', (array) $current_user->roles)) {
                $base_url = get_bloginfo('url');
                $edit_project_url = trailingslashit($base_url) . 'edit-article?article-name=' . get_the_ID();
    
                // Return the link
                return '<i class="fa-solid fa-pen-to-square" style="padding-right: 0.5em;"></i><a href="' . esc_url($edit_project_url) . '" style="display:inline-block; margin-bottom:10px;">Edit Press Submission</a>';
            }
        }
    
        // If conditions are not met, return an empty string or other content as needed
        return '';
    }

    public function db_featured_image_caption() {
        global $post;

        // Check if the post has a featured image (post thumbnail)
        if (has_post_thumbnail($post->ID)) {
            // Get the featured image ID
            $thumbnail_id = get_post_thumbnail_id($post->ID);

            // Get the caption for the featured image
            $caption = get_post_field('post_excerpt', $thumbnail_id);

            // If no caption is set, provide a fallback
            if (empty($caption)) {
                //$caption = __('No caption available.', 'your-text-domain');
            } else {
                // Find the position of "Credit:" in the caption
                $credit_position = strpos($caption, 'Credit:');

                // If "Credit:" is found, split the caption into two parts
                if ($credit_position !== false) {
                    $before_credit = substr($caption, 0, $credit_position);
                    $after_credit = substr($caption, $credit_position);

                    // Combine the parts with a line break
                    $caption = sprintf('<em>%s<br>%s</em>', esc_html($before_credit), esc_html($after_credit));
                } else {
                    // If "Credit:" is not found, simply use the caption
                    $caption = sprintf('<em>%s</em>', esc_html($caption));
                }
            }
        } else {
            // If no featured image is set, provide a message
            $caption = __('No featured image available.', 'your-text-domain');
        }

        return sprintf('<p class="featured-caption">%s</p>', $caption);
    }

    public function project_form_link($atts) {
        // Check if the current user has the necessary capabilities
        if (current_user_can('administrator') || PSI_User::is_staff_member_editor()) {
            // Default attributes
            $atts = shortcode_atts(
                array(
                    'url' => '', // Default URL
                    'text' => 'Go To Projects', // Default text
                    'icon' => true // Whether to include icon (default: true)
                ),
                $atts
            );
    
            // Check if URL is provided
            if (empty($atts['url'])) {
                return ''; // Return empty string if URL is not provided
            }
    
            ob_start();
        ?>
            <div class="project-form__link-container" style="text-align: center;margin-bottom: 25px;">
                <?php if ($atts['icon']) : ?>
                    <i style="font-size:1em; margin-right: 0.2em;" class="wpmi__icon wpmi__label-0 wpmi__position-before wpmi__align-middle wpmi__size-1 fa fa-external-link "></i>
                <?php endif; ?>
                <a href="<?php echo esc_url($atts['url']); ?>"><?php echo esc_html($atts['text']); ?></a>
            </div>
        <?php
            return ob_get_clean();
        } else {
            return ''; // Return empty string if the user doesn't have the necessary capabilities
        }
    }
    
}
