<?php get_header(); ?>
<?php  $getDate = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS); ?>
<?php $weekSet = ['[日]','[月]','[火]','[水]','[木]','[金]','[土]']; ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
        <?php $i=1; foreach( scf::get('newLoops') as $field ): $cahckdate = 'slide_'.$i; ?>
        <div class="tabNew03 actionNew03 actionNew03_<?php echo $i; ?> <?php if($cahckdate==$getDate){ echo 'active'; }else{ if(empty($getDate) and $i===1){echo 'active';} else{echo 'nonactive';} } ?>">
            <?php $img = get_scf_img_loop_url($field['flyerImg']);?>
            <img src="<?php echo $img[0]; ?>" alt="画像" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" />
        </div>
        <?php $i++; endforeach; ?>
<?php endwhile; // end of the loop. ?>
<?php get_footer(); ?>