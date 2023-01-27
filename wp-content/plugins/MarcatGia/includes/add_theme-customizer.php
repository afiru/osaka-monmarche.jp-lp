<?php
/*テーマカスタマイザーの拡張*/
//ベーススタイルのcss選択
//require_once(dirname(__FILE__) .  '/theme_customizer/add_theme_base_css.php');

//ヘッダー項目
require_once(dirname(__FILE__) .  '/theme_customizer/add_theme_customizer_seo.php');

//ヘッダー項目
require_once(dirname(__FILE__) .  '/theme_customizer/add_theme_customizer_header.php');
//ヘッダー（amp用）項目
require_once(dirname(__FILE__) .  '/theme_customizer/add_theme_customizer_header_amp.php');

//フッター項目
require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_footer.php');

//index項目
//require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_index.php');

//404項目
require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_404.php');

//search項目
//require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_search.php');

//記事ページ・詳細ページ
//require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_page.php');

//カスタムナビ項目
require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_navigation.php');

//スライダー用の設定
require_once(dirname(__FILE__) . '/theme_customizer/add_slaider_custom_field.php');

//widget用の設定
require_once(dirname(__FILE__) . '/theme_customizer/add_theme_customizer_widget.php');


//rest-api出力処理
$themes_custom_lists[1]="h1_text";
$themes_custom_lists[2]="header_logo_pc_image";
$themes_custom_lists[3]="header_logo_sp_image";
$themes_custom_lists[4]="tel_number_logo_image";
$themes_custom_lists[5]="tel_number_header_pref";
$themes_custom_lists[6]="button_contact_image";
$themes_custom_lists[7]="link_button_contact_pref";
$themes_custom_lists[8]="button_faq_image";
$themes_custom_lists[9]="link_button_faq_pref";
$themes_custom_lists[10]="button_topics_image";
$themes_custom_lists[11]="link_button_topics_pref";
$themes_custom_lists[12]="button_sp_menu_open_image";
$themes_custom_lists[13]="button_sp_menu_close_image";
$themes_custom_lists[14]="header_temp_select";
$themes_custom_lists[15]="header_back_image";
$themes_custom_lists[16]="svg_text_box_01";
$themes_custom_lists[17]="svg_text_box_02";
$themes_custom_lists[18]="svg_text_box_03";
$themes_custom_lists[19]="svg_text_box_04";
$themes_custom_lists[20]="svg_text_box_05";
$themes_custom_lists[21]="svg_text_box_06";
$themes_custom_lists[22]="footer_section";
$themes_custom_lists[23]="footer_logo_pc_image";
$themes_custom_lists[24]="footer_logo_sp_image";
$themes_custom_lists[25]="tel_number_logo_footer_image";
$themes_custom_lists[26]="tel_number_link_footer_pref";
$themes_custom_lists[27]="button_footer_contact_image";
$themes_custom_lists[28]="link_button_contact_footer_pref";
$themes_custom_lists[29]="button_footer_faq_image";
$themes_custom_lists[30]="link_button_footer_faq_pref";
$themes_custom_lists[31]="button_footer_topics_image";
$themes_custom_lists[32]="link_footer_button_topics_pref";
$themes_custom_lists[33]="footer_pref_text_01";
$themes_custom_lists[34]="footer_pref_text_02";
$themes_custom_lists[35]="footer_address_pref";
$themes_custom_lists[36]="footer_temp_select";
$themes_custom_lists[37]="tel_number_footer_pref";
$themes_custom_lists[38]="footer_go_top_button";
$themes_custom_lists[39]="svg_text_footer_box_01";
$themes_custom_lists[40]="svg_text_footer_box_02";
$themes_custom_lists[41]="svg_text_footer_box_03";
$themes_custom_lists[42]="svg_text_footer_box_04";
$themes_custom_lists[43]="svg_text_footer_box_05";
$themes_custom_lists[44]="svg_text_footer_box_06";
$themes_custom_lists[45]="index_temp_select";
$themes_custom_lists[46]="index_main_thumbs_img_pc";
$themes_custom_lists[47]="index_main_thumbs_img_sp";
$themes_custom_lists[48]="index_text_pref_box";
$themes_custom_lists[49]="index_more_button_thumbs_img";
$themes_custom_lists[50]="index_more_link_special_contents";
$themes_custom_lists[51]="index_special_contents_back_image";
$themes_custom_lists[52]="svg_text_index__box_01";
$themes_custom_lists[53]="svg_text_index__box_02";
$themes_custom_lists[54]="svg_text_index__box_03";
$themes_custom_lists[55]="svg_text_index__box_04";
$themes_custom_lists[56]="svg_text_index__box_05";
$themes_custom_lists[57]="svg_text_index__box_06";
$themes_custom_lists[58]="temp_404_select";
$themes_custom_lists[59]="page_404_pc_imglinks_image";
$themes_custom_lists[60]="page_404_sp_imglinks_image";
$themes_custom_lists[61]="page_404_sub_imglinks_image";
$themes_custom_lists[62]="svg_text_404_box_01";
$themes_custom_lists[63]="svg_text_404_box_02";
$themes_custom_lists[64]="svg_text_404_box_03";
$themes_custom_lists[65]="svg_text_404_box_04";
$themes_custom_lists[66]="svg_text_404_box_05";
$themes_custom_lists[67]="svg_text_404_box_06";
$themes_custom_lists[68]="page_search_pc_imglinks_image";
$themes_custom_lists[69]="page_search_sp_imglinks_image";
$themes_custom_lists[70]="svg_text_search_box_01";
$themes_custom_lists[71]="svg_text_search_box_02";
$themes_custom_lists[72]="svg_text_search_box_03";
$themes_custom_lists[73]="svg_text_search_box_04";
$themes_custom_lists[74]="svg_text_search_box_05";
$themes_custom_lists[75]="svg_text_search_box_06";


