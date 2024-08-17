<?php

use \PSI\Utils;

$post = $args['post'];

if(isset($args['page'])) {
    $page = $args['page'];
} else {
    $page = '';
}

$psi_lead_data = get_field('psi_lead', $post->ID);
$psi_lead_role = get_field('psi_lead_role', $post->ID);

if(is_array($psi_lead_data)) {
    $psi_lead_id   = $psi_lead_data[0]->ID;
    $psi_lead_name = $psi_lead_data[0]->display_name;
  } else {
    $psi_lead_id   = $psi_lead_data->ID;
    $psi_lead_name = $psi_lead_data->display_name;
  }

$pi_images = get_field('profile_pictures', 'user_' . $psi_lead_id);
$pi_slug = get_field('user_slug', 'user_' . $psi_lead_id);

$nickname = get_field('nickname', $post->ID);
$title = $post->post_title;
$funding_instrument = get_field('funding_instrument', $post->ID);
$is_sub_contrt_awrd = false;


/* $co_primary_investigator_data = get_field('co_principal_investigator') ?: [];
$co_investigators_data = get_field('co-investigators') ?: [];
$collaborators_data = get_field('collaborators') ?: [];
$supporters_data = get_field('support') ?: [];
$science_pi_data = get_field('science_pi') ?: [];
$post_doc_data = get_field('postdoctoral_associate') ?: [];
$graduate_data = get_field('graduate_student') ?: [];

$psi_team = array_merge(
  $co_primary_investigator_data,
  $co_investigators_data,
  $collaborators_data,
  $supporters_data,
  $science_pi_data,
  $post_doc_data,
  $graduate_data
); */

$psi_team = Utils::get_project_users($post->ID);

$non_psi_personnel = get_field('non-psi_personnel', $post->ID);
// Unserialize the string to get an array
$personnel_array = unserialize($non_psi_personnel);

$science_pi = '';
$science_pi_institute = '';
$pi = ''; 
$pi_institute = '';

// Check if the unserialization was successful
if ($personnel_array !== false) {
    foreach ($personnel_array as $person) {
        // Check if the role is "Principal Investigator"
        if ($person['Role'] === "Principal Investigator") {
            // Extract the name of the person with the role of Principal Investigator
            $pi = $person['Name'];
            $pi_institute = $person['Institution'];
            // Break out of the loop since we found the Principal Investigator
            continue;
        }

        // Check if the role is "Science PI"
        if ($person['Role'] === "Science PI") {
            // Extract the name of the person with the role of Science PI
            $science_pi = $person['Name'];
            $science_pi_institute = $person['Institution'];
            // Skip this person and move to the next iteration
            continue;
        }

        // Add all other personnel to psi team
        $psi_team[] = $person;
    }
} 


if($funding_instrument == 'Subcontract' || $funding_instrument == 'Subaward') {
    $is_sub_contrt_awrd = true;
}

// Get Program (category) for this project
$programs = get_the_terms(get_the_ID(), 'funding-program');

// Check if there are any programs assigned
if ($programs) {
    // Get the first program
    $first_program = $programs[0];
    
    // Get the term ID
    $term_id = $first_program->term_id;
    
    // Get the term meta value for 'term_nickname'
    $program_nickname = get_term_meta($term_id, 'term_nickname', true);
    
    // If term_nickname exists, use it. Otherwise, use the term name
    $program = $program_nickname ? $program_nickname : $first_program->name;
}

$funding_sources = get_the_terms(get_the_ID(), 'funding-agency');
// Check if there are any funding sources assigned
if ($funding_sources) {
    // Get the first funding source
    $first_funding_source = $funding_sources[0];
    
    // Get the term ID
    $term_id = $first_funding_source->term_id;
    
    // Get the term meta value for 'term_nickname'
    $funding_source_nickname = get_term_meta($term_id, 'term_nickname', true);
    
    // If term_nickname exists, use it. Otherwise, use the term name
    $funding_source = $funding_source_nickname ? $funding_source_nickname : $first_funding_source->name;
}

$pass_through_entity = get_field('pass_through_entity');

$pte = get_field('passthrough_entity');

if($pass_through_entity){
  $decodedData = unserialize($pass_through_entity);

  if (is_array($decodedData) && !empty($decodedData)) {
      $passThroughEntities = array_column($decodedData, 'Pass Through Entity');
  
      // If there are multiple entities, join them with commas
      $pass_through_entity = implode(', ', $passThroughEntities);
      
      // Extract "PTE Primary Investigator" values
      $primaryInvestigators = array_column($decodedData, 'PTE Principal Investigator');
      $primary_investigators = implode(', ', $primaryInvestigators);

      
  } 
}

