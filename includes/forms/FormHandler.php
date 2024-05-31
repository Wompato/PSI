<?php

namespace PSI\Forms;

use PSI\Forms\Managers\ProjectManager;
use PSI\Forms\Managers\ArticleManager;
use PSI\Forms\Managers\StaffManager;
use PSI\Users\PSI_User;
use PSI\Forms\GWiz\GravityFormsCustomizations;

class FormHandler {

    private static $instance = null;
    
    private $gravityFormsCustomizations;
    
    private $projectManager;
    private $articleManager;
    private $staffManager;

    private function __construct() {

        $this->projectManager = new ProjectManager();
        $this->articleManager = new ArticleManager();
        $this->staffManager   = new StaffManager();

        $this->gravityFormsCustomizations = new GravityFormsCustomizations();

        // ACF FORM Update Profile
        add_action('acf/save_post', array($this, 'update_user_fields'), 30);

        // GForms Add-Ons 
        // Add excerpt to Gravity Forms Advanced Post Creation
        add_filter( 'gform_advancedpostcreation_excerpt', function( $enable_excerpt ) {
            return true;
        }, 10, 1 );

        // Restrict access to listed forms by user role
        add_filter('gform_pre_render', array($this, 'restrict_project_form_access')); 
    
    } 

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function update_user_related_projects($user_id, $project_id) {
        // Retrieve related projects, handling the case where it might be an empty string
        $related_projects = get_user_meta($user_id, 'related_projects_and_initiatives', true);
    
        // Convert empty string to empty array
        if (empty($related_projects)) {
            $related_projects = [];
        }
    
        // Add current project if not already present
        if (!in_array($project_id, $related_projects)) {
            $related_projects[] = $project_id;
            update_user_meta($user_id, 'related_projects_and_initiatives', $related_projects);
        }
    }

    public function update_user_fields($post_id) {
        if (is_page('edit-user')) {

            $user_id = str_replace('user_', '', $post_id);
            
            $field_groups = array(
                'field_658219e86331c', // profile pictures group field key
                'field_652f531642964', // professional interests group field key
                'field_65318b433fabd', // professional history group field key
                'field_65318bc05ad17' // honors and awards group field key
            );

            $profile_pictures_group_field = 'field_658219e86331c';
            $profile_pictures_group_subfields = array(
                'primary_picture' => ($_POST['acf']['field_65821a416331d']),
                'professional_history_picture' => ($_POST['acf']['field_65821a686331e']),
                'honors_and_awards_picture' => ($_POST['acf']['field_65821aa06331f']),
                'icon_picture' => ($_POST['acf']['field_65821aaf63320']),
            );

            $professional_interest_group_field = 'field_652f531642964';
            $professional_interest_group_subfields = array(
                'professional_interests_text' => ($_POST['acf']['field_6531887586121']),
                'professional_interests_images' => ($_POST['acf']['field_6553c9ab418c0']),
                'professional_interests_image_caption' => ($_POST['acf']['field_653188ad86124']),
            );

            $professional_history_group_field = 'field_65318b433fabd';
            $professional_history_group_subfields = array(
                'professional_history_text' => ($_POST['acf']['field_65318b433fabe']),
                'professional_history_images' => ($_POST['acf']['field_6553c9eac423f']),
                'professional_history_image_caption' => ($_POST['acf']['field_65318b433fac1']),
            );

            $honors_and_awards_group_field = 'field_65318bc05ad17';
            $honors_and_awards_group_subfields = array(
                'honors_and_awards_text' => ($_POST['acf']['field_65318bc05ad18']),
                'honors_and_awards_images' => ($_POST['acf']['field_6553ca6855149']),
                'honors_and_awards_image_caption' => ($_POST['acf']['field_65318bc05ad1b']),
            );
    
            $meta_fields = array(
                'cv' => sanitize_text_field($_POST['acf']['field_652f53163ade2']),
                'publications_link' => sanitize_text_field($_POST['acf']['field_6579fb74c2164']),
                'publications_url' => sanitize_text_field($_POST['acf']['field_65c29cc948514']),
                'personal_page' => sanitize_text_field($_POST['acf']['field_6531839aff808']),
                'display_in_directory' => sanitize_text_field($_POST['acf']['field_653fddd65f0b3']),
                'targets_of_interests' => sanitize_text_field($_POST['acf']['field_6594dae77bc2e']),
                'disciplines_techniques' => sanitize_text_field($_POST['acf']['field_6594dafa7bc2f']),
                'missions' => sanitize_text_field($_POST['acf']['field_6594db127bc30']),
                'mission_roles' => sanitize_text_field($_POST['acf']['field_6594db247bc31']),
                'instruments' => sanitize_text_field($_POST['acf']['field_6594db2c7bc32']),
                'facilities' => sanitize_text_field($_POST['acf']['field_6594db387bc33']),
                'twitter_x' => sanitize_text_field($_POST['acf']['field_65ca58de9e649']),
                'linkedin' => sanitize_text_field($_POST['acf']['field_65ca58f69e64a']),
                'youtube' => sanitize_text_field($_POST['acf']['field_65ca59179e64c']),
                'facebook' => sanitize_text_field($_POST['acf']['field_65ca59099e64b']),
                'instagram' => sanitize_text_field($_POST['acf']['field_65ca59209e64d']),
                'github' => sanitize_text_field($_POST['acf']['field_65d905aa86ece']),
                'orchid' => sanitize_text_field($_POST['acf']['field_65d932851c5fa']),
                'gscholar' => sanitize_text_field($_POST['acf']['field_65d932951c5fb']),
            );
    
            foreach ($meta_fields as $meta_key => $meta_value) {
                update_field($meta_key, $meta_value, 'user_'.$user_id);
            }

            update_field($profile_pictures_group_field, $profile_pictures_group_subfields, 'user_'.$user_id);
            update_field($professional_interest_group_field, $professional_interest_group_subfields, 'user_'.$user_id);
            update_field($professional_history_group_field, $professional_history_group_subfields, 'user_'.$user_id);
            update_field($honors_and_awards_group_field, $honors_and_awards_group_subfields, 'user_'.$user_id);
        }
    }