add_action( 'rest_api_init', 'customizers_index_hooks' );
function customizers_index_hooks() {
    register_rest_route( 'wp/v2/customizers', '/date/', array(
        'methods' => 'GET',
        'callback' => 'get_the_index_customizer',
    ) );
}
function get_the_index_customizer() {    
    global $themes_custom_lists;
    $outputlist =array();
    foreach($themes_custom_lists as $key => $val){
        $outputlist[$val] = get_theindex_customizer_check($val);
    }
    return $outputlist;
}

function get_theindex_customizer_check($date) {
    if(strpos($date,'thumbs') !== false){
       $themes_custom_listsdate = esc_url( get_theme_mod($date));
    }
    if(strpos($date,'image') !== false){
        $themes_custom_listsdate = esc_url( get_theme_mod($date));
    }
    if(strpos($date,'img') !== false){
        $themes_custom_listsdate = esc_url( get_theme_mod($date));
    }
    if(strpos($date,'logo') !== false){
        $themes_custom_listsdate = esc_url( get_theme_mod($date));
    }
    if(strpos($date,'LOGO') !== false){
        $themes_custom_listsdate = esc_url( get_theme_mod($date));
    }
    $themes_custom_listsdate = nl2br(get_theme_mod( $date));
    if ( empty( $themes_custom_listsdate ) ) {
        return null;
    }
    return $themes_custom_listsdate;
}

// 投稿一覧にサムネイル追加
function add_posts_columns_thumbnail($columns) {
  $columns['thumbnail'] = 'サムネイル';
  return $columns;
}
function add_posts_columns_thumbnail_row($column_name, $post_id) {
  if ( 'thumbnail' == $column_name ) {
    $thumb = get_the_post_thumbnail($post_id, array(100, 100), 'thumbnail');
    echo ( $thumb ) ? $thumb : '－';
  }
}
add_filter( 'manage_posts_columns', 'add_posts_columns_thumbnail' );
add_action( 'manage_posts_custom_column', 'add_posts_columns_thumbnail_row', 10, 2 );


//ウィジェットリストのrest-api
$widget_lists[0]="index_01";
$widget_lists[1]="index_02";
$widget_lists[2]="index_03";
$widget_lists[3]="index_04";
$widget_lists[4]="index_05";
$widget_lists[5]="index_06";
$widget_lists[6]="sidebar_01";
$widget_lists[7]="sidebar_02";
$widget_lists[8]="sidebar_03";
$widget_lists[9]="sidebar_04";
$widget_lists[10]="sidebar_05";

add_action( 'rest_api_init', 'customizers_widget_hooks' );
function customizers_widget_hooks() {
    register_rest_route( 'wp/v2/customizers', '/widgets/', array(
        'methods' => 'GET',
        'callback' => 'get_the_widget_customizer',
    ) );
}
function get_the_widget_customizer() {    
    global $widget_lists;
    $outputlist =array();
    foreach($themes_custom_lists as $key => $val){
        if ( is_active_sidebar( $val ) ) :
        $widget_lists[$val] = dynamic_sidebar( $val );
        endif;
    }
    return $outputlist;
}

