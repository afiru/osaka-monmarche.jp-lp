<?php if ( have_posts() ) while ( have_posts() ) : the_post();  ?>
<aside class="breadcrumb_list"><?php if(function_exists('bcn_display')) { bcn_display(); }?></aside>
<main class="mainSingle">
<?php the_content(); ?>
</main>
<?php endwhile; // end of the loop. ?>