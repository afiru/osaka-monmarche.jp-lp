<?php
$args = array(
    'post_type' => 'post',
    'posts_per_page' =>1,
    'order'=>'ASC',
    'orderby'=>'menu_order'
);
$the_query = new WP_Query( $args );
if ( $the_query->have_posts() ) {
    while ( $the_query->have_posts() ) {
        $the_query->the_post();
        header("Location: " . get_permalink($post->ID));
        exit();
    }
}
?>
<?php get_header(); ?>
<?php get_footer(); ?>