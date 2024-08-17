<?php

$staff_member = $args['staff-member'];

// Normalize input: Convert array to object if needed
if (is_array($staff_member)) {
    $staff_member = (object) $staff_member;
}

// Initialize $staff_member_data as an object
$staff_member_data = new stdClass();

// Check if $staff_member already has a 'data' property and it's an object
if (isset($staff_member->data) && is_object($staff_member->data)) {
    $staff_member_data = $staff_member->data; // Use it directly if it exists
} else {
    // Set up the nested data structure if 'data' property doesn't exist
    if (isset($staff_member->ID) && isset($staff_member->display_name)) {
        $staff_member_data->ID = $staff_member->ID;
        $staff_member_data->display_name = $staff_member->display_name;
    }
}

$user_slug = get_field('user_slug', 'user_' . $staff_member_data->ID);

$profile_images = get_field('profile_pictures', 'user_' . $staff_member_data->ID);
$profile_img_url = '';
$profile_img_alt = '';

if ($profile_images) {
    $profile_img = $profile_images['icon_picture'] ? $profile_images['icon_picture'] : null;

    if (!empty($profile_img)) {
        $profile_img_url = $profile_img['url'];
        $profile_img_alt = $profile_img['alt'];
    } else {
        $profile_img = $profile_images["primary_picture"] ? $profile_images["primary_picture"] : null;

        if (!empty($profile_img)) {
            $profile_img_url = $profile_img['url'];
            $profile_img_alt = $profile_img['alt'];
        }
    }
}


if (empty($profile_img_url)) {
    $default_image = get_field('default_user_picture', 'option');
    $profile_img_url = $default_image['url'];
    $profile_img_alt = $default_image['alt'];
}
?>

<div class="staff-member">
    <a href="<?php echo home_url('/staff/profile/' . $user_slug); ?>">
        <img src="<?php echo $profile_img_url; ?>" alt="<?php echo $profile_img_alt; ?>">
        <span><?php echo $staff_member_data->display_name; ?></span>
    </a>
</div>

