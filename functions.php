<?php

require_once __DIR__ . '/vendor/autoload.php';

class PSI_Child_Theme {
    public function __construct() { 

        PSI\Users\PSI_User::getInstance();
        PSI\Rewrites::getInstance();
        PSI\Shortcodes\PSI_Shortcodes::getInstance();
        PSI\Widgets\Widget_Loader::getInstance();
        PSI\Forms\FormHandler::getInstance();

        add_action('rest_api_init', [PSI\API\Endpoints::class, 'register_endpoints']);
        
        add_filter( 'send_email_change_email', '__return_false' );

        // Single Post Pages get the grid of posts as the bottom.
        add_filter('the_content', array($this, 'display_additional_images_in_single_post'));

        // Modify menu items
        add_filter('wp_nav_menu_items', array($this, 'modify_menu_items'), 10, 2);  

        // Handle conflict of Select2 library
        add_action('admin_enqueue_scripts', array($this, 'enqueue_acf_select2_and_dequeue_wcd_select2_admin'), 99);

        // Enqueue custom scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_scripts'));

        // Enqueue Slick.js from CDN
        add_action('wp_enqueue_scripts', array($this, 'enqueue_slick_from_cdn'));

        add_filter('render_block', function ($block_content, $block) {
            if (!is_admin() && !empty($block['attrs']['className']) && strpos($block['attrs']['className'], 'related-users-hook') !== false) {
                // Get the current post ID
                $post_id = get_the_ID();
        
                // Generate the content using the [related_users] shortcode
                $shortcode_content = do_shortcode("[related_users ID={$post_id}]");
        
                // Enqueue the "slick-init.js" script
                wp_enqueue_script('slick-init', get_stylesheet_directory_uri() . '/js/slick-init.js', array('jquery'), '1.0', true);
        
                // Replace the block content with the generated content
                $block_content = $shortcode_content;
            }
        
            return $block_content;
        }, 10, 2);
        
        add_filter('render_block', function ($block_content, $block) {
            if (!is_admin() && !empty($block['attrs']['className']) && strpos($block['attrs']['className'], 'related-users-hook-large') !== false) {
                // Get the current post ID
                $post_id = get_the_ID();
        
                // Generate the content using the [related_users] shortcode
                $shortcode_content = do_shortcode("[related_users ID={$post_id} class='large'] ");
        
                // Replace the block content with the generated content
                $block_content = $shortcode_content;
            }
        
            return $block_content;
        }, 10, 2);
        
    }

    /**
     * Display additional images in a single post content.
     *
     * @param string $content The original post content.
     * @return string The modified post content with additional images appended.
     */
    public function display_additional_images_in_single_post($content) {
        global $post;
    
        if (is_single() && $post) {
            $additional_images = get_field('field_65ba89acc66e1', $post->ID);
    
            if ($additional_images && is_array($additional_images)) {
                $image_html = '<div class="image-grid">';
    
                foreach ($additional_images as $attachment_id) {
                    $image_html .= '<div class="grid-item">';
                    $image_html .= wp_get_attachment_image($attachment_id, 'medium');
                    $image_html .= '<p>' . esc_html(get_post_field('post_excerpt', $attachment_id)) . '</p>'; // Display the caption
                    $image_html .= '</div>';
                }
    
                $image_html .= '</div>';
    
                $content .= $image_html;
            }
        }
    
        return $content;
    }

