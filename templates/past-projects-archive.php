<?php
/*
Template Name: Past Projects Archive
*/

get_header();
?>

<div class="active-projects__link-container">
    <i style="font-size:1em; margin-right: 0.2em;" class="wpmi__icon wpmi__label-0 wpmi__position-before wpmi__align-middle wpmi__size-1 fa fa-external-link "></i>
    <a href="<?php echo home_url('/active-projects'); ?>">Go To Active Projects</a>
</div>

<?php
// Get all funding agency terms
$funding_agencies = get_terms(array(
    'taxonomy' => 'funding-agency',
    'hide_empty' => true, // Show even if they don't have projects associated
));

// Get the default program ID
$nasa = get_term_by('slug', 'nasa', 'funding-agency');

// Create an array to store the ordered terms
$ordered_agencies = array();

// Create a single query to get all related programs and their posts with end_date in the past
$related_programs_query = new WP_Query(array(
    'post_type' => 'project',
    'posts_per_page' => -1,
    'tax_query' => array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'funding-agency',
            'field' => 'term_id',
            'terms' => wp_list_pluck($funding_agencies, 'term_id'),
        ),
        array(
            'taxonomy' => 'funding-program',
            'field' => 'term_id',
            'terms' => wp_list_pluck($funding_agencies, 'term_id'),
        ),
    ),
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'end_date',
            'value' => date('Y-m-d'),
            'compare' => '<',
            'type' => 'DATE',
        ),
    ),
));

// Loop through the funding agencies to check if any have past projects
foreach ($funding_agencies as $agency) {
    // Check if there are any past projects associated with this agency
    $has_past_projects = false;
    foreach ($related_programs_query->posts as $post) {
        if (has_term($agency->term_id, 'funding-agency', $post)) {
            $has_past_projects = true;
            break;
        }
    }

    // If there are no past projects, skip rendering this agency as a link
    if (!$has_past_projects) {
        continue;
    }

    // Add the agency to the ordered list
    $ordered_agencies[] = $agency;
}

?>

<div class="archive-navigation">
    <ul class="agency-list">
        <?php foreach ($ordered_agencies as $agency) : ?>
            <?php
            $nickname = get_field('term_nickname', 'funding-agency_' . $agency->term_id);
            $programs = get_field('related_programs', 'funding-agency_' . $agency->term_id);
            if (!$programs) {
                continue;
            }         
            ?>
            <li><a data-term-id="<?php echo $agency->term_id; ?>" href="#" class="funding-agency-link <?php echo ($agency->slug === 'nasa') ? 'current' : ''; ?>"><?php echo $nickname ? $nickname : $agency->name; ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>


<div class="archive-programs">
        <?php
        // Get related programs for the selected agency
        $programs = get_field('related_programs', 'funding-agency_' . $nasa->term_id);

        // Filter out programs without posts which are past projects
        $filtered_programs = array();
        if (!$programs) : ?>
            <p>Sorry, there are no programs for this funding agency which have past projects.</p>
        <?php else :
            foreach ($programs as $program_id) {
                // Check if the program has associated posts with end date in the past
                $args = array(
                    'post_type' => 'project',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'funding-program',
                            'terms' => $program_id,
                            'operator' => 'IN',
                        ),
                    ),
                    'meta_query' => array(
                        array(
                            'key' => 'end_date',
                            'value' => date('Y-m-d'), // Current date in YYYY-MM-DD format
                            'type' => 'DATE',
                            'compare' => '<', // Check if end date is before the current date
                        ),
                    ),
                    'posts_per_page' => 1, // Only need to check if at least one past project exists
                    'fields' => 'ids', // Optimize query by fetching only post IDs
                );

                $projects_query = new WP_Query($args);

                if ($projects_query->have_posts()) {
                    // If at least one past project is found, add the program to the filtered array
                    $filtered_programs[] = $program_id;
                }

                // Reset post data
                wp_reset_postdata();
            }

            // Sort the programs alphabetically by name
            usort($filtered_programs, function ($a, $b) {
                $program_a = get_term($a, 'funding-program');
                $program_b = get_term($b, 'funding-program');
                return strcasecmp($program_a->name, $program_b->name);
            });

            ?>

            <?php if (!empty($filtered_programs)) : ?>
                <ul class="program-list">
                    <?php foreach ($filtered_programs as $program_id) : ?>
                        <?php $program = get_term($program_id, 'funding-program'); ?>
                        <li><a data-program-id = <?php echo $program->term_id; ?> class="funding-program-link" href="#"><?php echo $program->name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>Sorry, there are no programs for this funding agency which have past projects.</p>
            <?php endif; ?>
        <?php endif; ?>
</div>

<div class="past-projects">
    <h4></h4>
    <div class="project-list"></div>
</div>

<?php get_footer(); ?>
