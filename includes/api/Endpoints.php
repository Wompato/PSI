<?php

namespace PSI\API;

use PSI\Utils;

class Endpoints {

    public static function register_endpoints() {
        register_rest_route('psi/v1', '/user-related-posts/(?P<userID>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_related_posts'),
        ));    
        register_rest_route('psi/v1', '/project-related-posts/(?P<projectID>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_project_related_posts'),
        ));     
        register_rest_route('psi/v1', '/load-more-posts/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'load_more_posts'),
        ));
        register_rest_route('psi/v1', '/projects/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'get_projects'),
        ));
        register_rest_route('psi/v1', '/active-projects/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'get_active_projects'),
        ));
        register_rest_route('psi/v1', '/past-projects/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'get_past_projects'),
        ));
        register_rest_route('psi/v1', '/active-user-projects/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'get_active_user_projects'),
        ));
        register_rest_route('psi/v1', '/past-user-projects/', array(
            'methods'  => 'GET',
            'callback' => array(__CLASS__, 'get_past_user_projects'),
        ));
        register_rest_route('psi/v1', '/project/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_project'),
        ));
        register_rest_route('psi/v1', '/post/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_post'),
        ));
        register_rest_route('psi/v1', '/user/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user'),
        ));
        register_rest_route('psi/v1', '/users', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_users'),
        ));
        register_rest_route('psi/v1', '/funding-programs/(?P<termId>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_funding_programs'),
        ));
    }

    public static function get_funding_programs($request) {
        // Check if termId parameter is provided in the request
        $term_id = $request->get_param('termId');
        $active = $request->get_param('active');

        // If termId is provided, retrieve funding programs associated with the term
        if ($term_id) {
            $programs = get_field('related_programs', 'funding-agency_' . $term_id);
            $filtered_programs = [];

            if($active === 'true') {
                $filtered_programs = \PSI\Utils::get_programs_with_active_projects($programs);
            } else {
                $filtered_programs = \PSI\Utils::get_programs_with_active_projects($programs, 'past');
            }

            $funding_programs = [];
            
            if ($filtered_programs && is_array($filtered_programs)) {
                foreach($filtered_programs as $program_id) {
                    $funding_programs[] = [
                        'id'    => $program_id,
                        'name'  => get_term_field('name', $program_id),
                    ];
                }
            }

            // Sort the funding programs alphabetically by 'name'
            usort($funding_programs, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            // Return the response
            return rest_ensure_response($funding_programs);
        } else {
            // If no termId is provided, retrieve all funding program terms
            $terms = get_terms(array(
                'taxonomy' => 'funding_program', // Adjust this based on your taxonomy
                'hide_empty' => false, // Include terms with no posts
            ));
    
            // Prepare the response
            $funding_programs = [];
            foreach ($terms as $term) {
                $funding_programs[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
    
            // Return the response
            return rest_ensure_response($funding_programs);
        }
    }
    

    public static function get_active_projects($request) {
        $agency_id = $request->get_param('agency_id');
        $program_id = $request->get_param('program_id');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount'); 
        $posts_to_skip = $request->get_param('skip');
    
        // Set up base args for WP_Query
        $args = [
            'post_type' => 'project',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Fetch all to filter later
            'tax_query' => []
        ];
    
        // Add tax_query filters if agency_id or program_id is provided
        if (!empty($agency_id)) {
            $args['tax_query'][] = [
                'taxonomy' => 'funding-agency',
                'field' => 'term_id',
                'terms' => $agency_id
            ];
        }
    
        if (!empty($program_id)) {
            $args['tax_query'][] = [
                'taxonomy' => 'funding-program',
                'field' => 'term_id',
                'terms' => $program_id
            ];
        }
    
        // Ensure multiple taxonomy queries work together
        if (!empty($agency_id) && !empty($program_id)) {
            $args['tax_query']['relation'] = 'AND';
        }
    
        $query = new \WP_Query($args);
        $all_projects = $query->posts;
    
        // Filter out past projects
        $active_projects = array_filter($all_projects, function($post) {
            return !\PSI\Utils::is_past_project($post);
        });
    
        // Apply manual pagination
        $start_index = ($page ?: 0) * $posts_per_page + $posts_to_skip;
        $selected_projects = array_slice($active_projects, $start_index, $posts_per_page);
    
        $has_more = count($active_projects) > $start_index + $posts_per_page;
    
        ob_start();
        foreach ($selected_projects as $post) {
            get_template_part('template-parts/projects/activity-banner', '', ['post' => $post]);
        }
        $html = ob_get_clean();
    
        return rest_ensure_response([
            'has_more' => $has_more,
            'html' => $html,
        ]);
    }
    
    public static function get_past_projects($request) {
        $agency_id = $request->get_param('agency_id');
        $program_id = $request->get_param('program_id');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount'); 
        $posts_to_skip = $request->get_param('skip');
    
        // Set up base args for WP_Query
        $args = [
            'post_type' => 'project',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Fetch all to filter later
            'tax_query' => []
        ];
    
        // Add tax_query filters if agency_id or program_id is provided
        if (!empty($agency_id)) {
            $args['tax_query'][] = [
                'taxonomy' => 'funding-agency',
                'field' => 'term_id',
                'terms' => $agency_id
            ];
        }
    
        if (!empty($program_id)) {
            $args['tax_query'][] = [
                'taxonomy' => 'funding-program',
                'field' => 'term_id',
                'terms' => $program_id
            ];
        }
    
        // Ensure multiple taxonomy queries work together
        if (!empty($agency_id) && !empty($program_id)) {
            $args['tax_query']['relation'] = 'AND';
        }
    
        $query = new \WP_Query($args);
        $all_projects = $query->posts;
    
        // Filter out past projects
        $active_projects = array_filter($all_projects, function($post) {
            return \PSI\Utils::is_past_project($post);
        });
    
        // Apply manual pagination
        $start_index = ($page ?: 0) * $posts_per_page + $posts_to_skip;
        $selected_projects = array_slice($active_projects, $start_index, $posts_per_page);
    
        $has_more = count($active_projects) > $start_index + $posts_per_page;
    
        ob_start();
        foreach ($selected_projects as $post) {
            get_template_part('template-parts/projects/activity-banner', '', ['post' => $post]);
        }
        $html = ob_get_clean();
    
        return rest_ensure_response([
            'has_more' => $has_more,
            'html' => $html,
        ]);
    }

    public static function get_active_user_projects($request) {
        $user_id = $request->get_param('userID');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount'); 
        $posts_to_skip = $request->get_param('skip');
        
        $response = array();
       
        if (empty($user_id)) {
            return new \WP_Error('invalid_parameter', __('User ID is required.'), array('status' => 400));
        }
    
        $related_posts = get_field('related_projects_and_initiatives', 'user_' . $user_id);
    
        if (!$related_posts) {
            return new \WP_Error('no_projects_found', __('No projects found for the user.'), array('status' => 404));
        }

        $active_related_posts = array_filter($related_posts, function($post) {
            return get_post_type($post) === 'project' && !Utils::is_past_project($post);
        });

        usort($active_related_posts, function($a, $b) use ($user_id) {
            return Utils::sort_user_projects($a, $b, $user_id);
        });

        $start_index = ($page ?: 0) * $posts_per_page + $posts_to_skip;
        $posts = array_slice($active_related_posts, $start_index, $posts_per_page);

        $has_more = count($active_related_posts) > $start_index + $posts_per_page;

        $response = array(
            'has_more' => $has_more,
            'html' => '',
        );

        if (!empty($posts)) {
            ob_start();
            foreach ($posts as $post) {
                get_template_part('template-parts/projects/activity-banner', '', array(
                    'page' => $page,
                    'post' => $post,
                ));
            }
            $response['html'] = ob_get_clean();
        }

        return rest_ensure_response($response);
    }

    public static function get_past_user_projects($request) {
        $user_id = $request->get_param('userID');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount'); 
        $posts_to_skip = $request->get_param('skip');
        
        $response = array();
       
        if (empty($user_id)) {
            return new \WP_Error('invalid_parameter', __('User ID is required.'), array('status' => 400));
        }
    
        $related_posts = get_field('related_projects_and_initiatives', 'user_' . $user_id);
    
        if (!$related_posts) {
            return new \WP_Error('no_projects_found', __('No projects found for the user.'), array('status' => 404));
        }

        // Filter out active projects
        $past_related_posts = array_filter($related_posts, function($post) {
            return Utils::is_past_project($post) === true;
            
        });

        usort($past_related_posts, function($a, $b) use ($user_id) {
            return Utils::sort_user_projects($a, $b, $user_id);
        });

        $start_index = ($page ?: 0) * $posts_per_page + $posts_to_skip;
        $posts = array_slice($past_related_posts, $start_index, $posts_per_page);

        $has_more = count($past_related_posts) > $start_index + $posts_per_page;

        $response = array(
            'has_more' => $has_more,
            'html' => '',
        );

        if (!empty($posts)) {
            ob_start();
            foreach ($posts as $post) {
                get_template_part('template-parts/projects/activity-banner', '', array(
                    'page' => $page,
                    'post' => $post,
                ));
            }
            $response['html'] = ob_get_clean();
        }

        return rest_ensure_response($response);
    }

    public static function get_user_related_posts($request) {
        // Retrieve parameters from the request
        $user_id = $request->get_param('userID');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount');
        $posts_to_skip = $request->get_param('skip');
    
        $response = array();
    
        // Check if user ID is provided
        if (!$user_id) {
            return new \WP_Error('missing_user_id', 'User ID is required.', array('status' => 400));
        }
    
        // Check if user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return new \WP_Error('invalid_user_id', 'Invalid user ID.', array('status' => 404));
        }
    
        // Retrieve related posts for the user
        $related_posts = get_field('related_posts', 'user_' . $user_id);
    
        // Sort the related posts by date in descending order
        usort($related_posts, function ($a, $b) {
            $date_a = strtotime($a->post_date);
            $date_b = strtotime($b->post_date);
            return ($date_a < $date_b) ? 1 : -1;
        });
    
        // Calculate start index based on pagination
        $start_index = ($page) * $posts_per_page + $posts_to_skip;
    
        // Retrieve posts for the current page
        $posts = array_slice($related_posts, $start_index, $posts_per_page);
    
        // Check if there are more posts
        $has_more = count($related_posts) > $start_index + $posts_per_page;
        $response['has_more'] = $has_more;
    
        // Generate HTML for related posts
        ob_start();
        foreach ($posts as $post) {
            get_template_part('template-parts/related-post', '', array(
                'page' => $page,
                'post' => $post,
            ));
        }
        $response['html'] = ob_get_clean();
    
        // Return response
        return rest_ensure_response($response);
    }
    
    public static function get_project_related_posts($request) {
        // Retrieve parameters from the request
        $project_id = $request->get_param('projectID');
        $page = $request->get_param('page');
        $posts_per_page = $request->get_param('amount');
        $posts_to_skip = $request->get_param('skip');
    
        $response = array();
    
        // Check if project ID is provided
        if (!$project_id) {
            return new \WP_Error('missing_project_id', 'Project ID is required.', array('status' => 400));
        }
    
        // Retrieve related posts for the project
        $related_posts = get_field('related_articles', $project_id); // Assuming 'related_articles' is the meta field name for related posts
    
        // Check if related posts exist
        if (!$related_posts) {
            // No related posts found for the project
            return new \WP_Error('no_related_posts', 'No related posts found for the project.', array('status' => 404));
        }
    
        // Sort the related posts by date in descending order
        usort($related_posts, function ($a, $b) {
            $date_a = strtotime($a->post_date);
            $date_b = strtotime($b->post_date);
            return ($date_a < $date_b) ? 1 : -1;
        });
    
        // Calculate start index based on pagination
        $start_index = ($page) * $posts_per_page + $posts_to_skip;
    
        // Retrieve posts for the current page
        $posts = array_slice($related_posts, $start_index, $posts_per_page);
    
        // Check if there are more posts
        $has_more = count($related_posts) > $start_index + $posts_per_page;
        $response['has_more'] = $has_more;
    
        // Generate HTML for related posts
        ob_start();
        foreach ($posts as $post) {
            get_template_part('template-parts/related-post', '', array(
                'page' => $page,
                'post' => $post,
            ));
        }
        $response['html'] = ob_get_clean();
    
        // Return response
        return rest_ensure_response($response);
    }
    
    public static function load_more_posts($data) {
        $post_type      = sanitize_text_field($data['post_type']);
        $posts_per_page = intval($data['posts_per_page']);
        $category       = sanitize_text_field($data['category']);
        $page_number    = intval($data['page']) + 1;
        $search_keyword = sanitize_text_field($data['search_keyword']);
        //$search_staff   = sanitize_text_field($data['search_staff']);
        $start_date     = sanitize_text_field($data['start_date']);
        $end_date       = sanitize_text_field($data['end_date']);

        // Base args
        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts_per_page,
            'category_name'  => $category,
            'paged'          => $page_number,
            'orderby'        => 'date',
            'order'          => 'DESC' 
        ];
    
        // Adding keyword search if not empty
        if (!empty($search_keyword)) {
            $args['s'] = $search_keyword;
        }
    
        $date_query = [];

        // Check if the end date is provided and valid
        if (!empty($end_date)) {
            $date_query['before'] = [
                'year'  => date('Y', strtotime($end_date)),
                'month' => date('m', strtotime($end_date)),
                'day'   => date('d', strtotime($end_date)),
                'inclusive' => true
            ];
        }
        
        // Check if the start date is provided and valid
        if (!empty($start_date)) {
            $date_query['after'] = [
                'year'  => date('Y', strtotime($start_date)),
                'month' => date('m', strtotime($start_date)),
                'day'   => date('d', strtotime($start_date)),
                'inclusive' => true
            ];
        }
        
        // If either start date or end date is provided, add the date_query to args
        if (!empty($date_query)) {
            $args['date_query'] = [$date_query]; // Note the array wrapping
        }
    
        $query = new \WP_Query($args);

        $response = array();
        $response['has_more'] = $query->max_num_pages > $page_number;
    
        ob_start();
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                get_template_part('template-parts/load-more-item', null, array('page_number' => $page_number)); 
            endwhile;
        endif;
        wp_reset_postdata();
        $response['html'] = ob_get_clean();
    
        return rest_ensure_response($response);
    }
    
    public static function get_projects($request) {
        // Get parameters from the REST API request
        $program_id = $request['program_id'];

        // Get the program title
        $program = get_term($program_id, 'funding-agency');
        $program_title = $program ? $program->name : '';
        
        // Perform a custom query to retrieve projects based on $program_id
        $args = array(
            'post_type' => 'project', // Replace 'projects' with your actual post type
            'tax_query' => array(
                array(
                    'taxonomy' => 'funding-agency',
                    'field' => 'term_id',
                    'terms' => $program_id,
                ),
            ),
        );
    
        $query = new \WP_Query($args);

        $projects_html = '';  // Initialize an empty string to store HTML markup
    
        if ($query->have_posts()) {
            $projects_html .= '<h3>' . esc_html($program_title) . '</h3>';

            while ($query->have_posts()) {
                $query->the_post();
    
                // Get the current post data
                $post = $query->post;
    
                ob_start();
                get_template_part('template-parts/projects/activity-banner', '', [
                    'post' => $post,
                ]);
                $projects_html .= ob_get_clean();
            }
            wp_reset_postdata();
        }
    
        $response['html'] = $projects_html;
    
        return rest_ensure_response($response);
    }

    public static function get_project($data) {
        // Get the project ID from the request
        $project_id = $data['id'];
    
        // Check if the project post type exists
        $project_post = get_post($project_id);

       
    
        if (!$project_post || $project_post->post_type !== 'project') {
            // Project not found or not of the correct post type
            $response = array(
                'error' => 'Project not found',
            );
            return rest_ensure_response($response);
        }
    
        // Get meta data for the project
        $meta_data = get_post_meta($project_id);

        $funding_agencies = get_the_terms($project_id, 'funding-agency');
        $funding_programs = get_the_terms($project_id, 'funding-program');

        // Retrieve term name and nickname for funding agencies
        $funding_agency_data = [];
        if (!empty($funding_agencies)) {
            foreach ($funding_agencies as $agency) {
                if (isset($agency->term_id)) {
                    $term_id = $agency->term_id;
                    $term_name = $agency->name;
                    $term_nickname = get_term_meta($term_id, 'term_nickname', true);
                    $funding_agency_data[] = array(
                        'name' => $term_name,
                        'nickname' => $term_nickname ? $term_nickname : null
                    );
                }
            }
        }

        // Retrieve term name and nickname for funding programs
        $funding_program_data = [];
        if (!empty($funding_programs)) {
            foreach ($funding_programs as $program) {
                if (isset($program->term_id)) {
                    $term_id = $program->term_id;
                    $term_name = $program->name;
                    $term_nickname = get_term_meta($term_id, 'term_nickname', true);
                    $funding_program_data[] = array(
                        'name' => $term_name,
                        'nickname' => $term_nickname ? $term_nickname : null
                    );
                }
            }
        }

        // Get the featured image URL
        $featured_image_id = get_post_thumbnail_id($project_id);

        // Initialize variables to store metadata
        $featured_image_url = null;
        $featured_image_title = null;
        $filesize = null;
    
        if ($featured_image_id){
            // Get featured image URL
            $featured_image_url = wp_get_attachment_url($featured_image_id, 'thumbnail');
    
            // Get featured image metadata
            $featured_image_metadata = wp_get_attachment_metadata($featured_image_id);
    
            // Check if metadata exists and contains 'file' property
            if (is_array($featured_image_metadata) && isset($featured_image_metadata['file'])) {
                // Get the title of the image to use as the filename
                $featured_image_title = get_the_title($featured_image_id);
    
                // Format the filesize into a human-readable format
                $filesize = isset($featured_image_metadata['filesize'])
                    ? self::formatBytes($featured_image_metadata['filesize'])
                    : 'N/A';
            }
        }
    
        // Prepare the response
        $response = array(
            'message'         => 'Meta data for Project ID ' . $project_id,
            'meta_data'       => $meta_data,
            'featured_image' => array(
                'ID' => $featured_image_id,
                'url' => $featured_image_url,
                'title' => $featured_image_title ? sanitize_title($featured_image_title) : null,
                'filename' => isset($featured_image_metadata['file']) ? $featured_image_metadata['file'] : null,
                'filesize' => $filesize,
                // Add more fields as needed
            ),
            'post_title'        => $project_post->post_title,
            'post_permalink'    => get_permalink($project_id),
            'post_id'           => $project_id,
            'funding_agency_data'  => $funding_agency_data,
            'funding_program_data'  => $funding_program_data,
        );

        return rest_ensure_response($response);
    }
    

    public static function get_post($request) {
        $post_id = $request['id'];

        
    
        // Check if the post exists
        $post = get_post($post_id);

        
    
        if (empty($post) || is_wp_error($post)) {
            return new \WP_Error('not_found', 'Post not found', array('status' => 404));
        }
    
        // Get featured image ID
        $featured_image_id = get_post_thumbnail_id($post->ID);
    
        // Get post date, time, and status
        $post_date = date('m/d/Y', strtotime(get_the_date('Y-m-d', $post->ID)));
        $post_time = get_the_time('h:i A', $post->ID); // Format as hh:mm am/pm
        $post_status = $post->post_status;
    
        // Initialize variables to store metadata
        $featured_image_url = null;
        $featured_image_title = null;
        $featured_image_caption = null;
        $filesize = null;
    
        // Check if featured image ID is valid
        if ($featured_image_id) {
            // Get featured image URL
            $featured_image_url = wp_get_attachment_url($featured_image_id, 'thumbnail');
    
            // Get featured image metadata
            $featured_image_metadata = wp_get_attachment_metadata($featured_image_id);
    
            // Check if metadata exists and contains 'file' property
            if (is_array($featured_image_metadata) && isset($featured_image_metadata['file'])) {
                // Get the title of the image to use as the filename
                $featured_image_title = get_the_title($featured_image_id);
                $featured_image_caption = get_post_field('post_excerpt', $featured_image_id);
    
                // Format the filesize into a human-readable format
                $filesize = isset($featured_image_metadata['filesize'])
                    ? self::formatBytes($featured_image_metadata['filesize'])
                    : 'N/A';
            }
        }

        $additional_images = get_field('additional_images', $post_id);

        $attachment_images_data = [];

        if($additional_images) {
            foreach ($additional_images as $image_id) {
                $attachment_metadata = wp_get_attachment_metadata($image_id);
    
                $attachment_images_data[] = array(
                    'ID'        => $image_id,
                    'url'       => wp_get_attachment_url($image_id),
                    'title'     => get_the_title($image_id),
                    'caption'   => wp_get_attachment_caption($image_id), // Use wp_get_attachment_caption to get the caption
                    'filename'  => wp_basename(wp_get_attachment_url($image_id)),
                    'filesize'  => isset($attachment_metadata['filesize'])
                        ? self::formatBytes($attachment_metadata['filesize'])
                        : 'N/A',
                    // Add more fields as needed
                );
            }
        }

        // Get post meta data for related staff
        $related_staff = get_field('field_65021ce5287c6', $post_id);

        // Get post meta data for related projects
        $related_projects = get_field('field_65a6fa6acef28', $post_id);
    
        $response = array(
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_date' => $post_date,
            'post_time' => $post_time,
            'post_status' => $post_status,
            'featured_image' => array(
                'ID' => $featured_image_id,
                'url' => $featured_image_url,
                'title' => $featured_image_title ? sanitize_title($featured_image_title) : null,
                'caption' => $featured_image_caption ? $featured_image_caption : null,
                'filename' => isset($featured_image_metadata['file']) ? $featured_image_metadata['file'] : null,
                'filesize' => $filesize,
                // Add more fields as needed
            ),
            'related_staff' => $related_staff,
            'related_projects' => $related_projects,
            'additional_images' => $attachment_images_data,
            // Add more fields as needed
        );
    
        return rest_ensure_response($response);
    }

    public static function get_user($request) {
        $user_id = $request['id'];

        // Check if user ID is provided
        if (empty($user_id)) {
            return new \WP_Error('missing_user_id', 'User ID is required.', array('status' => 400));
        }

        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return new \WP_Error('user_not_found', 'User not found.', array('status' => 404));
        }

        $nickname = get_user_meta($user_id, 'nickname', true);
        $address = get_user_meta($user_id, 'location', true);

        // Prepare the response
        $response = array(
            'nickname' => $nickname,
            'address' => $address,
        );

        return rest_ensure_response($response);
    }

    public static function get_users($request) {
        $staff_users = get_users( array(
            'role__in' => array( 'staff_member', 'staff_member_editor' ),
        ) );

        // Prepare the response
        $response = array(
            'staff_members' => $staff_users,
        );

        return rest_ensure_response($response);
    }
    
    // Function to format file size in a human-readable format
    private static function formatBytes($bytes, $decimals = 2) {
        $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
    
     
}