/* 
if($co_investigators_data && !$collaborators_data){
  $all_coi_collabs = $co_investigators_data;
} elseif($collaborators_data && !$co_investigators_data) {
  $all_coi_collabs = $collaborators_data;
} elseif($co_investigators_data && $collaborators_data) {
  $all_coi_collabs = [...$co_investigators_data, ...$collaborators_data];
} */



if($pi_images){
    $pi_img = $pi_images["icon_picture"] ? $pi_images["icon_picture"] : null;
        
    if(!empty($pi_img)){
        $pi_img_url = $pi_img['url'];
        $pi_img_alt = $pi_img['alt'];
    } else {
        $pi_img = $pi_images["primary_picture"] ? $pi_images["primary_picture"] : null;
        if(!empty($pi_img)){
            $pi_img_url = $pi_img['url'];
            $pi_img_alt = $pi_img['alt'];
        }
    }

    if(empty($pi_img)) {
        $default_img = get_field('default_user_picture', 'option');
        $pi_img_url = $default_img['url'];
        $pi_img_alt = $default_img['alt'];
    }  
    
} else {
    
        $default_img = get_field('default_user_picture', 'option');
        $pi_img_url = $default_img['url'];
        $pi_img_alt = $default_img['alt'];

}



// Get the featured image
$featured_image = get_the_post_thumbnail_url($post->ID, 'full');
if(!$featured_image) {
    $default_image = get_field('default_post_image', 'option');
    $featured_image = $default_image['url'];
}
// Get the project permalink
$project_permalink = get_permalink();

$user_slug = get_field('user_slug', 'user_' . $psi_lead_id);
$user_permalink = home_url() . '/staff/profile/' . $user_slug;
?>
<div class="activity-banner <?php echo $page ? 'page' . $page : '';?> <?php echo str_replace(" ", "-", strtolower($psi_lead_role)); ?>">
    <div class="project-image-container">
        <img class="project-image" src="<?php echo $featured_image; ?>" alt="">
    </div>
    <div class="activity-banner__content">
        <h3>
            <a href="<?php echo $project_permalink; ?>">
                <?php
                $limit = 150;
                $limitedTitle = substr($title, 0, $limit);

                // Display the limited title
                echo $limitedTitle;

                // If the title is longer than 200 characters, display ellipsis
                if (strlen($title) > $limit) {
                    echo '...';
                }
                if($nickname) { ?>
                    <span>(<?php echo $nickname; ?>)</span>
                <?php } ?>
            </a>     
        </h3>
        <div>
            <h4>
            <?php echo isset($funding_source) ? $funding_source : ''; ?> <?php echo isset($program) ? $program : ''; ?>
            </h4>
        </div>
        <a href="<?php echo $project_permalink; ?>">Learn More</a>
    </div>
    <div class="activity-banner__primary-investigator">
        <h4>
            <?php echo $psi_lead_role ? $psi_lead_role : 'Principal Investigator'; ?>
        </h4>
        <a href="<?php echo home_url('/staff/profile/' . $pi_slug); ?>">
            <img src="<?php echo $pi_img_url; ?>" alt="<?php echo $pi_img_alt;?>">
            <div><?php echo $psi_lead_name; ?></div>  
        </a>
        <?php if($psi_lead_role === 'Institutional PI') { ?>
            <div class="seperator"></div>
        <?php } ?>
        <?php if($psi_lead_role === 'Institutional PI') { ?>
            <?php if ($pi || $science_pi) { ?>
                <?php if ($pi) { ?>
                    <p>PI: <?php echo $pi; ?></p>
                <?php } ?>
                <?php if ($science_pi) { ?>
                    <p>Science PI: <?php echo $science_pi; ?></p>
                <?php } ?>
            <?php } ?>

            <?php if($funding_instrument === 'Subaward' || $funding_instrument === 'Subcontract') { ?>
                <p><?php echo $pte ? $pte : ''; ?></p>
            <?php } else { ?>
                <p><?php echo $pi_institute ? $pi_institute : $science_pi_institute; ?></p>
            <?php } ?>
            
            
        <?php } ?>
    </div>
    <?php if($psi_team) { ?>
    <div class="project-team">
        <h4>Project Team</h4>
        <div class="project-team-member-container">
        <?php
            foreach ($psi_team as $user) { 
                if(is_object($user)) {
                    get_template_part('template-parts/related-staff-member', '', array(
                        'staff-member' => $user
                    ));
                } else { ?>
                    <div class="staff-member">
                        <div class="pte-member">
                            <p><?php echo $user['Name']; ?></p>
                            <p><?php echo $user['Institution']; ?></p>
                        </div>
                    </div>
                <?php } 
            }
        ?>
        </div>
    </div>
    <?php } ?>
</div>