/*PHPでの読み取り関数*/
function get_php_customzer($templatename = 0 ){
    if(empty($templatename)) {
        $return_value = '';
    }else {
        if(strpos($templatename,'image') !== false){
            $return_value = esc_url( get_theme_mod( $templatename ) );
        }else {
            $return_value = get_theme_mod( $templatename);
        }
    }
    return $return_value;
}
//ロゴの取得用（ヘッダー用）
function get_logo_img() {
    $logo_pass = '';
    $logo_svg_code = get_php_customzer('svg_text_box_01');
    $logo_svg = '<figure title="'.get_the_site_title(get_php_customzer('h1_text')).'" class="logo_box">' . get_php_customzer('svg_text_box_01') . '</figure>'; 
    $logo_img= '<img alt="'.get_the_site_title(get_php_customzer('h1_text')).'" src="'.get_php_customzer('header_logo_pc_image') .'" />'; 
    $logo_pass = empty($logo_svg_code)  ? $logo_img : $logo_svg;
    return $logo_pass;
}
//お問合せ画像（PC）取得用
function get_contact_pc_pass_img() {
    $contact_pass = '';
    $contact_svg_code = get_php_customzer('svg_text_box_05');
    $contact_svg = '<a title="お問い合わせはこちら" class="header_contact_link_box" href="'. get_php_customzer('link_button_contact_pref') .'"><figure title="お問い合わせボタン" class="logo_box">' . get_php_customzer('svg_text_box_05') . '</figure></a>'; 
    $contact_img= '<a title="お問い合わせはこちら" class="header_contact_link_box" href="'. get_php_customzer('link_button_contact_pref') .'"><img alt="お問い合わせボタン" src="'.get_php_customzer('button_contact_image') .'" class="img_block" /></a>'; 
    $contact_pass = empty($contact_svg_code)  ? $contact_img : $contact_svg;
    return $contact_pass;
}
//お問合せ画像（SP）取得用
function get_contact_sp_pass_img() {
    $contact_pass = '';
    $contact_svg_code = get_php_customzer('svg_text_box_02');
    $contact_svg = '<a title="お問い合わせはこちら" class="header_contact_link_box" href="'. get_php_customzer('link_button_contact_pref') .'"><figure title="お問い合わせボタン" class="logo_box">' . get_php_customzer('svg_text_box_02') . '</figure></a>'; 
    $contact_img= '<a title="お問い合わせはこちら" class="header_contact_link_box" href="'. get_php_customzer('link_button_contact_pref') .'"><img alt="お問い合わせボタン" src="'.get_php_customzer('header_contact_sp_button_image') .'" class="img_block" /></a>'; 
    $contact_pass = empty($contact_svg_code)  ? $contact_img : $contact_svg;
    return $contact_pass;
}
//スマホボタン取得
function get_sp_menu_button_img() {
    //スマホボタン（通常時)
    $sp_button_off_pass = '';
    $sp_button_off_svg_code = get_php_customzer('svg_text_box_03');
    $sp_button_off_svg = '<figure title="スマホメニューを開く" class="off">' . get_php_customzer('svg_text_box_03') . '</figure>'; 
    $sp_button_off_img= '<img class="off" alt="スマホメニューを開" src="'.get_php_customzer('header_contact_sp_button_image') .'" /></a>'; 
    $sp_button_off_pass = empty($sp_button_off_svg_code)  ? $sp_button_off_img : $sp_button_off_svg;    
    //スマホボタン（押し込み時)
    $sp_button_on_pass = '';
    $sp_button_on_svg_code = get_php_customzer('svg_text_box_04');
    $sp_button_on_svg = '<figure title="スマホメニューを開く" class="on">' . get_php_customzer('svg_text_box_04') . '</figure>'; 
    $sp_button_on_img= '<img class="on" alt="スマホメニューを開" src="'.get_php_customzer('button_sp_menu_close_image') .'" /></a>'; 
    $sp_button_on_pass = empty($sp_button_on_svg_code)  ? $sp_button_on_img : $sp_button_on_svg;    
    return $sp_button_off_pass . $sp_button_on_pass;
}

//ロゴの取得用（フッター用）
function get_footer_logo_img() {
    $logo_pass = '';
    $logo_svg_code = get_php_customzer('svg_text_footer_box_01');
    $logo_svg = '<figure title="'.get_the_site_title(get_php_customzer('h1_text')).'" class="logo_box">' . get_php_customzer('svg_text_footer_box_01') . '</figure>'; 
    $logo_img= '<img alt="'.get_the_site_title(get_php_customzer('h1_text')).'" src="'.get_php_customzer('footer_logo_pc_image') .'" />'; 
    $logo_pass = empty($logo_svg_code)  ? $logo_img : $logo_svg;
    return $logo_pass;
}
?>