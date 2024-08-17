<?php

namespace PSI\Forms\GWiz;

use DateTime;
use PSI\Forms\FormHandler;

class GravityFormsCustomizations {
    
    public function __construct() {
        new GWPostPermalink();
        $this->initHooks();
    }

    private function initHooks() {
        add_filter('gppa_process_template_value', [$this, 'processTemplateValue'], 10, 7);
        add_filter('gppa_live_merge_tag_value', [$this, 'liveMergeTagValue'], 10, 5);
		add_filter('gppa_process_template_value', [$this, 'processTemplateDate'], 10, 8);

    }

	/**
	* Convert entry's "date_created" value from the database format (e.g. ISO 8601) to the datepicker format (e.g. m/d/Y)
	* when populating into a Date field.
	* Used for form 16 (edit article)
	*/
	public function processTemplateDate($value, $field, $template_name, $populate, $object, $object_type, $objects, $template) {
		// Check if $field is an object or an array and retrieve formId and id accordingly
		if ((is_object($field) && property_exists($field, 'formId') && property_exists($field, 'id')) || 
			(is_array($field) && isset($field['formId']) && isset($field['id']))) {
			
			$formId = is_object($field) ? $field->formId : $field['formId'];
			$fieldId = is_object($field) ? $field->id : $field['id'];
	
			if ($formId == 16) {
				if ($fieldId == 13) {
					$date = new DateTime($value);
					$value = $date->format('m/d/Y');
				}
				if ($fieldId == 14) {
					$value = date_format(date_create_from_format('Y-m-d H:i:s', $value), 'h:i A');
				}
			}
		}
	
		return $value;
	}
	

    public function processTemplateValue($template_value, $field, $template_name, $populate, $object, $object_type, $objects) {
		// Check if $field is an object and has the cssClass property
		if ((is_object($field) && property_exists($field, 'cssClass')) || (is_array($field) && isset($field['cssClass']))) {
			$cssClass = is_object($field) ? $field->cssClass : $field['cssClass'];
	
			if (strpos($cssClass, 'gppa-format-acf-date') === false) {
				return $template_value;
			}
	
			$date = DateTime::createFromFormat('Ymd', $template_value);
			return $date ? wp_date('m/d/Y', $date->getTimestamp()) : $template_value;
		}
	
		// If $field is neither an object with cssClass property nor an array with cssClass key, return the template value as is
		return $template_value;
	}

    public function liveMergeTagValue($merge_tag_match_value, $merge_tag, $form, $field_id, $entry_values) {
        $bits = explode(':', str_replace(['{', '}'], '', $merge_tag));
			$modifiers = explode(',', array_pop($bits));
		
			$post_id = rgar($entry_values, $field_id);
			if (!$post_id) {
				return $merge_tag_match_value;
			}
		
			$thumbnail_id = get_post_thumbnail_id($post_id);
			
			$image_data = get_post($thumbnail_id);
			if ($image_data && $thumbnail_id !== 0) {
				foreach ($modifiers as $modifier) {
					switch ($modifier) {
						case 'featured_image':
							$image_src = wp_get_attachment_image_src($thumbnail_id, 'full');
							if ($image_src) {
								return esc_url($image_src[0]);
							}
							break;
							
						case 'featured_image_filename':
							return esc_html($image_data->post_title);
							
						case 'featured_image_id':
							return esc_html($thumbnail_id);
							
						case 'featured_image_name':
							return esc_html($image_data->post_name);
							
						case 'featured_image_excerpt':
							return esc_html($image_data->post_excerpt);
							
						case 'featured_image_mime_type':
							return esc_html($image_data->post_mime_type);
							
						case 'featured_image_guid':
							return esc_url($image_data->guid);
							
						case 'featured_image_filesize':
							$file_path = get_attached_file($thumbnail_id);
							$file_size = filesize($file_path);
							if ($file_size !== false) {
								return esc_html(FormHandler::human_filesize($file_size));
							}
							break;
							
						default:
							// Do nothing if the modifier is not recognized.
							break;
					}
				}
			} 
		
			$additional_images = get_field('additional_images', $post_id);
			
			if (empty($additional_images)) {
				return '';
			}
		
			// Get the image index from the merge tag bits, default to 0
			$image_index = 0;
			if (isset($bits[3]) && is_numeric($bits[3])) {
				$image_index = intval($bits[3]);
			}
		
			if (!isset($additional_images[$image_index])) {
				return '';
			}
		
			$image_id = $additional_images[$image_index];
			$image_data = get_post($image_id);
			if (!$image_data) {
				return $merge_tag_match_value;
			}
		
			foreach ($modifiers as $modifier) {
				switch ($modifier) {
					case 'additional_image':
						$image_src = wp_get_attachment_image_src($image_id, 'full');
						if ($image_src) {
							return esc_url($image_src[0]);
						}
						break;
		
					case 'additional_image_filename':
						return esc_html($image_data->post_title);
		
					case 'additional_image_id':
						return esc_html($image_id);
		
					case 'additional_image_name':
						return esc_html($image_data->post_name);
		
					case 'additional_image_excerpt':
						return esc_html($image_data->post_excerpt);
		
					case 'additional_image_mime_type':
						return esc_html($image_data->post_mime_type);
		
					case 'additional_image_guid':
						return esc_url($image_data->guid);
		
					case 'additional_image_filesize':
						$file_path = get_attached_file($image_id);
						$file_size = filesize($file_path);
						if ($file_size !== false) {
							return esc_html(FormHandler::human_filesize($file_size));
						}
						break;
		
					default:
						break;
				}
			}
		
			if(!$thumbnail_id) {
				return '';
			}
		
			return $merge_tag_match_value;
		
    }
}