    function modify_menu_items($items, $args) {
        // Check if it's the primary or off-canvas menu and the user is logged in
        if ((($args->theme_location == 'primary' || $args->theme_location == 'off-canvas') && !is_admin()) || ($args->menu->slug == 'primary' && !is_admin())) {
            // Check if "Staff" menu item exists
            $staff_menu_item_position = strpos($items, 'menu-item-22859');
            
            if ($staff_menu_item_position !== false) {
                // Check if the user is logged in
                if (is_user_logged_in()) {
                    // User is logged in, add Logout link
                    $current_user = wp_get_current_user();
                    $logout_url = wp_logout_url(home_url('/')); // Logout URL with redirect to home
                    $logout_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-logout"><a href="' . esc_url($logout_url) . '">Logout</a></li>';

                     // Get the user's slug from the usermeta field
                    $user_slug = get_user_meta($current_user->ID, 'user_slug', true);
                    $profile_url = home_url('/staff/profile/' . $user_slug); // Adjust the URL structure as needed
                    $profile_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-profile"><a href="' . esc_url($profile_url) . '">My Profile</a></li>';

                    // Find the end of "Staff" submenu and insert Logout and My Profile links
                    $submenu_end_position = strpos($items, '</ul>', $staff_menu_item_position);
                    if ($submenu_end_position !== false) {
                        $items = substr_replace($items, $profile_link . $logout_link, $submenu_end_position, 0);
                    }
                } else {
                    // User is logged out, add Login link
                    $login_url = wp_login_url(home_url('/')); // Login URL with redirect to home
                    $login_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-login"><a href="' . esc_url($login_url) . '">Login</a></li>';
                    // Find the end of "Staff" submenu and insert Login link
                    $submenu_end_position = strpos($items, '</ul>', $staff_menu_item_position);
                    if ($submenu_end_position !== false) {
                        $items = substr_replace($items, $login_link, $submenu_end_position, 0);
                    }
                }
            }
        }
        return $items;
    }

    public function enqueue_acf_select2_and_dequeue_wcd_select2_admin() {
        // Dequeue the West Coast Digital version of Select2 in the admin area.
        wp_dequeue_style('select2');
        wp_deregister_style('select2');
        wp_dequeue_script('select2');
        wp_deregister_script('select2');
    
        // Load the proper compatible version for ACF and Social Share
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        // Must be full version
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js', array('jquery') );
    }

    public function enqueue_custom_scripts() {
        // JS for the user profile pages (only load if on the /profile/ route)
        if (is_page() && strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) {
            wp_enqueue_script('user-profile-scripts', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '1.2', true);
        }
            
        if(!is_singular('post')){
            wp_enqueue_script('slick-init', get_stylesheet_directory_uri() . '/js/slick-init.js', array('jquery'), null, true);
        }

        if(is_singular('project')){
            wp_enqueue_script('single-project-scripts', get_stylesheet_directory_uri() . '/js/projects/project.js', array('jquery'), '1.4', true);
            //wp_enqueue_script('user-profile-scripts', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '1.1', true);
        }

        if(is_page('edit-staff-page')){
            wp_enqueue_script('edit-staffpage-scripts', get_stylesheet_directory_uri() . '/js/editStaffPage.js', array('jquery'), null, true);
        }

        if(is_page('edit-article')){
            wp_enqueue_script('edit-article', get_stylesheet_directory_uri() . '/js/editArticle.js', array('jquery'), '1.4', true);
        }
            
        if(is_page('press-submission')){
            wp_enqueue_script('article-form', get_stylesheet_directory_uri() . '/js/articleForm.js', array('jquery'), null, true);
        }

        if(is_page('edit-user')) {
            wp_enqueue_script('edit-user', get_stylesheet_directory_uri() . '/js/editProfile.js', array('jquery'), null, true);
        }

        if(is_page('edit-project')){
            wp_enqueue_script('edit-projects-scripts', get_stylesheet_directory_uri() . '/js/projects/editProject.js', array('jquery'), '2.0', true);
            wp_enqueue_script('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js', array(), '', true);
            //wp_enqueue_script('tom-select-styles', 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css', array(), '', true);
        }

        if(is_page('create-project')) {
            wp_enqueue_script('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js', array(), null, true);
            wp_enqueue_script('create-projects-scripts', get_stylesheet_directory_uri() . '/js/projects/createProject.js', array('jquery'), '1.2', true);
        }

        
    }
    
    public function enqueue_slick_from_cdn() {
        // Register and enqueue Slick.js from the CDN
        wp_enqueue_script('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);

        // Enqueue Slick.js CSS from the CDN
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', array(), '1.8.1', 'all');

        // Enqueue Slick.js theme CSS from the CDN (optional)
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', array(), '1.8.1', 'all');
    }
    
}

$psi_theme = new PSI_Child_Theme();