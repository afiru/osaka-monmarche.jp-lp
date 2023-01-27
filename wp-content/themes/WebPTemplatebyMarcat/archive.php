<?php get_template_part('include/common/header/header'); ?>
<aside class="breadcrumb_list"><?php if(function_exists('bcn_display')) { bcn_display(); }?></aside>
<main class="mainArchives">
<?php if ( have_posts() ) while ( have_posts() ) : the_post();  ?>
<?php endwhile; // end of the loop. ?>
</main>
<?php get_template_part('include/common/footer/footer'); ?>