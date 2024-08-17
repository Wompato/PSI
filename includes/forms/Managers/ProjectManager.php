<?php

namespace PSI\Forms\Managers;

use PSI\Utils;
use PSI\Forms\FormHanlder;
use PSI\Forms\GWiz\GPASVS_Enable_Add_New_Option;

class ProjectManager {

    private static $roles = [
        "Psi Lead"                  => 'field_66281ae0732f4',
        "Co-Principal Investigator" => 'field_66281b47732f6',
        "Co-Investigator"           => 'field_656539a3fe4ba',
        "Science PI"                => 'field_664291adf52eb',
        "Collaborator"              => 'field_656541567d94b',
        "Support"                   => 'field_66281b66732f7',
        "Postdoctoral Associate"    => 'field_66424f3422202',
        "Graduate Student"          => 'field_6642500d22203',
    ];
    
    public function __construct() {

        
        add_action('gform_after_submission_10', array($this, 'create_project'), 10, 2);
        add_action('gform_after_submission_8', array($this, 'update_project'), 10, 2);

        //add_filter('gform_column_input_10_14_2', array($this, 'customize_role_type'));
        //add_filter('gform_column_input_8_12_2', array($this, 'customize_role_type'));

        add_filter('gppa_query_limit_10_22', array($this, 'set_query_limit'), 10, 2);

        // Edit Project //
        new GPASVS_Enable_Add_New_Option(array(
            'form_id' => 8,
            'field_id' => 18,
        ));

        new GPASVS_Enable_Add_New_Option(array(
            'form_id' => 8,
            'field_id' => 19,
        ));
        // End Edit Project //

        // Create Project //
        new GPASVS_Enable_Add_New_Option(array(
            'form_id' => 10,
            'field_id' => 18,
        ));

        new GPASVS_Enable_Add_New_Option(array(
            'form_id' => 10,
            'field_id' => 19,
        ));
        // End Create Project //
    }

    public function create_project($entry, $form) {
        $title = rgar($entry, '1');
        $nickname = rgar($entry, '4');
        $funding_instrument = rgar($entry, '12');
        $pte = rgar($entry, '24');
        $non_psi_personel = rgar($entry, '14');
        $psi_lead = rgar($entry, '15');
        $psi_lead_role = rgar($entry, '25');
        $featured_image = json_decode(rgar($entry, '5'));
        $description = rgar($entry, '3');
        $project_number = rgar($entry, '6');
        $agency_award_number = rgar($entry, '7');
        $project_website = rgar($entry, '10');
        $start_date = rgar($entry, '8');
        $end_date = rgar($entry, '9');
        $related_articles = rgar($entry, '22');
        $funding_source = rgar($entry, '18');
        $funding_program = rgar($entry, '19');

        $meta_fields = [
            'field_65652c908b353' => $funding_instrument,
            'field_66281e08a4ce4' => $pte,
            'field_656541b47d94c' => $non_psi_personel,
            'field_66281b08732f5' => $psi_lead_role,
            'field_65652d6d24359' => $nickname,
            'field_65652c058b351' => $project_number,
            'field_65652c7e8b352' => $agency_award_number,
            'field_65652f772435e' => $project_website,
            'field_65652f0a2435c' => $start_date,
            'field_65652f552435d' => $end_date,
            'field_6574c321bde33' => json_decode($related_articles),
        ];

        $taxonomies = array(
            'funding-agency'   => $funding_source,
            'funding-program'  => $funding_program,
        );

        // Create post data
        $post_data = array(
            'post_title'      => $title,
            'post_content'    => $description,
            'post_type'       => 'project',
            'post_status'     => 'publish',
            'post_author'     => get_current_user_id(),
        );

        // Insert the post and get the post ID
        $post_id = wp_insert_post($post_data);

        if(!$post_id) {
            return;
        }

        if(isset($featured_image[0])) {
            $attachment_id = attachment_url_to_postid( $featured_image[0] );
            if ( !$attachment_id ) {
                return;
            }
            set_post_thumbnail( $post_id, $attachment_id );
        }

        $psi_team = json_decode(stripslashes($_POST["other-psi-personnel"]));

        Utils::update_project_users( $post_id , $psi_team);

        update_field('field_65652d6d24359', $nickname, $post_id);
        update_field('field_65652c058b351', $project_number, $post_id);
        update_field('field_65652c7e8b352', $agency_award_number, $post_id);
        update_field('field_65652f0a2435c', $start_date, $post_id);
        update_field('field_65652f552435d', $end_date, $post_id);
        update_field('field_65652f772435e', $project_website, $post_id);
        update_field('field_656541b47d94c', $non_psi_personel, $post_id);
        update_field('field_65652c908b353', $funding_instrument, $post_id);
        update_field('field_66281b08732f5', $psi_lead_role, $post_id);
        update_field('field_66281ae0732f4', $psi_lead, $post_id);
        
        $taxonomies = array(
            'funding-agency'   => $funding_source,
            'funding-program'  => $funding_program,
        );

        // Update taxonomies
        foreach ($taxonomies as $taxonomy => $term_name) {
            if ($term_name) {
                $term = get_term_by('name', $term_name, $taxonomy);
                $term_id = $term ? $term->term_id : 0;

                if (!$term_id) {
                    // Term doesn't exist, let's create it
                    $term_info = wp_insert_term($term_name, $taxonomy);

                    if (!is_wp_error($term_info)) {
                        // Term created successfully, get the term ID
                        $term_id = $term_info['term_id'];

                    } else {
                        // Handle error (e.g., log, notify, etc.)
                        continue;
                    }
                }

                // Set the post terms
                wp_set_post_terms($post_id, [$term_id], $taxonomy, false);
                
            }
        }
    }


    
    

