<?php
/*
Template Name: Advanced Search Template
*/
get_header();

// Set up pagination variables
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$args['paged'] = $paged;

// Handle form submission
if (isset($_GET['title']) || isset($_GET['project_number']) || isset($_GET['award_number'])) {
    $title = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : '';
    $projectNumber = isset($_GET['project_number']) ? sanitize_text_field($_GET['project_number']) : '';
    $awardNumber = isset($_GET['award_number']) ? sanitize_text_field($_GET['award_number']) : '';

    $args = array(
        'post_type'      => 'project', // Adjust post type if needed
        'posts_per_page' => 5,          // Show only the first 5 results per page
        'paged'          => $paged,     // Current page number
        'meta_query'     => array(
            'relation' => 'AND', // Combine meta queries with AND
        )
    );
    
    // Check if each parameter is set and not empty, then add it to meta_query
    if (!empty($projectNumber)) {
        $args['meta_query'][] = array(
            'key'     => 'project_number',
            'value'   => $projectNumber,
            'compare' => '='
        );
    }
    
    if (!empty($awardNumber)) {
        $args['meta_query'][] = array(
            'key'     => 'award_number',
            'value'   => $awardNumber,
            'compare' => '='
        );
    }
    
    // Check if title parameter is set and not empty
    if (!empty($title)) {
        $args['s'] = $title; // Include title in the main query
    }
    
    $query = new WP_Query($args);
}
?>

<form role="search" method="get" id="searchform" class="searchform">
    <div>
        <label for="title">Search by Title:</label>
        <input type="text" value="<?php echo isset($_GET['title']) ? $_GET['title'] : ''; ?>" name="title" id="title" />
        <label for="project_number">Project Number:</label>
        <input type="text" value="<?php echo isset($_GET['project_number']) ? $_GET['project_number'] : ''; ?>" name="project_number" id="project_number" />
        <label for="award_number">Award Number:</label>
        <input type="text" value="<?php echo isset($_GET['award_number']) ? $_GET['award_number'] : ''; ?>" name="award_number" id="award_number" />
        <input type="hidden" name="post_type" value="projects" /> <!-- Adjust the post type to your custom post type name -->
        <input type="submit" id="searchsubmit" value="Search" />
    </div>
</form>

<?php
if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            // Display each project here
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry-content">
                <?php get_template_part('template-parts/projects/activity-banner', '', array('post' => $post)); ?>
                </div>
            </article>
            <?php
        endwhile;

        // Pagination
        echo '<div class="pagination">';
        echo paginate_links(array(
            'total' => $query->max_num_pages,
            'prev_text' => __('Previous', 'textdomain'),
            'next_text' => __('Next', 'textdomain'),
        ));
        echo '</div>';
    else :
        // No projects found
        echo '<p>No projects found.</p>';
    endif;

    // Restore global post data
    wp_reset_postdata();

get_footer(); ?>
