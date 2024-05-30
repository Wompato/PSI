<?php

$post = $args['post'];

if(isset($args['page'])) {
    $page = $args['page'];
} else {
    $page = '';
}

$thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
if(!$thumbnail_url) {
    $default_image = get_field('default_post_image', 'option');
    $thumbnail_url = $default_image['url'];
}
$post_permalink = get_permalink($post->ID);
$title = get_the_title($post->ID);
$post_date = date('F j, Y', strtotime($post->post_date)); // Format the date
$categories = get_the_category($post->ID); // Get all categories
$category_svg_icon = '<svg viewBox="0 0 16 16" class="bi bi-bookmarks" fill="currentColor" height="16" width="16" xmlns="http://www.w3.org/2000/svg">   <path d="M2 4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v11.5a.5.5 0 0 1-.777.416L7 13.101l-4.223 2.815A.5.5 0 0 1 2 15.5V4zm2-1a1 1 0 0 0-1 1v10.566l3.723-2.482a.5.5 0 0 1 .554 0L11 14.566V4a1 1 0 0 0-1-1H4z"></path>   <path d="M4.268 1H12a1 1 0 0 1 1 1v11.768l.223.148A.5.5 0 0 0 14 13.5V2a2 2 0 0 0-2-2H6a2 2 0 0 0-1.732 1z"></path> </svg>';
$date_svg_icon = '<svg viewBox="0 0 16 16" class="bi bi-calendar4" fill="currentColor" height="16" width="16" xmlns="http://www.w3.org/2000/svg">   <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z"></path> </svg>';
    
// Get the first category (if available)
$category_name = !empty($categories) ? $categories[0]->name : '';
                    
// Fetch related staff members using another ACF field
$related_staff_members = get_field('related_staff', $post->ID);
                    
// Output the related post with staff members
?>
<div class="related-post__item">
    <div class="related-post__container" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>');">
        <div class="related-post__category">
            <?php echo $category_svg_icon; ?>
            <span><?php echo esc_html($category_name); ?></span>
        </div>
                                
        <h3><a href="<?php echo esc_url($post_permalink); ?>"><?php echo esc_html($title); ?></a></h3>
    
        <div class="related-post__date">
            <?php echo $date_svg_icon; ?>
            <span><?php echo $post_date; ?></span>
        </div>
    </div>

    <?php
    $index = 1; 
     
    if ($related_staff_members) { ?>
    
    <div class="related-staff-container <?php echo $page ? 'page' . $page : '';?>" id="<?php echo "related-staff-container-" . $index; ?>">
        <?php 
                      
            foreach ($related_staff_members as $staff_member) { 
                get_template_part('template-parts/related-staff-member', '', array(
                    'staff-member' => $staff_member
                ));
            }
            $index++;
        
        ?> 
    </div>   
   <?php } ?>                  
</div>