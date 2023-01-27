<?php get_template_part('include/common/header/header'); ?>
<aside class="breadcrumb_list"><?php if(function_exists('bcn_display')) { bcn_display(); }?></aside>
<main class="mainCategory">
<?php get_template_part('include/category/catName/00_CatName'); ?>
</main>
<?php get_template_part('include/common/footer/footer'); ?>