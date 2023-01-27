<!DOCTYPE html>
<html>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta content="text/css" http-equiv="Content-Style-Type" />
<meta content="text/javascript" http-equiv="Content-Script-Type" />
<meta http-equiv="content-type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<meta http-equiv="expires" content="86400">
<meta http-equiv="Content-Language" content="<?php bloginfo('language'); ?>">
<?php $user = get_user_by( 'id', 1 ); ?>
<?php if(!empty($user->first_name)): ?>
<meta name="Author" content="<?php echo $user->first_name.$user->last_name; ?>">
<?php endif; ?>
<meta name="format-detection" content="telephone=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="copyright" content="<?php bloginfo('name'); ?>" />
<meta name="viewport" content="viewport-fit=cover,width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="thumbnail" content="<?php echo get_bloginfo('template_url'); ?>/img/thumbs.png" />
<!--
  <PageMap>
    <DataObject type="thumbnail">
      <Attribute name="src" value="<?php echo get_bloginfo('template_url'); ?>/img/thumbs.png"/>
      <Attribute name="width" value="100"/>
      <Attribute name="height" value="100"/>
    </DataObject>
  </PageMap>
-->
<?php //タイトルの設定。【トップページ】カスタマイザーのSEOタイトル　【下層】ページタイトル｜カスタマイザーのSEOタイトル　 ?>
<title><?php echo get_the_site_title(get_php_customzer('seo_title')); ?></title>
<?php wp_head(); ?>
<script>
    var home_url ="<?php echo home_url('/'); ?>";
    var theme_url = "<?php echo get_bloginfo('template_url'); ?>";
    var rest_url = "<?php echo home_url('/wp-json/wp/v2/'); ?>";
</script>
<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
<script type='text/javascript' src="//cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js"></script>
<script type='text/javascript' src="<?php echo get_bloginfo('template_url'); ?>/js/animsition.min.js?ver=<?php echo date('Y-m-d'); ?>"></script>
<script type="text/javascript" src='<?php echo get_bloginfo('template_url'); ?>/js/config.js?ver=<?php echo date('Y-m-d'); ?>'> </script>
<script type="text/javascript" src='<?php echo get_bloginfo('template_url'); ?>/js/bxslider_setting.js?ver=<?php echo date('Y-m-d'); ?>'> </script>
<link rel="stylesheet" id='def_set_css' type="text/css" href="<?php echo get_bloginfo('template_url'); ?>/css/basestyle.css?ver=<?php echo date('Y-m-d'); ?>" media="all">
</head>
<body id="body" <?php body_class( 'baseBody' ); ?>>
<div id="pageTop" class="wap">
<header id="scroll_off" class="base_header">
    <div class="pc_only"><?php get_template_part('include/common/header/header_pc'); ?></div>
    <div class="sp_only"><?php get_template_part('include/common/header/header_sp'); ?></div>
</header>