    public function restrict_project_form_access($form) {
        
        if ($form['id'] != 8 && $form['id'] != 10) {
            return $form; 
        }
    
        if (!is_user_logged_in()) {
            // Redirect non-logged-in users to the home page
            wp_redirect(home_url());
            exit;
        }
    
        // Get the current user and project ID from the query parameter
        $user = wp_get_current_user();
        $user_id = (int) $user->ID;
        $project_id = isset($_GET['project-name']) ? (int) $_GET['project-name'] : 0;
    
        // Get the PSI lead ID for the project
        $psi_lead = get_field('psi_lead', $project_id);

        if (PSI_User::is_staff_member_editor() || current_user_can('manage_options')) {
            return $form; // Allow staff member editor or administrator to access the form
        }
    
        // Check if the PSI lead matches the current user
        if (isset($psi_lead[0]->ID) && $user_id === $psi_lead[0]->ID) {
            return $form; // Allow PSI lead to access the form
        }
    
        // Redirect unauthorized users to the home page
        wp_redirect(home_url());
        exit;
    }
    
    public static function get_post_date_time_fields($date, $time) {
        // Retrieve the WordPress time zone setting
        $wp_timezone = get_option('timezone_string');
        $timezone = new \DateTimeZone($wp_timezone ?: 'UTC');
    
        
        $converted_date = !empty($date) ? new \DateTime($date, $timezone) : new DateTime('now', $timezone);
        $formatted_date = $converted_date->format('Y-m-d');
    
        // Convert time from "hh:mm am/pm" to "H:i:s"
        $converted_time = !empty($time) ? \DateTime::createFromFormat('h:i a', $time) : false;
        
        // Check if $converted_time is false
        if ($converted_time === false) {
            // Handle the case where $converted_time is false
            throw new \Exception('Invalid time format: ' . $time);
        }
    
        $formatted_time = $converted_time->format('H:i:s');
    
        // Combine date and time
        if($formatted_time){
            $post_date = new \DateTime("{$formatted_date} {$formatted_time}", $timezone);
        }
        else{
            throw new \Exception('Formatted time is empty.');
        }
        $current_date = new \DateTime('now', $timezone);
    
        // Determine post status based on whether the date is in the future
        $post_status = ($post_date > $current_date) ? 'future' : 'publish';
    
        // Format post date for local time and GMT
        $post_date_formatted = $post_date->format('Y-m-d H:i:s');
        $post_date_gmt = $post_date->setTimezone(new \DateTimeZone('GMT'))->format('Y-m-d H:i:s');
    
        // Return the relevant fields
        return array(
            'post_date'     => $post_date_formatted,
            'post_date_gmt' => $post_date_gmt,
            'post_status'   => $post_status,
        );
    }
    
    // Function to convert bytes into human-readable format
    public static function human_filesize($bytes, $decimals = 2) {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sizes[$factor];
    }
}