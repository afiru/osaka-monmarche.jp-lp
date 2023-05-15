<!DOCTYPE html>
<html>
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-5PRPDCL');</script>
<!-- End Google Tag Manager -->
<meta charset="UTF-8">
<meta content="text/css" http-equiv="Content-Style-Type" />
<meta content="text/javascript" http-equiv="Content-Script-Type" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="86400">
<meta http-equiv="Content-Language" content="ja">
<meta name="Robots" content="noodp">
<meta name="Author" content="Marcatucube">
<meta name="copyright" content="" />
<meta name="viewport" content="viewport-fit=cover,width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<?php //タイトルの設定。【トップページ】カスタマイザーのSEOタイトル　【下層】ページタイトル｜カスタマイザーのSEOタイトル　 ?>
<title><?php echo get_the_site_title(get_php_customzer('seo_title')); ?></title>
<?php wp_head(); ?>
<script>
    var home_url ="<?php echo home_url('/'); ?>";
    var theme_url = "<?php echo get_bloginfo('template_url'); ?>";
    var rest_url = "<?php echo home_url('/wp-json/wp/v2/'); ?>";
    <?php if(is_single()): ?>
        var post_id = <?php echo get_the_ID(); ?>;
    <?php endif; ?>
</script>
<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js?ver=<?php echo date('Y-m-d-H-i-s'); ?>'></script>
<script async src="//cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js?ver=<?php echo date('Y-m-d-H-i-s'); ?>"></script>
<link rel="stylesheet" id='def_set_css' type="text/css" href="<?php echo get_bloginfo('template_url'); ?>/css/basestyle.css?ver=<?php echo date('Y-m-d'); ?>" media="all">
</head>
<body id="page_top">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5PRPDCL"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <div id="page_wapper_master" class="new03wap">