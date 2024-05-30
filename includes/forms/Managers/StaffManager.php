<?php

namespace PSI\Forms\Managers;

use PSI\Forms\FormHandler;

class StaffManager {
    public function __construct() {

        // Article Forms
        add_action('gform_after_submission_13', array($this, 'create_article'), 10, 2);
        add_action('gform_after_submission_16', array($this, 'update_article'), 10, 2);
        // for the create project form, this allows for 1000 posts to be queries by the related CS/PR section.
        add_filter( 'gppa_query_limit_16_1', function( $query_limit, $object_type ) {
            // Update "10000" to the maximum number of results that should be returned for the query populating this field.
            return 10000;
        }, 10, 2 );
    }

        /**
     * Create staff page after user registration via Gravity Forms.
     * 
     * User Meta Fields created by ACF need to be given some value on creation otherwise sometimes there will be no default value set and the row will not exist in the database. 
     * Without a row in the Database the Update User Form cannot display the fields required to have users edit their profiles so we are setting defaults to all fields here.
     *
     * @param array $entry The form entry data.
     * @param array $form The form data.
     */
    public function create_staff_page($entry, $form) {
         
        $user_email = rgar($entry, '6'); 
        // Get the user ID based on the user email
        $user = get_user_by('email', $user_email);

        if(!$user){
            error_log('No User Found');
            return;
        }

        $user_id = $user->ID;

        $position = rgar($entry, '10');
        $status = rgar($entry, '32');
        $state = rgar($entry, '7.4');
        $country = rgar($entry, '7.6');
        $display_in_directory = rgar($entry, '31');

        $prefix = rgar($entry, '4.2');
        $f_name = rgar($entry, '4.3');
        $m_name = rgar($entry, '4.4');
        $l_name = rgar($entry, '4.6');
        $full_name = implode(' ', array_filter([$prefix, $f_name, $m_name, $l_name]));
        // Display name without prefix
        $display_name = trim(implode(' ', array_filter([$f_name, $m_name, $l_name])));

        $user_slug = trim(strtolower($f_name . '-' . $l_name));

        $address = $state . ' '. $country;
    
        $primary_picture = json_decode(rgar($entry, '30'));
      
        $primary_picture_array = is_array($primary_picture) ? $primary_picture : [''];
        $primary_picture_attatchment_id = isset($primary_picture_array[0]) ? attachment_url_to_postid($primary_picture_array[0]) : 0;

        if ($user) {
            wp_update_user(array(
                'ID'                   => $user_id,
                'first_name'           => $f_name,
                'last_name'            => $l_name,
                'display_name'         => $display_name,
            ));
    
            update_user_meta($user_id, 'nickname', $full_name);
            update_field('prefix', $prefix, 'user_' . $user_id);
            update_field('middle_name', $m_name, 'user_' . $user_id);

            $user_meta = array(
                'field_652f53162105b' => 0, // Related Posts
                'field_652f531645d90' => 0, // Related Projects
                'field_6531839aff808' => '', // Personal Page
                'field_652f531624bdc' => $status, // Status
                'field_652f53163ade2' => 0, // CV
                'field_6579fb74c2164' => 0, // Publications File 
                'field_653fddd65f0b3' => $display_in_directory, // Display In Directory
                'field_6594dae77bc2e' => '', // Targets of Interests
                'field_6594dafa7bc2f' => '', // Disciplines and Techniques
                'field_6594db127bc30' => '', // Missions
                'field_6594db247bc31' => '', // Mission Roles
                'field_6594db2c7bc32' => '', // Instruments
                'field_6594db387bc33' => '', // Facilities
                'field_65ca58de9e649' => '', // Twitter
                'field_65ca58f69e64a' => '', // LinkedIn
                'field_65ca59099e64b' => '', // Facebook
                'field_65ca59179e64c' => '', // YouTube
                'field_65ca59209e64d' => '', // Instagram
                'field_65d905aa86ece' => '', // GitHub
                'field_65d932851c5fa' => '', // Orchid ID
                'field_65d932951c5fb' => '', // Google Scholar
                'field_652f53162879f' => $position, // Position
                'field_652f5316338be' => $address, // Location
                'field_656d4ea2c5454' => $user_slug, // Slug in the URL identifiying the user and their staff page
            );

            // Field Group ID for professional interests
            $professional_interests_group_subfields = array(
                'field_6531887586121' => '', // Professional Interests Content
                'field_6553c9ab418c0' => 0, // Professional Interests Images
                'field_653188ad86124' => '' // Professional Interests Image Captions
            );

            $professional_history_group_subfields = array(
                'field_65318b433fabe' => '', // Professional History Content
                'field_6553c9eac423f' => 0, // Professional History Images
                'field_65318b433fac1' => '', // Professional History Image Captions
            );

            $honors_and_awards_group_subfields = array(
                'field_65318bc05ad18' => '', // Honors and Awards Content
                'field_6553ca6855149' => 0, // Honors and Awards Images
                'field_65318bc05ad1b' => '', // Honors and Awards Image Captions
            );

            // Field group ID for the primary pictures
            $profile_pictures_group_subfields = array(
                'field_65821a416331d' => $primary_picture_attatchment_id, // Primary Image
                'field_65821a686331e' => 0, // Image for Professional History Page
                'field_65821aa06331f' => 0, // Image for Honors and Awards Page
                'field_65821aaf63320' => 0  // Image for Personal Icon
            );

            // profile pictures
            update_field('field_658219e86331c', $profile_pictures_group_subfields, 'user_' . $user->data->ID);
            // professional interests
            update_field('field_652f531642964', $professional_interests_group_subfields, 'user_' . $user->data->ID);
            // professional history
            update_field('field_65318b433fabd', $professional_history_group_subfields, 'user_' . $user->data->ID);
            // honors and awards
            update_field('field_65318bc05ad17', $honors_and_awards_group_subfields, 'user_' . $user->data->ID);

            foreach ($user_meta as $field => $value) {
                update_field($field, $value, 'user_' . $user->data->ID);
            }    

            
        }
    }

    /**
     * Update staff page after form submission via Gravity Forms. These fields are not available to be updated by a staff member
     *
     * @param array $entry The form entry data.
     * @param array $form The form data.
     */
    public function update_staff_page($entry, $form) {
        $user_id = rgar($entry, '8');

        if(!$user_id) {
            error_log('No user ID found');
        }

        $email = rgar($entry, '3');
        $position = rgar($entry, '4');
        $address = rgar($entry, '7');
        $status = rgar($entry, '10');
        $display_in_directory = rgar($entry, '11');

        $prefix = rgar($entry, '12.2');
        $f_name = rgar($entry, '12.3');
        $m_name = rgar($entry, '12.4');
        $l_name = rgar($entry, '12.6');
        $full_name = implode(' ', array_filter([$prefix, $f_name, $m_name, $l_name]));
        // Display name without prefix
        $display_name = trim(implode(' ', array_filter([$f_name, $m_name, $l_name])));

        wp_update_user(array(
            'ID'                   => $user_id,
            'user_email'           => $email,
            'first_name'           => $f_name,
            'last_name'            => $l_name,
            'display_name'         => $display_name,
        ));

        update_user_meta($user_id, 'nickname', $full_name);
        update_field('prefix', $prefix, 'user_' . $user_id);
        update_field('middle_name', $m_name, 'user_' . $user_id);
        update_field('position', $position, 'user_' . $user_id);
        update_field('location', $address, 'user_' . $user_id);
        update_field('status', $status, 'user_' . $user_id);
        update_field('display_in_directory', $display_in_directory, 'user_' . $user_id);
    }

}