    public function update_project($entry, $form) {
        // Default 
        $post_id = rgar($entry, '22');
        
        $content = str_replace(['“','”'], '', rgar($entry, '3'));

        $title = rgar($entry, '2'); 
        $featured_image = json_decode(rgar($entry, '21'));
        $featured_image_preview = rgpost( 'featured-image-id' );

        // Custom Taxonomies
        $funding_source = rgar($entry, '18');
        $funding_program = rgar($entry, '19');

        // Meta 
        $nickname = rgar($entry, '4');
        $project_number = rgar($entry, '6');
        $agency_award_number = rgar($entry, '7');
        $start_date = rgar($entry, '8');
        $end_date = rgar($entry, '10');
        $project_website = rgar($entry, '11');
        $non_psi_personel = rgar($entry, '12');
        $passthrough_entity = rgar($entry, '31');
        $funding_instrument = rgar($entry, '14');
        
        $psi_lead = rgar($entry, '15');
        $psi_lead_role = rgar($entry, '27');
       
        $psi_team = json_decode(stripslashes($_POST["other-psi-personnel"]));

        Utils::update_project_users( $post_id , $psi_team);

        $post_data = array(
            'ID'           => $post_id,
            'post_content' => $content,
            'post_title'   => $title,
        );

        update_field('field_65652d6d24359', $nickname, $post_id);
        update_field('field_65652c058b351', $project_number, $post_id);
        update_field('field_65652c7e8b352', $agency_award_number, $post_id);
        update_field('field_65652f0a2435c', $start_date, $post_id);
        update_field('field_65652f552435d', $end_date, $post_id);
        update_field('field_65652f772435e', $project_website, $post_id);
        update_field('field_656541b47d94c', $non_psi_personel, $post_id);
        update_field('field_66281e08a4ce4', $passthrough_entity, $post_id);
        update_field('field_65652c908b353', $funding_instrument, $post_id);
        update_field('field_66281b08732f5', $psi_lead_role, $post_id);
        update_field('field_66281ae0732f4', $psi_lead, $post_id);

        if( rgempty( $featured_image ) && $featured_image_preview ) {
            // do nothing
        } elseif ( !rgempty( $featured_image ) && rgempty( $featured_image_preview ) ) {
            $attachment_id = attachment_url_to_postid( $featured_image[0] );
            if ( !$attachment_id ) {
                return;
            }
            set_post_thumbnail( $post_id, $attachment_id );
        } else {
            delete_post_thumbnail( $post_id );
        }

        $taxonomies = array(
            'funding-agency'   => $funding_source,
            'funding-program'  => $funding_program,
        );

        // Update taxonomies
        foreach ($taxonomies as $taxonomy => $term_name) {
            if ($term_name) {
                $term = get_term_by('name', $term_name, $taxonomy);
                $term_id = $term ? $term->term_id : 0;

                if (!$term_id) {
                    // Term doesn't exist, let's create it
                    $term_info = wp_insert_term($term_name, $taxonomy);

                    if (!is_wp_error($term_info)) {
                        // Term created successfully, get the term ID
                        $term_id = $term_info['term_id'];

                    } else {
                        // Handle error (e.g., log, notify, etc.)
                        continue;
                    }
                }

                // Set the post terms
                wp_set_post_terms($post_id, [$term_id], $taxonomy, false);
                
            }
        }

        // Get the first term for 'funding-agency'
        $funding_agency_terms = wp_get_post_terms($post_id, 'funding-agency', array('fields' => 'ids'));
        $funding_agency_term = !empty($funding_agency_terms) ? $funding_agency_terms[0] : null;

        // Get the first term for 'funding-program'
        $funding_program_terms = wp_get_post_terms($post_id, 'funding-program', array('fields' => 'ids'));
        $funding_program_term = !empty($funding_program_terms) ? $funding_program_terms[0] : null;

        // Funding Agency/Source related programs.

        // Get existing related programs
        $existing_programs = get_field('related_programs', 'funding-agency_' . $funding_agency_term);

        // Ensure $existing_programs is an array
        $existing_programs = is_array($existing_programs) ? $existing_programs : array();

        // Add the new program to the array if it doesn't already exist
        if (!in_array($funding_program_term, $existing_programs)) {
            $existing_programs[] = $funding_program_term;

            // Update the field with the modified array
            update_field('related_programs', $existing_programs, 'funding-agency_' . $funding_agency_term);
        }

        // Similarly, for related agencies
        $existing_agencies = get_field('related_agencies', 'funding-program_' . $funding_program_term);

        // Ensure $existing_agencies is an array
        $existing_agencies = is_array($existing_agencies) ? $existing_agencies : array();

        if (!in_array($funding_agency_term, $existing_agencies)) {
            $existing_agencies[] = $funding_agency_term;

            update_field('related_agencies', $existing_agencies, 'funding-program_' . $funding_program_term);
        }

        // Update the post with the new content
        wp_update_post($post_data);
    }

    // Shared method for customizing role type in forms
    public function customize_role_type() {
        $role_type = array(
            'type'    => 'select',
            'choices' => array(' ', 'Principal Investigator', 'Science PI', 'Co-Principal Investigator', 'Co-Investigator', 'Collaborator', 'Postdoctoral Associate', 'Consultant', 'Graduate Student', 'Support'),
            'placeholder' => '',
        );
        return $role_type;
    }

    // Method to set query limit for form fields
    public function set_query_limit($query_limit, $object_type) {
        // Update "1000" to the maximum number of results that should be returned for the query populating this field
        return 1000;
    }
}