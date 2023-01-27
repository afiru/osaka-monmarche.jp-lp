<?php get_template_part('include/common/header/header'); ?>
<?php remove_filter ('the_content', 'wpautop'); ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post();  ?>
<aside class="breadcrumb_list"><?php if(function_exists('bcn_display')) { bcn_display(); }?></aside>
<main class="main<?php echo strtoupper($post->post_name); ?>">
<?php the_content(); ?>
</main>
<?php endwhile; // end of the loop. ?>
<?php get_template_part('include/common/footer/footer'); ?>