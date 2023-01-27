<?php
/*ヘッダーの内容を記載する*/
define('header_amp_section', 'header_amp_section'); //セクションIDの定数化
define('header_logo_amp_pc_image', 'header_logo_amp_pc_image');
define('header_contact_sp_amp_button_image', 'header_contact_sp_amp_button_image');
define('button_contact_amp_image', 'button_contact_amp_image');
define('link_button_contact_amp_pref', 'link_button_contact_amp_pref');
define('button_sp_menu_open_amp_image', 'button_sp_menu_open_amp_image');
define('button_sp_menu_close_amp_image', 'button_sp_menu_close_amp_image');

define('svg_text_box_amp_01', 'svg_text_box_amp_01');
define('svg_text_box_amp_02', 'svg_text_box_amp_02');
define('svg_text_box_amp_03', 'svg_text_box_amp_03');
define('svg_text_box_amp_04', 'svg_text_box_amp_04');

function theme_header_amp_customizer( $wp_customize ) {
    $wp_customize->add_section( header_amp_section , array(
        'title' => 'ヘッダー(AMP用)設定', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => 'ヘッダー(AMP用)設定について', //セクションの説明
    ) );    


}
add_action( 'customize_register', 'theme_header_amp_customizer' );//カスタマイザーに登録
 

?>