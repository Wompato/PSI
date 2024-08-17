<?php get_header(); 

use \PSI\Utils;

function format_non_psi_personnel($name, $role, $institution, $index, $total) {
  if (empty($name)) {
      return '';
  }

  $formatted = $name;

  $details = array_filter([$role, $institution]);
  if (!empty($details)) {
      $formatted .= ' (' . implode(', ', $details) . ')';
  }

  if ($index < $total - 1) {
      $formatted .= ', ';
  }

  return $formatted;
}


$content = wpautop(get_the_content());
$count = str_word_count($content);

$title = get_the_title();
$post_id = get_the_ID();

$psi_lead_data = get_field('psi_lead');
$psi_lead_role = get_field('psi_lead_role');

$psi_lead_id = $psi_lead_name = '';

/* For some historical reasons some psi lead data will come back as either an array or an object (might need to manually update all WP users) */
if ($psi_lead_data) {
  $psi_lead = is_array($psi_lead_data) ? $psi_lead_data[0] : $psi_lead_data;
  $psi_lead_id = $psi_lead->ID ?? '';
  $psi_lead_name = $psi_lead->display_name ?? '';
}

$pi_slug = get_field('user_slug', 'user_' . $psi_lead_id);

$psi_lead_image_data = get_field('profile_pictures', 'user_' . $psi_lead_id);
$default_image = get_field('default_user_picture', 'option');

$profile_image_url = $default_image['url'] ?? '';
$profile_image_alt = $default_image['alt'] ?? '';

if ($psi_lead_image_data && $psi_lead_image_data['primary_picture'] ?? false) {
    $profile_image_url = $psi_lead_image_data['primary_picture']["url"] ?? $profile_image_url;
    $profile_image_alt = $psi_lead_image_data['primary_picture']["alt"] ?? $profile_image_alt;
}

$psi_team = Utils::get_project_users($post_id);

$non_psi_personel_data = get_field('non-psi_personnel');
$science_pi = $science_pi_institute = $pi = $pi_institute = $non_psi_personel = '';

if ($unserialized_data = unserialize($non_psi_personel_data)) {
  foreach ($unserialized_data as $i => $personel) {
      $name = $personel['Name'] ?? '';
      $role = $personel['Role'] ?? '';
      $institution = $personel['Institution'] ?? '';

      if ($role === "Science PI") {
          $science_pi = $name;
          $science_pi_institute = $institution;
          continue;
      }

      if ($role === "Principal Investigator") {
          $pi = $name;
          $pi_institute = $institution;
          continue;
      }

      $non_psi_personel .= format_non_psi_personnel($name, $role, $institution, $i, count($unserialized_data));
  }
}

// Get Program (category) for this project
$programs = get_the_terms($post_id, 'funding-program');

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

$funding_sources = get_the_terms($post_id, 'funding-agency');

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

$funding_instrument = get_field('funding_instrument');

$pte = get_field('passthrough_entity');

$nickname = get_field('nickname');
$start_date = get_field('start_date');
$end_date = get_field('end_date');
$project_num = get_field('project_number');
$award_num = get_field('agency_award_number');
$project_website = get_field('project_website');

$related_posts = get_field('related_articles');

$current_user = wp_get_current_user();

?>

