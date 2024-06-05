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
        $end_date = \DateTime::createFromFormat('m/d/Y', $end_date);
    
        // Get the current date
        $current_date = new \DateTime();
    
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
        // Retrieve the single user ID of the psi_lead for each project
        $a_psi_lead_user = get_post_meta($a->ID, 'psi_lead', true);
        $b_psi_lead_user = get_post_meta($b->ID, 'psi_lead', true);

        // Retrieve the role of the psi_lead for each project
        $a_psi_lead_role = get_post_meta($a->ID, 'psi_lead_role', true);
        $b_psi_lead_role = get_post_meta($b->ID, 'psi_lead_role', true);

        // Prioritize project where the user is the Principal Investigator
        if ($a_psi_lead_user == $user_id && $a_psi_lead_role == 'Principal Investigator') {
            return -1; // $a should come before $b
        } elseif ($b_psi_lead_user == $user_id && $b_psi_lead_role == 'Principal Investigator') {
            return 1; // $b should come before $a
        }

        // Next, prioritize if the user is the Institutional PI
        if ($a_psi_lead_user == $user_id && $a_psi_lead_role == 'Institutional PI') {
            return -1; // $a should come before $b
        } elseif ($b_psi_lead_user == $user_id && $b_psi_lead_role == 'Institutional PI') {
            return 1; // $b should come before $a
        }

        // Next, prioritize if the user is the Science PI
        if ($a_psi_lead_user == $user_id && $a_psi_lead_role == 'Science PI') {
            return -1; // $a should come before $b
        } elseif ($b_psi_lead_user == $user_id && $b_psi_lead_role == 'Science PI') {
            return 1; // $b should come before $a
        }

        // If none of the conditions match, maintain the current order
        return 0;
    }

    public static function get_programs_with_active_projects($programs, $active = 'active') {
        //error_log($active);
        if($active === 'past') {
            $date_comparison = '<';
        } else {
            $date_comparison = '>=';
        }

        
        $programs_with_projects = array();

        foreach ($programs as $program_id) {
            // Check if the program has associated projects with end_date in the future
            $projects_query = new \WP_Query(array(
                'post_type' => 'project',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'funding-program',
                        'field' => 'id',
                        'terms' => $program_id
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => 'end_date',
                        'value' => date('Y-m-d'),
                        'type' => 'DATE',
                        'compare' => $date_comparison, // Check if end_date is greater than or equal to current date
                    )
                )
            ));

            if ($projects_query->have_posts()) {
                $programs_with_projects[] = $program_id;
            }
            
            // Reset Post Data
            wp_reset_postdata();
        }

        return $programs_with_projects;
    }
    
}
