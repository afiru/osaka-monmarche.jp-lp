<!--メニューの呼び込み(ここから）-->
<?php 
$header_menu_name = 'メニューでチェックを入れた場所の名前';//要変更
$header_menu_nav_class = 'メニューのnavタグのクラス名';//要変更
$header_menu_ul_class = 'メニューのulタグのクラス名';//要変更
if( has_nav_menu( $header_menu_name ) ){ 
    wp_nav_menu ( array (
        'menu' => $header_menu_name,'menu_id' => $header_menu_name,'theme_location' => $header_menu_name, 'depth' => 2,'fallback_cb'     => 'wp_page_menu',
        'container' => 'nav','container_class'  =>$header_menu_nav_class,'menu_class' => $header_menu_ul_class
    ));
}
?>
<!--メニューの呼び込み(ここまで）-->

<!--ウィジェットの呼び込み(ここから）-->
<?php $read_widget_name = 'ウィジェット名';//要変更 ?>
<?php if ( is_active_sidebar( $read_widget_name ) ): dynamic_sidebar($read_widget_name);  endif; ?>
<!--ウィジェットの呼び込み(ここまで）-->
