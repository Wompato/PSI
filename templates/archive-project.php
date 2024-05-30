<?php
/*
Template Name: Active Projects Archive
*/

get_header();
?>

<div class="active-projects__link-container">
    <i style="font-size:1em; margin-right: 0.2em;" class="wpmi__icon wpmi__label-0 wpmi__position-before wpmi__align-middle wpmi__size-1 fa fa-external-link "></i>
    <a href="<?php echo home_url('/past-projects'); ?>">Go To Past Projects</a>
</div>

<?php
// Get all funding agency terms
$funding_agencies = get_terms(array(
    'taxonomy' => 'funding-agency',
    'hide_empty' => true, // Show even if they don't have projects associated
));

// Check if a funding agency is selected
$selected_agency_id = isset($_GET['agency_id']) ? $_GET['agency_id'] : '784';

// Get the default program ID
$nasa_agency = get_term_by('slug', 'nasa', 'funding-agency');
$default_program_id = $nasa_agency ? $nasa_agency->term_id : '';

// Create an array to store the ordered terms
$ordered_agencies = array();

// Create a single query to get all related programs and their posts with end_date in the future
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
            'compare' => '>',
            'type' => 'DATE',
        ),
    ),
));

// Loop through the funding agencies to check if any have active projects
foreach ($funding_agencies as $agency) {
    // Check if there are any active projects associated with this agency
    $has_active_projects = false;
    foreach ($related_programs_query->posts as $post) {
        if (has_term($agency->term_id, 'funding-agency', $post)) {
            $has_active_projects = true;
            break;
        }
    }

    // If there are no active projects, skip rendering this agency as a link
    if (!$has_active_projects) {
        continue;
    }

    // If the agency name is "NASA", add it to the beginning of the ordered list
    if ($agency->slug === 'nasa') {
        array_unshift($ordered_agencies, $agency);
    } else {
        // Add the agency to the ordered list
        $ordered_agencies[] = $agency;
    }
}

?>

<div class="archive-navigation">
   <!--  <h4>Funding Agencies</h4>
    <div class="section-headline"></div> -->
    <ul class="agency-list">
        <?php foreach ($ordered_agencies as $agency) : ?>
            <?php
            $nickname = get_field('term_nickname', 'funding-agency_' . $agency->term_id);
            $programs = get_field('related_programs', 'funding-agency_' . $agency->term_id);
            if (!$programs) {
                continue;
            }
            $is_current = ($agency->term_id == $selected_agency_id) ? 'active-link' : '';
            $agency_link = esc_url(remove_query_arg('program_id', add_query_arg('agency_id', $agency->term_id)));
            ?>
            <li><a href="<?php echo $agency_link; ?>" class="<?php echo $is_current; ?>"><?php echo $nickname ? $nickname : $agency->name; ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if (!empty($selected_agency_id)) : ?>
    <div class="archive-programs">
        

        <?php
        // Get related programs for the selected agency
        $programs = get_field('related_programs', 'funding-agency_' . $selected_agency_id);

        // Filter out programs without posts which are active projects
        $filtered_programs = array();
        if (!$programs) : ?>
            <p>Sorry, there are no programs for this funding agency which have active projects.</p>
        <?php else :
            foreach ($programs as $program_id) {
                // Check if the program has associated posts with end date in the future
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
                            'compare' => '>', // Check if end date is after the current date
                        ),
                    ),
                    'posts_per_page' => 1, // Only need to check if at least one active project exists
                    'fields' => 'ids', // Optimize query by fetching only post IDs
                );

                $projects_query = new WP_Query($args);

                if ($projects_query->have_posts()) {
                    // If at least one active project is found, add the program to the filtered array
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

            $default_program_id = !empty($filtered_programs) ? $filtered_programs[0] : '';
            $selected_program_id = isset($_GET['program_id']) ? $_GET['program_id'] : $default_program_id;
            ?>

            <?php if (!empty($filtered_programs)) : ?>
                <ul class="program-list">
                    <?php foreach ($filtered_programs as $program_id) : ?>
                        <?php
                        $program = get_term($program_id, 'funding-program');
                        $is_current = ($program->term_id == $selected_program_id) ? 'active-link' : '';
                        $program_link = esc_url(add_query_arg(array('agency_id' => $selected_agency_id, 'program_id' => $program_id), get_permalink()));
                        ?>
                        <li><a class="<?php echo $is_current; ?>" href="<?php echo $program_link; ?>"><?php echo $program->name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>Sorry, there are no programs for this funding agency which have active projects.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($programs)) : ?>
    <div class="archive-past-projects">
        <h4><?php echo ($selected_program_id) ? 'Projects from ' . get_term($selected_program_id, 'funding-program')->name : 'Active Projects'; ?></h4>
        <div class="section-headline"></div>

        <?php
        // Query for active projects associated with the selected program
        $args = array(
            'post_type' => 'project',
            'posts_per_page' => 5, // Number of projects per page
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1, // Get current page number
            'tax_query' => array(
                array(
                    'taxonomy' => 'funding-program',
                    'terms' => $selected_program_id,
                    'operator' => 'IN',
                ),
            ),
            // Custom meta query to check for active projects based on end_date
            'meta_query' => array(
                array(
                    'key' => 'end_date',
                    'value' => date('Y-m-d'), // Today's date
                    'compare' => '>', // Check if end date is after the current date
                    'type' => 'DATE',
                ),
            ),
        );

        $projects_query = new WP_Query($args);

        // Display active projects
        if ($projects_query->have_posts()) : ?>
            <div>
                <ul>
                    <?php while ($projects_query->have_posts()) : $projects_query->the_post(); ?>
                        <?php get_template_part('template-parts/projects/activity-banner', '', array('post' => $post)); ?>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php else : ?>
            <p>Sorry, no Active Projects found.</p>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php echo paginate_links(array('total' => $projects_query->max_num_pages, 'current' => max(1, get_query_var('paged')))); ?>
        </div>

        <?php wp_reset_postdata(); ?>
    </div>
<?php endif; ?>

<?php get_footer(); ?>
