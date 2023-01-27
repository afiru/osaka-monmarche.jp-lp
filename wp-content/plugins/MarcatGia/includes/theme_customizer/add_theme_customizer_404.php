<?php
define('section_404', 'section_404'); //セクションIDの定数化
define('temp_404_select', 'temp_404_select'); //セクションIDの定数化
define('page_404_pc_imglinks_image', 'page_404_pc_imglinks_image'); //セクションIDの定数化
define('page_404_sp_imglinks_image', 'page_404_sp_imglinks_image'); //セクションIDの定数化
define('page_404_sub_imglinks_image', 'page_404_sub_imglinks_image'); //セクションIDの定数化

define('svg_text_404_box_01', 'svg_text_404_box_01');
define('svg_text_404_box_02', 'svg_text_404_box_02');
define('svg_text_404_box_03', 'svg_text_404_box_03');
define('svg_text_404_box_04', 'svg_text_404_box_04');
define('svg_text_404_box_05', 'svg_text_404_box_05');
define('svg_text_404_box_06', 'svg_text_404_box_06');

function theme_404_customizer( $wp_customize ) {
    $wp_customize->add_section( section_404 , array(
        'title' => '404部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => '404部分', //セクションの説明
    ) );


}
add_action( 'customize_register', 'theme_404_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_404_temp_select(){
 return get_theme_mod( temp_404_select);
}

?>