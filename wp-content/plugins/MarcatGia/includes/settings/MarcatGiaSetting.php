<?php
//トップページ設定
require_once(dirname(__FILE__) . '/pages/MarcatGiaSetting_top.php');
//メニューの拡張設定
require_once(dirname(__FILE__) . '/pages/MarcatGiaMenuExpansion.php');
//ウィジェットの拡張設定
require_once(dirname(__FILE__) . '/pages/MarcatGiaWidgetExpansion.php');
//カスタマイザーの拡張設定
require_once(dirname(__FILE__) . '/pages/MarcatGiaCustomizerExpansion.php');
//カテゴリーの拡張設定
require_once(dirname(__FILE__) . '/pages/MarcatGiaCategoryExpansion.php');

add_action('admin_menu', 'MarcatGiaOptionPageSettings');
function MarcatGiaOptionPageSettings() {
    $page_title     = 'MarcatGia管理';
    $menu_title     = 'MarcatGia管理';
    $capability     = 'manage_options';
    $menu_slug      = 'MarcatGiaSettingTop';
    $function       = 'MarcatGiaSettingTop';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    $sub_menu_title = 'MarcatGiaについて';
    add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

    $submenu_page_title = 'メニューの拡張設定';
    $submenu_title      = 'メニューの拡張設定';
    $submenu_slug       = 'MarcatGiaMenuExpansion';
    $submenu_function   = 'MarcatGiaMenuExpansion';
    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
  
    $submenu_page_title = 'ウィジェットの拡張設定';
    $submenu_title      = 'ウィジェットの拡張設定';
    $submenu_slug       = 'MarcatGiaWidgetExpansion';
    $submenu_function   = 'MarcatGiaWidgetExpansion';
    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
    
    $submenu_page_title = 'カスタマイザーの拡張設定';
    $submenu_title      = 'カスタマイザーの拡張設定';
    $submenu_slug       = 'MarcatGiaCustomizerExpansion';
    $submenu_function   = 'MarcatGiaCustomizerExpansion';
    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
    
    $submenu_page_title = 'カテゴリーの拡張設定';
    $submenu_title      = 'カテゴリーの拡張設定';
    $submenu_slug       = 'MarcatGiaCategoryExpansion';
    $submenu_function   = 'MarcatGiaCategoryExpansion';
    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);   
}