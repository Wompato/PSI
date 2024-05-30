<?php

namespace PSI;

class Utils {
    /**
    * Check if a given post is a past project based on its end date.
    *
    * @param object $post The WordPress post object.
    * @return bool True if the post is a past project, false otherwise.
    */
    public static function is_past_project($post) {
        // Check if $post is a valid object
        if (!is_object($post) || empty($post->ID)) {
            return false; // Return false if $post is not valid
        }
    
        if ($post->post_type !== 'project') {
            return false;
        }
        
        // Get the end_date
        $end_date = get_field('end_date', $post->ID);
        
        // Check if end_date exists
        if (!$end_date) {
            return false; // Return false if end_date does not exist
        }
        
        // Convert end_date to DateTime object
        $end_date = DateTime::createFromFormat('m/d/Y', $end_date);
    
        // Get the current date
        $current_date = new DateTime();
    
        // Check if end_date is past the current date
        if ($end_date && $end_date < $current_date) {
            
            return true; // Project is past
        } else {
            return false; // Project is ongoing or in the future
        }
    }

    /**
     * Sort user projects.
     *
     * @param WP_Post $a First project post object.
     * @param WP_Post $b Second project post object.
     * @param int $user_id The user ID to sort projects for.
     * @return int Sorting value.
     */
    public static function sort_user_projects($a, $b, $user_id) {
        
        $a_psi_lead = get_post_meta($a->ID, 'psi_lead', true);
        $b_psi_lead = get_post_meta($b->ID, 'psi_lead', true);

        $a_psi_lead_user = isset($a_psi_lead[0]) ? $a_psi_lead[0] : '';
        $b_psi_lead_user = isset($b_psi_lead[0]) ? $b_psi_lead[0] : '';

        $a_psi_lead_role = get_post_meta($a->ID, 'psi_lead_role', true);
        $b_psi_lead_role = get_post_meta($b->ID, 'psi_lead_role', true);

        // Check if either $a or $b is the lead for the user and is a principal investigator
        if ($a_psi_lead_user == $user_id && $a_psi_lead_role == 'Principal Investigator') {
            return -1; // $a should come before $b
        } elseif ($b_psi_lead_user == $user_id && $b_psi_lead_role == 'Principal Investigator') {
            return 1; // $b should come before $a
        }
    
        // Check if either $a or $b is the lead for the user and is an Institutional PI
        if ($a_psi_lead_user == $user_id && $a_psi_lead_role == 'Institutional PI') {
            return -1; // $a should come before $b
        } elseif ($b_psi_lead_user == $user_id && $b_psi_lead_role == 'Institutional PI') {
            return 1; // $b should come before $a
        }
    
        // If none of the conditions match, maintain the current order
        return 0;
    }

    
}
