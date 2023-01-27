<?php get_header(); ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
    <?php foreach (SCF::get('PostImages') as $val):$img = get_scf_img_loop_url_id($val['PostImage']); ?>
        <img loading="lazy" src="<?php echo $img[0]; ?>" alt="" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" />
    <?php endforeach; ?>
<?php endwhile; // end of the loop. ?>
<?php get_footer(); ?>