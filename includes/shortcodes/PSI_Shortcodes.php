<?php

namespace PSI\Shortcodes;

use PSI\Users\PSI_User;

class PSI_Shortcodes {

    private static $instance = null;

    private function __construct() {
        add_shortcode('related_users',      array($this, 'related_users_shortcode'));
        add_shortcode('load_more_posts',    array($this, 'load_more_posts_shortcode'));
        add_shortcode('edit_article_link',  array($this, 'edit_article_link_shortcode'));
        add_shortcode('featured_image',     array($this, 'featured_image_with_caption'));
        add_shortcode('project_form_link',  array($this, 'project_form_link'));
        add_shortcode('social_share_icons', array($this, 'social_share_buttons_shortcode'));
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function related_users_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ID' => get_the_ID(),
            'class' => 'normal', 
            'widget' => false,
            'page' => '',
        ), $atts);
    
        $post_id = intval($atts['ID']);
        $related_users = get_field('related_staff', $post_id);
    
        if (empty($related_users)) {
            return '';
        }
    
        // Determine the appropriate CSS class based on the 'class' attribute
        $carousel_class = '';
        switch ($atts['class']) {
            case 'large':
                $carousel_class = 'related-staff-carousel-large';
                break;
            case 'medium':
                $carousel_class = 'related-staff-carousel-medium';
                break;
            default:
                $carousel_class = 'related-staff-carousel';
                break;
        }
    
        $output_class = $carousel_class;
    
        if (!empty($atts['page'])) {
            $page_number = intval($atts['page']);
            $output_class .= ' page' . $page_number;
        }
    
        $output = $atts['widget'] 
            ? '<h2 class="related-staff-heading">RELATED STAFF</h2><ul class="' . esc_attr($output_class) . '">' 
            : '<ul class="' . esc_attr($output_class) . '">';
    
        foreach ($related_users as $user) {
            $user_id = $user->ID;
            $user_slug = get_field('user_slug', 'user_' . $user_id);
            $user_permalink = home_url() . '/staff/profile/' . $user_slug;
    
            // Fetch profile images
            $profile_images = get_field('profile_pictures', 'user_' . $user_id);
            
            // Determine which image to use, prioritizing icon, then primary, then default
            if (!empty($profile_images['icon_picture'])) {
                $profile_img = $profile_images['icon_picture'];
            } elseif (!empty($profile_images['primary_picture'])) {
                $profile_img = $profile_images['primary_picture'];
            } else {
                $profile_img = get_field('default_user_picture', 'option');
            }
    
            // Get the image URL and alt text
            $profile_img_url = isset($profile_img['url']) ? $profile_img['url'] : '';
            $profile_img_alt = isset($profile_img['alt']) ? $profile_img['alt'] : '';
    
            $output .= '<li class="related-user">';
            $output .= '<img class="related-staff__image" src="' . esc_url($profile_img_url) . '" alt="' . esc_attr($profile_img_alt) . '">';
            $output .= '<a class="related-staff__name" href="' . esc_url($user_permalink) . '">' . esc_html($user->display_name) . '</a>';
            $output .= '</li>';
        }
    
        $output .= '</ul>';
    
        return $output;
    }
    
    

    public function load_more_posts_shortcode($atts) {
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
    
        wp_enqueue_script('load-more-posts', get_stylesheet_directory_uri() . '/js/loadMorePosts.js', array('jquery'), '1.0', true);
    
        wp_localize_script('load-more-posts', 'load_more_params', array(
            'post_type'      => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'category'       => $atts['category'],
            'paged'          => intval($atts['paged']),
        ));
    
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
        if (!is_user_logged_in()) {
            return '';
        }

        if (current_user_can('administrator') || PSI_User::is_staff_member_editor()) {
            $base_url = get_bloginfo('url');
            $edit_project_url = trailingslashit($base_url) . 'edit-article?article-name=' . get_the_ID();

            return '<i class="fa-solid fa-pen-to-square" style="padding-right: 0.5em;"></i><a href="' . esc_url($edit_project_url) . '" style="display:inline-block; margin-bottom:10px;">Edit Press Submission</a>';
        }
    
        return '';
    }

    public function featured_image_with_caption() {
        global $post;

        if (!has_post_thumbnail($post->ID)) {
            return ''; 
        }

        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'full');

        $caption = get_post_field('post_excerpt', $thumbnail_id);

        if (empty($caption)) {
            return '';
        }

        // Find the position of "Credit:" in the caption
        $credit_position = strpos($caption, 'Credit:');

        if ($credit_position !== false) {
            // Split the caption into two parts and format with a line break
            $before_credit = substr($caption, 0, $credit_position);
            $after_credit = substr($caption, $credit_position);
            $caption_content = sprintf(
                '<p class="featured-caption"><em>%s<br>%s</em></p>',
                esc_html($before_credit),
                esc_html($after_credit)
            );
        } else {
            // Use the caption as is
            $caption_content = sprintf(
                '<p class="featured-caption"><em>%s</em></p>',
                esc_html($caption)
            );
        }

        return '<div class="featured-image-wrapper">
        <img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($caption) . '" style="width: auto; height: auto; max-width: 100%;" />
        ' . $caption_content . '
    </div>';
    
    }

    public function project_form_link($atts) {
        
        if (current_user_can('administrator') || PSI_User::is_staff_member_editor()) {
            $atts = shortcode_atts(
                array(
                    'url' => '',
                    'text' => 'Go To Projects',
                    'icon' => true
                ),
                $atts
            );
    
            if (empty($atts['url'])) {
                return ''; 
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
            return '';
        }
    }

    public function social_share_buttons_shortcode() {
        // Get the current post URL
        $post_url = rawurlencode(get_permalink());
        
        // Get the current post title
        $post_title = get_the_title();
        
        // Get the featured image URL (if you want to include it in the Pinterest share)
        $post_image = wp_get_attachment_url(get_post_thumbnail_id());
    
        // Get the post excerpt for sharing
        $post_excerpt = rawurlencode(get_the_excerpt());
    
        // Generate the markup
        $output = '<ul class="social-share-buttons">';
        
        // Facebook
        $output .= '<li><a id="facebook" class="social-share-button" href="https://www.facebook.com/sharer/sharer.php?u=' . $post_url . '" title="Share this post!"><i class="fa-brands fa-facebook-f fa-xs"></i></a></li>';
        
        // Twitter
        $output .= '<li><a id="twitter" class="social-share-button" href="https://twitter.com/intent/tweet?url=' . $post_url . '&text=' . $post_title . '" title="Tweet this post!"><i class="fa-brands fa-twitter fa-xs"></i></a></li>';
        
        // LinkedIn
        $output .= '<li><a id="linkedin" class="social-share-button" href="https://www.linkedin.com/shareArticle?url=' . $post_url . '&title=' . $post_title . '" title="Share this post!"><i class="fa-brands fa-linkedin-in fa-xs"></i></a></li>';
        
        // Pinterest
        if ($post_image) {
            $output .= '<li><a id="pinterest" class="social-share-button" href="https://pinterest.com/pin/create/button/?url=' . $post_url . '&media=' . rawurlencode($post_image) . '&description=' . $post_title . '" title="Pin this post!"><i class="fa-brands fa-pinterest fa-xs"></i></a></li>';
        }
        
        // Email
        $output .= '<li><a id="email" class="social-share-button" href="mailto:?subject=' . $post_title . '&body=Check out this post: ' . $post_url . '" target="_top" title="Email this post!"><i class="fa-regular fa-envelope fa-xs"></i></a></li>';
        
        $output .= '</ul>';
    
        // Add the JavaScript code to open links in a new window
        $output .= '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var links = document.querySelectorAll(".social-share-button");
            links.forEach(function(el) {
                el.addEventListener("click", function(event) {
                    event.preventDefault();
                    window.open(el.href, "", "width=600,height=300");
                });
            });
        });
        </script>
        ';
        
        return $output;
    }
    
    
    
    
}
