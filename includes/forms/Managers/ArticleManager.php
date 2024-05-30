<?php

namespace PSI\Forms\Managers;

use PSI\Forms\FormHandler;

class ArticleManager {
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

    public function create_article($entry, $form) {
        $title = rgar( $entry, '37' ); 
        $post_id = get_page_by_title( $title, OBJECT, 'post' );

        $image_captions = array();
        $additional_images = array();

        for ($i = 0; isset($_POST['captionInput' . $i]); $i++) {
            $image_captions[] = sanitize_text_field($_POST['captionInput' . $i]);
        } 

        // FIND POST ID TO-DO
        $image_caption_data = array(
            isset($entry["gpml_ids_24"][0]) ? $entry["gpml_ids_24"][0] : false => $image_captions[0],
            isset($entry["gpml_ids_26"][0]) ? $entry["gpml_ids_26"][0] : false => $image_captions[1],
            isset($entry["gpml_ids_27"][0]) ? $entry["gpml_ids_27"][0] : false => $image_captions[2],
            isset($entry["gpml_ids_28"][0]) ? $entry["gpml_ids_28"][0] : false => $image_captions[3],
            isset($entry["gpml_ids_29"][0]) ? $entry["gpml_ids_29"][0] : false => $image_captions[4],
            isset($entry["gpml_ids_30"][0]) ? $entry["gpml_ids_30"][0] : false => $image_captions[5], 
            isset($entry["gpml_ids_31"][0]) ? $entry["gpml_ids_31"][0] : false => $image_captions[6],
        );
        $related_staff = rgar($entry, '23');
        $related_projects = rgar($entry, '32');  

        foreach($image_caption_data as $id => $caption) {
            if(!$id){
                continue;
            }
            if ( key($image_caption_data) !== $id ) {
                $additional_images[] = $id;
            } else {
                // Featured Image
                set_post_thumbnail($post_id, $id);
            }
            wp_update_post(array(
                'ID'            => $id,
                'post_excerpt'  => $caption,
            ));
        }

        update_field('additional_images', $additional_images, $post_id);

        if (!empty($related_staff)) {
            $unserialized_related_staff = json_decode($related_staff);
            if (is_array($unserialized_related_staff)) {
                update_field('field_65021ce5287c6', $unserialized_related_staff, $post_id);
            }
        }

        if (!empty($related_projects)) {
            $unserialized_related_projects = json_decode($related_projects);
            if (is_array($unserialized_related_projects)) {
                update_field('field_65a6fa6acef28', $unserialized_related_projects, $post_id);
            }
        }

    }

    public function update_article($entry, $form) {
        
        $post_id = rgar($entry, '15');
        $title = rgar($entry, '3');
        $content = rgar($entry, '4');
        $excerpt = rgar($entry, '5');
        $tags = rgar($entry, '6');
        
        $related_staff = rgar($entry, '10');
        $related_projects = rgar($entry, '16');
        $date = rgar($entry, '13');
        $time = rgar($entry, '14');

        $featured_image = json_decode(rgar($entry, '12'));
        $featured_image_caption = rgpost( 'caption_0' );
        $featured_image_preview = rgpost( 'featured-image-id' );
        $featured_image_preview_caption = rgpost( 'caption_field_16_28' );

        $additional_images = [];

        if( rgempty( $featured_image ) && $featured_image_preview ) {
            wp_update_post(array(
                'ID'           => $featured_image_preview,
                'post_excerpt' => $featured_image_preview_caption,
                'post_parent'  => $post_id,
            ));
        } elseif ( !rgempty( $featured_image ) && rgempty( $featured_image_preview ) ) {
            $attachment_id = attachment_url_to_postid( $featured_image[0] );
            if ( $attachment_id ) {
                set_post_thumbnail( $post_id, $attachment_id );
                wp_update_post(array(
                    'ID'           => $attachment_id,
                    'post_excerpt' => $featured_image_caption,
                    'post_parent'  => $post_id,
                ));
            }
        } else {
            delete_post_thumbnail( $post_id );
        }

        // Process additional images
        for ($i = 1; $i <= 6; $i++) {
            $additional_image = json_decode(rgar($entry, (string)(16 + $i))); // Adjust the index as necessary
            $additional_image_caption = rgpost('caption_' . $i);
            $additional_image_preview = rgpost('additional-image-id_' . $i);
            $additional_image_preview_caption = rgpost('caption_field_16_' . (29 + $i)); // Adjust the index as necessary

            if (rgempty($additional_image) && $additional_image_preview) {
                wp_update_post(array(
                    'ID' => $additional_image_preview,
                    'post_excerpt' => $additional_image_preview_caption,
                    'post_parent' => $post_id,
                ));
                $additional_images[] = $additional_image_preview; // Add to additional images array
            } elseif (!rgempty($additional_image) && rgempty($additional_image_preview)) {
                $attachment_id = attachment_url_to_postid($additional_image[0]);
                if ($attachment_id) {
                    wp_update_post(array(
                        'ID' => $attachment_id,
                        'post_excerpt' => $additional_image_caption,
                        'post_parent' => $post_id,
                    ));
                    $additional_images[] = $attachment_id; // Add to additional images array
                }
            }
        }

        // Update the ACF gallery field 'additional_images'
        update_field('field_65ba89acc66e1', $additional_images, $post_id);

        $category_name = rgar($entry, '23');
        $category = get_term_by( 'name', $category_name, 'category' );
        $category_id = $category->term_id;
        
        // Get the date, time, and status fields
        $date_time_fields = FormHandler::get_post_date_time_fields($date, $time);
        
        $post_data = array(
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
        ) + $date_time_fields;

        // Update the post
        wp_update_post($post_data);
    
        // Update post tags and categories
        wp_set_post_tags($post_id, $tags);
        wp_set_post_categories($post_id, array($category_id));

        update_field('related_staff', json_decode($related_staff), $post_id);
        update_field('related_projects', json_decode($related_projects), $post_id); 
                 
    }
}