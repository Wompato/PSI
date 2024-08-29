<?php

namespace PSI\Users;

class PSI_User {

    private static $instance = null;
    
    private function __construct() {
        // Add hooks for user management tasks
        add_action('init', array($this, 'add_custom_roles'));
        // Modify attachments query so non admins can only access media files that they upload
        add_filter('ajax_query_attachments_args', array($this, 'modify_attachments_query'));
        // Redirect non admin users from the backend of the site
        add_filter('login_redirect', array($this, 'redirect_non_admin_users'), 10, 3);
        add_action('admin_init', array($this, 'restrict_non_admin_access'));
        // Hide admin bar for staff members and staff member editors
        add_action('init', array($this, 'hide_admin_bar_for_staff'));
        add_action('admin_menu', array($this, 'hide_admin_menus'), 999);

        add_filter('acf/load_field_group', array($this, 'hide_acf_fields'));
        
        add_action('init', array($this, 'add_project_caps'), 11);
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function add_custom_roles() {
        // Add Staff Member Role
        add_role(
            'staff_member',
            __('Staff Member'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'read_private_posts' => true,
                'edit_private_posts' => true,
                'delete_private_posts' => true,
                'publish_private_posts' => true,
                'read_published_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                // Add other capabilities as needed
            )
        );
    
        // Optionally, define a new role 'staff_member_editor' inheriting from 'staff_member'
        add_role(
            'staff_member_editor',
            __('Staff Member Editor'),
            get_role('staff_member')->capabilities
        );
    }

    public function hide_acf_fields($field_group) {
        
        if (!current_user_can('administrator')) {
            // User Profile Fields
            $hidden_field_groups = array('group_652f53160f1a4');

            if (in_array($field_group['key'], $hidden_field_groups)) {
                return false;
            }
        }

        return $field_group;
    }

    public function add_project_caps() {
        
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('edit_project');
            $admin->add_cap('read_project');
            $admin->add_cap('delete_project');
            $admin->add_cap('edit_projects');
            $admin->add_cap('edit_others_projects');
            $admin->add_cap('publish_projects');
            $admin->add_cap('read_private_projects');
            $admin->add_cap('delete_projects');
            $admin->add_cap('delete_private_projects');
            $admin->add_cap('delete_published_projects');
            $admin->add_cap('delete_others_projects');
            $admin->add_cap('edit_private_projects');
            $admin->add_cap('edit_published_projects');
            $admin->add_cap('create_projects');
        }

        $editor = get_role('editor');
        if ($editor) {
            $editor->remove_cap('edit_project');
            $editor->remove_cap('read_project');
            $editor->remove_cap('delete_project');
            $editor->remove_cap('edit_projects');
            $editor->remove_cap('edit_others_projects');
            $editor->remove_cap('publish_projects');
            $editor->remove_cap('read_private_projects');
            $editor->remove_cap('delete_projects');
            $editor->remove_cap('delete_private_projects');
            $editor->remove_cap('delete_published_projects');
            $editor->remove_cap('delete_others_projects');
            $editor->remove_cap('edit_private_projects');
            $editor->remove_cap('edit_published_projects');
            $editor->remove_cap('create_projects');

        }
    }

    /**
     * Check if a user is a staff member.
     *
     * @param int|WP_User $user User ID or WP_User object.
     * @return bool True if the user is a staff member, false otherwise.
     */
    public static function is_staff_member($user = null) {
        // If $user is not provided, assume the current user
        if (!$user) {
            $user = wp_get_current_user();
        } elseif (is_numeric($user)) {
            // If user ID is provided, get the WP_User object
            $user = get_user_by('ID', $user);
        }

        // Check if the user has the 'staff_member' role
        $is_staff_member = $user && in_array('staff_member', (array) $user->roles);
        
        return $is_staff_member;
    }

