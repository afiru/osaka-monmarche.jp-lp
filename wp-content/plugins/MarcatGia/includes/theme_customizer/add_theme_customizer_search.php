<?php
define('section_search', 'section_search'); //セクションIDの定数化

define('page_search_pc_imglinks_image', 'page_search_pc_imglinks_image'); //セクションIDの定数化
define('page_search_sp_imglinks_image', 'page_search_sp_imglinks_image'); //セクションIDの定数化

define('svg_text_search_box_01', 'svg_text_search_box_01');
define('svg_text_search_box_02', 'svg_text_search_box_02');
define('svg_text_search_box_03', 'svg_text_search_box_03');
define('svg_text_search_box_04', 'svg_text_search_box_04');
define('svg_text_search_box_05', 'svg_text_search_box_05');
define('svg_text_search_box_06', 'svg_text_search_box_06');


function theme_search_customizer( $wp_customize ) {
    $wp_customize->add_section( section_search , array(
        'title' => 'search部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => 'search部分', //セクションの説明
    ) );


}
add_action( 'customize_register', 'theme_search_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_search_temp_select(){
 return get_theme_mod( temp_search_select);
}
?>