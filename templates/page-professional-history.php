<?php
/* Template Name: Professional History */
get_header();

$user_slug = get_query_var('user_nicename');

// Retrieve the user based on the 'user_slug' meta field
$user= get_users(array(
    'meta_key'   => 'user_slug',
    'meta_value' => $user_slug,
    'number'     => 1, // Limit the result to one user
));

if($user && $user[0]){
    $user_data = $user[0]->data;
} else {
    return "";
}

$user_id = $user_data->ID;

$position = get_field('position', 'user_'.$user_id);
$name = $user_data->display_name;

$profile_images = get_field('profile_pictures', 'user_' .$user_id);

    if($profile_images){
        $profile_img = $profile_images['professional_history_picture'] ? $profile_images['professional_history_picture'] : null;
        if(!empty($profile_img)){
            $profile_img_url = $profile_img['url'];
            $profile_img_alt = $profile_img['alt'];
        } else {
            $profile_img = $profile_images["primary_picture"] ? $profile_images["primary_picture"] : null;
            if(!empty($profile_img)){
                $profile_img_url = $profile_img['url'];
                $profile_img_alt = $profile_img['alt'];
            }
        }
            
    } 

    if(empty($profile_img)) {
        $default_image = get_field('default_user_picture', 'option');
        $profile_img_url = $default_image['url'];
        $profile_img_alt = $default_image['alt'];
    }

$professional_history = get_field('professional_history', 'user_' . $user_id);
if($professional_history) {
    $professional_history_text = $professional_history["professional_history_text"];
    $professional_history_images = $professional_history["professional_history_images"];
    $professional_history_image_caption = $professional_history["professional_history_image_caption"] ? $professional_history["professional_history_image_caption"] : '';
}

?>

<div id="primary" class="content-area professional-history">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="entry-content">
            <section class="professional-history__header">
                <img src="<?php echo $profile_img_url; ?>" alt="<?php echo $profile_img_alt;?>">
                <h1 class="staff__title"><?php echo $name; ?></h1>
                <h2 class="staff-info staff__position"><?php echo $position ? $position : ''; ?></h2> 
            </section>
            <section class="professional-history" <?php echo empty($professional_history_text) ? 'style="text-align:center;"' : ''; ?>>
                <?php if ($professional_history) { ?>
                    <h2><?php the_title(); ?></h2>
                    <p>
                        <?php 
                            if($professional_history_text) {                     
                                echo $professional_history_text;                  
                            } else {
                                echo 'No Professional History Found';
                            }
                        ?>
                    </p>
                    <?php
                        if(!$professional_history_images) {
                            $grid_style = 'less than 2 images';
                            
                        } else {
                            $grid_style = count($professional_history_images) > 1 ? 'style="grid-template-columns: 1fr 1fr;"' : 'style="grid-template-columns: 1fr; place-items: center;"';
                        }
                    ?>
                    <div class="optional-image-grid" <?php echo $grid_style; ?>>
                        <?php if($professional_history_images) { 
                            foreach($professional_history_images as $image) { ?>
                                <img src="<?php echo $image['sizes']['large'] ;  ?>" alt="<?php echo $image['alt'];  ?>">
                            <?php }
                        } ?>
                    </div>
                    <p><i><?php echo $professional_history_image_caption; ?></i></p>
                <?php } ?>
                </section>
            </div>
        </article>
    </main>
</div>

<?php
get_footer();
?>