    /**
     * Check if a user is a staff member editor.
     *
     * @param int|WP_User $user User ID or WP_User object.
     * @return bool True if the user is a staff member editor, false otherwise.
     */
    public static function is_staff_member_editor($user = null) {
        // If $user is not provided, assume the current user
        if (!$user) {
            $user = wp_get_current_user();
        } elseif (is_numeric($user)) {
            // If user ID is provided, get the WP_User object
            $user = get_user_by('ID', $user);
        }

        // Check if the user has the 'staff_member_editor' role
        $is_staff_member_editor = $user && in_array('staff_member_editor', (array) $user->roles);

        return $is_staff_member_editor;
    }

    /**
     * Modify attachments query for non-admin users.
     *
     * @param array $query The original query arguments.
     * @return array Modified query arguments.
     */
    public function modify_attachments_query($query) {
        // If the current user is an admin, do not modify the query
        if (current_user_can('administrator')) {
            return $query;
        }

        // For all non-admin users, limit the query to only show media files they uploaded
        $user_id = get_current_user_id();
        $query['author'] = $user_id;

        return $query;
    }

    /**
     * Hide admin bar for staff members and staff member editors.
     */
    public function hide_admin_bar_for_staff() {
        if (self::is_staff_member() || self::is_staff_member_editor()) {
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /**
     * Redirect staff members and staff member editors away from the WordPress backend.
     *
     * @param string $redirect_to The redirect URL.
     * @param string $request The requested redirect URL.
     * @param object $user The user object.
     * @return string The modified redirect URL.
     */
    public function redirect_non_admin_users($redirect_to, $request, $user) {
        
        if(self::is_staff_member() || self::is_staff_member_editor()) {
            
            //return home_url();
        }
        return $redirect_to;
    }

    /**
     * Redirect non-admin users away from the WordPress admin area.
     */
    public function restrict_non_admin_access() {

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return; // Allow AJAX requests
        }

        $current_user = wp_get_current_user();

        if (self::is_staff_member($current_user) || self::is_staff_member_editor($current_user)) {
            wp_redirect(home_url());
            exit;
        }

    }

    public function hide_admin_menus() {
        if (!current_user_can('administrator')) {
            // Remove 'Projects' menu item
            remove_menu_page('edit.php?post_type=projects');
            
            remove_menu_page('wp-menu-icons');
            
            // Remove 'Tools' menu
            remove_menu_page('tools.php');
            
            // Remove 'Comments' menu
            remove_menu_page('edit-comments.php');
            
            // Remove 'Ninja Tables' menu item
            remove_menu_page('ninja_tables');
            
            // Remove 'Settings' menu
            remove_menu_page('options-general.php');

            // Remove 'Tags' submenu under 'Posts'
            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
            
            // Remove 'Categories' submenu under 'Posts'
            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
        }
    }

    public static function generate_unique_user_slug($first_name, $last_name, $user_id = null) {
        $base_slug = sanitize_title($first_name . '-' . $last_name); // Create a basic slug
        $user_slug = $base_slug;
        $i = 1;
    
        // Use self:: to refer to the current class
        while (self::user_slug_exists($user_slug, $user_id)) {
            $user_slug = $base_slug . '-' . $i; // Append suffix
            $i++;
        }
    
        return $user_slug;
    }
    

    public static function user_slug_exists($slug, $current_user_id = null) {
        global $wpdb;
        // Correct the table and query to check usermeta
        $query = $wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'user_slug' AND meta_value = %s",
            $slug
        );
        $users = $wpdb->get_results($query);
    
        if (empty($users)) {
            return false;
        }
    
        // If we're checking for a unique slug in the context of updating a user, exclude the current user's existing slug
        if ($current_user_id && count($users) === 1 && $users[0]->user_id == $current_user_id) {
            return false;
        }
    
        return true;
    }
    
    public static function get_user_profile_url($user_id) {
        // Ensure that the user ID is provided and is a valid number.
        if (empty($user_id) || !is_numeric($user_id)) {
            return false; // Return false if the user ID is not valid.
        }
    
        // Get the user slug from user meta.
        $user_slug = get_user_meta($user_id, 'user_slug', true);
        
        // Check if the user slug exists.
        if (empty($user_slug)) {
            return false; // Return false if the user slug is not set or empty.
        }
    
        // Construct the profile URL based on the local development environment.
        $profile_url = home_url("/staff/profile/{$user_slug}/");
    
        return $profile_url;
    }
}
