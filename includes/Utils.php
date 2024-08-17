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
        
        $end_date = get_field('end_date', $post->ID);
        
        if (!$end_date) {
            return false;
        }
        
        // Convert end_date to DateTime object
        $end_date = \DateTime::createFromFormat('m/d/Y', $end_date);
    
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
            
            wp_reset_postdata();
        }

        return $programs_with_projects;
    }

    public static function get_project_users($project_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'project_user_roles';
    
        $sql = $wpdb->prepare("
            SELECT user_id, role 
            FROM $table_name 
            WHERE project_id = %d", 
            $project_id
        );
    
        $results = $wpdb->get_results($sql);
    
        $users = [];
        foreach ($results as $row) {
            $user_info = get_userdata($row->user_id);
            if ($user_info) {
                // Create an object for each user role
                $user = new \stdClass();
                $user->ID = $row->user_id;
                $user->display_name = $user_info->display_name;
                $user->role = $row->role;

                $users[] = $user;
            }
        }
        
    
        return $users;
    }

    public static function update_project_users($project_id, $user_roles) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'project_user_roles';
    
        $project_id = intval($project_id);
    
        // Check if a valid project ID is passed and if the project exists
        if ($project_id <= 0 || !get_post($project_id)) {
            error_log('Invalid or non-existent project ID: ' . $project_id);
            return; // Exit the function if the project ID is not valid
        }
    
        $wpdb->query('START TRANSACTION');
    
        try {
            // Clear existing relationships for this project
            $wpdb->delete($table_name, ['project_id' => $project_id]);
    
            // Iterate over each user-role pair and insert into the database
            foreach ($user_roles as $user_role) {
                // Ensure user_id is valid and non-empty
                if (!empty($user_role->id) && is_numeric($user_role->id)) {
                    $user_id = intval($user_role->id);
                    $user_data = get_userdata($user_id);
    
                    // Proceed with insertion
                    if ($user_data && isset($user_role->role) && !empty($user_role->role)) {
                        $wpdb->insert($table_name, [
                            'project_id' => $project_id,
                            'user_id' => $user_id,
                            'role' => sanitize_text_field($user_role->role)
                        ]);
                    }
                }
            }
    
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Failed to update project user roles: ' . $e->getMessage());
        }
    }
    
    public static function get_user_active_projects($user_id, $offset = 0, $number_of_posts = 4) {
        global $wpdb;

        $projects = $wpdb->get_results($wpdb->prepare(
            "(SELECT p.* FROM {$wpdb->posts} p
              INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
              WHERE p.post_type = 'project'
              AND pm.meta_key = 'psi_lead'
              AND pm.meta_value = %d)
            UNION
            (SELECT p.* FROM {$wpdb->posts} p
              INNER JOIN {$wpdb->prefix}project_user_roles pur ON p.ID = pur.project_id
              WHERE p.post_type = 'project'
              AND pur.user_id = %d)
            ORDER BY post_date DESC",
            $user_id, $user_id
        ));
        

        $active_projects = array_filter($projects, function($project) {
            return !self::is_past_project($project);
        });        

        usort($active_projects, function($a, $b) use ($user_id) {
            return self::sort_user_projects($a, $b, $user_id);
        });
    
        $initial_projects = array_slice($active_projects, $offset, $number_of_posts);
        
        $has_more = count($active_projects) > ($offset + $number_of_posts);
    
        return [
            'projects' => $initial_projects,
            'has_more' => $has_more
        ];
    }
    
    public static function get_user_past_projects($user_id, $offset = 0, $number_of_posts = 4) {
        global $wpdb;
    
        $projects = $wpdb->get_results($wpdb->prepare(
            "(SELECT p.* FROM {$wpdb->posts} p
              INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
              WHERE p.post_type = 'project'
              AND pm.meta_key = 'psi_lead'
              AND pm.meta_value = %d)
            UNION
            (SELECT p.* FROM {$wpdb->posts} p
              INNER JOIN {$wpdb->prefix}project_user_roles pur ON p.ID = pur.project_id
              WHERE p.post_type = 'project'
              AND pur.user_id = %d)
            ORDER BY post_date DESC",
            $user_id, $user_id
        ));
        
        $past_projects = array_filter($projects, function($project) {
            return self::is_past_project($project);
        }); 

        usort($past_projects, function($a, $b) use ($user_id) {
            return self::sort_user_projects($a, $b, $user_id);
        });
    
        $initial_projects = array_slice($past_projects, $offset, $number_of_posts);
        $has_more = count($past_projects) > ($offset + $number_of_posts);

        return [
            'projects' => $initial_projects,
            'has_more' => $has_more
        ];
    }
    
}