<div id="primary" class="content-area" data-project="<?php echo $post_id; ?>">
  <main id="main" class="site-main">
    <?php
    while (have_posts()) :
      the_post(); 
      ?>
      <section class="project-banner">
        <div class="pi-container">
          <a href="<?php echo home_url('/staff/profile/' . $pi_slug); ?>">
            <img src="<?php echo $profile_image_url;?>" alt="<?php echo $profile_image_alt;?>">
            <p><?php echo $psi_lead_name; ?></p>
            <span>
              <?php echo isset($psi_lead_role) ? $psi_lead_role : 'Principal Investigator'; ?>
            </span>
          </a>
        </div>
        <div class="project-info-container">
          <div>
            <div class="project-title-container">
              <h1>
                <?php echo $title;?>
                <?php echo $nickname ? "($nickname)" : ''; ?>

                <?php if($project_website){ ?>
                  <a target="_blank" class ="project-website" href="<?php echo $project_website ? $project_website : '#'; ?>" data-tooltip="Link to project's website">
                    <i style="font-size:1em;" class="wpmi__icon wpmi__position-before wpmi__align-middle wpmi__size-1 fa fa-external-link "></i>
                  </a>
                <?php } ?>  
              </h1>
            </div>
          
            <?php 
            if (is_user_logged_in()) {
                // Check if the user has administrator or staff member editor role
                if (current_user_can('activate_plugins') || in_array('staff_member_editor', (array) $current_user->roles) || $current_user->ID === $psi_lead_id) {
                    $base_url = get_bloginfo('url');
                    $edit_project_url = trailingslashit($base_url) . 'edit-project?project-name=' . get_the_ID();
                    ?>
                    <a class="edit-project" href="<?php echo $edit_project_url;?>">Edit Project</a>
                <?php } 
            } 
            ?>
          </div>
          
          
          <div class="project-tax-container">
            <h3><?php echo isset($funding_source) ? $funding_source . ' ' : ''; ?><?php echo isset($program) ? $program : '' ?></h3>
            <div class="pte-container">
            <?php if ($funding_instrument === 'Subaward' || $funding_instrument === 'Subcontract' && $pte) { ?>
              <p>
                <?php echo $funding_instrument; ?> to PSI from <?php echo $pte ? $pte : ($pi_institute ? $pi_institute : $science_pi_institute); ?>
              </p>
            <?php } ?>
                <?php if ($pi || $science_pi) { ?>
                    <?php if ($pi) { ?>
                        <p>PI: <?php echo isset($pi) ? $pi : ''; ?><?php echo $pi_institute ? ' (' . $pi_institute . ')' : ''; ?></p>
                    <?php } ?>
                    <?php if ($science_pi) { ?>
                        <p>Science PI: <?php echo isset($science_pi) ? $science_pi : ''; ?><?php echo $science_pi_institute ? ' (' . $science_pi_institute . ')' : ''; ?></p>
                    <?php } ?>
                <?php } ?>

            </div>
            <div class="project-meta-container">
              <div class="project-meta">
                <div>Start Date: <?php echo $start_date; ?></div>
                <div>Project #: <?php echo $project_num; ?></div>
              </div>
              <div class="project-meta">
                <div>End Date: <?php echo $end_date; ?></div>
                <div>Award #: <?php echo $award_num; ?></div>
              </div>
            </div>
          </div>
          
        </div>
      </section>
      
        <section>
        <?php if($psi_team) { ?>
          <h3>PSI Personnel</h3>
          <div class="coi-collab-track related-staff-carousel-large">
            <?php
            foreach($psi_team as $user){
              get_template_part('template-parts/related-staff-member', '', array(
                'staff-member' => $user
              ));
            }
            ?>
          </div>
        <?php } ?>
        <?php if($non_psi_personel) { ?>
          <p><strong>Non PSI Personnel:</strong> <?php echo $non_psi_personel;?></p>
        <?php } ?>
            
        </section>

      <section>
        <?php if($content) { ?>
          <?php echo '<h5>Project Description</h5>'; ?>
            <div class="project-description">
              <div class="project-description-text <?php echo $count > 300 ? 'show' : 'no-show'; ?>">
                 <?php echo $content; ?>
              </div>
              <?php if($count > 300) { ?>
                <a class="project-description-show-more" href="#">Show More</a>
              <?php } ?>
            </div>
        <?php } ?>
            
      </section>
      
      <?php 
      
      if($related_posts) { 
        usort($related_posts, function ($a, $b) {
          $date_a = strtotime($a->post_date);
          $date_b = strtotime($b->post_date);

          return ($date_a < $date_b) ? 1 : -1;
        });

        $initial_posts = array_slice($related_posts, 0, 6);

        if(empty($initial_posts)) {
          // Return some error which says that there were no initial posts found  
          return false;
        }

        $has_more_posts = count($related_posts) > 6;
        
      ?>
        <section class="related-posts">
            <h2 class="section-headline">RELATED COVER STORIES & PRESS RELEASES</h2>
            <div id="related-posts-grid">
              <?php 
                foreach ($initial_posts as $post) {
                  get_template_part('template-parts/related-post', '', array(
                    'post' => $post,
                  ));
                }
              ?>
            </div>
            <div class="loader-container"></div>
            
            <?php if($has_more_posts) { ?>
              <div id="load-more-related-posts">Load More<i class="fa-solid fa-angle-right"></i></div>
            <?php } ?>
        </section>
      <?php }
      
      endwhile; ?>
  </main>
</div>


<?php get_footer(); ?>
