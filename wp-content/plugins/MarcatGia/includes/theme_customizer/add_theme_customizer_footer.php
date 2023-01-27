<?php

/*フッターの内容を記載する*/
define('footer_section', 'footer_section'); //セクションIDの定数化
define('footer_logo_pc_image', 'footer_logo_pc_image');
define('footer_logo_sp_image', 'footer_logo_sp_image');
define('tel_number_logo_footer_image', 'tel_number_logo_footer_image');
define('tel_number_link_footer_pref', 'tel_number_link_footer_pref');
define('button_footer_contact_image', 'button_footer_contact_image');
define('link_button_contact_footer_pref', 'link_button_contact_footer_pref');
define('button_footer_faq_image', 'button_footer_faq_image');
define('link_button_footer_faq_pref', 'link_button_footer_faq_pref');
define('button_footer_topics_image', 'button_footer_topics_image');
define('link_footer_button_topics_pref', 'link_footer_button_topics_pref');

define('footer_pref_input_01', 'footer_pref_input_01');
define('footer_pref_input_02', 'footer_pref_input_02');

define('footer_pref_input_03', 'footer_pref_input_03');
define('footer_pref_input_04', 'footer_pref_input_04');


define('footer_pref_text_01', 'footer_pref_text_01');
define('footer_pref_text_02', 'footer_pref_text_02');
define('footer_address_pref', 'footer_address_pref');
define('footer_temp_select', 'footer_temp_select');
define('tel_number_footer_pref', 'footer_temp_select');
define('footer_go_top_button', 'footer_go_top_button');

define('svg_text_footer_box_01', 'svg_text_footer_box_01');
define('svg_text_footer_box_02', 'svg_text_footer_box_02');
define('svg_text_footer_box_03', 'svg_text_footer_box_03');
define('svg_text_footer_box_04', 'svg_text_footer_box_04');
define('svg_text_footer_box_05', 'svg_text_footer_box_05');
define('svg_text_footer_box_06', 'svg_text_footer_box_06');

function theme_footer_customizer( $wp_customize ) {
    $wp_customize->add_section( footer_section , array(
        'title' => 'フッター部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => 'フッター部分', //セクションの説明
    ) );
    

    
//    $wp_customize->add_setting( footer_logo_sp_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, footer_logo_sp_image, array(
//        'label' => 'フッター用のロゴ画像（SP）', //設定ラベル
//        'section' => footer_section, //セクションID
//        'settings' => footer_logo_sp_image, //セッティングID
//        'description' => 'フッター用のロゴ画像（SP）をアップロードして変えましょう。',
//    ) ) );
//
//    $wp_customize->add_setting( tel_number_logo_footer_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, tel_number_logo_footer_image, array(
//        'label' => 'ショップ画像', //設定ラベル
//        'section' => footer_section, //セクションID
//        'settings' => tel_number_logo_footer_image, //セッティングID
//        'description' => 'ショップ画像をアップロードして変更しましょう。',
//    ) ) );
    
//    $wp_customize->add_setting( tel_number_link_footer_pref );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, tel_number_link_footer_pref, array(
//        'label'	=> '電話番号先設定',
//        'section'	=> footer_section,
//        'settings'	=> tel_number_link_footer_pref,
//        'description' => '電話番号先の設定しましょう。(URLを記載してください)',
//    ) ) );
//    
//    $wp_customize->add_setting( button_footer_contact_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, button_footer_contact_image, array(
//        'label' => 'お問合わせ画像', //設定ラベル
//        'section' => footer_section, //セクションID
//        'settings' => button_footer_contact_image, //セッティングID
//        'description' => 'お問合わせ画像をアップロードして変えましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( link_button_contact_footer_pref );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, link_button_contact_footer_pref, array(
//        'label'	=> 'お問い合わせ先設定',
//        'section'	=> footer_section,
//        'settings'	=> link_button_contact_footer_pref,
//        'description' => 'お問い合わせ先の設定しましょう。(URLを記載してください)',
//    ) ) );
//    
//    $wp_customize->add_setting( button_footer_faq_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, button_footer_faq_image, array(
//        'label' => 'よくあるご質問画像', //設定ラベル
//        'section' => footer_section, //セクションID
//        'settings' => button_footer_faq_image, //セッティングID
//        'description' => 'よくあるご質問画像をアップロードして変えましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( link_button_footer_faq_pref );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, link_button_footer_faq_pref, array(
//        'label'	=> 'よくあるご質問先設定',
//        'section'	=> footer_section,
//        'settings'	=> link_button_footer_faq_pref,
//        'description' => 'よくあるご質問先の設定しましょう。(URLを記載してください)',
//    ) ) );
//    
//    $wp_customize->add_setting( button_footer_topics_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, button_footer_topics_image, array(
//        'label' => '新着情報画像', //設定ラベル
//        'section' => footer_section, //セクションID
//        'settings' => button_footer_topics_image, //セッティングID
//        'description' => '新着情報画像をアップロードして変えましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( link_footer_button_topics_pref );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, link_footer_button_topics_pref, array(
//        'label'	=> '新着情報先設定',
//        'section'	=> footer_section,
//        'settings'	=> link_footer_button_topics_pref,
//        'description' => '新着情報先の設定しましょう。(URLを記載してください)',
//    ) ) ); 
//    
//    $wp_customize->add_setting( footer_pref_text_01 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_text_01, array(
//        'label'	=> 'フッター用テキスト入力項目エリア設定',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_text_01,
//        'type'          => 'textarea',
//        'description' => 'フッター用テキスト入力項目エリアの設定しましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( footer_pref_text_02 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_text_02, array(
//        'label'	=> 'フッター用テキスト入力項目エリア設定',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_text_02,
//        'type'          => 'textarea',
//        'description' => 'フッター用テキスト入力項目エリアの設定しましょう。',
//    ) ) );
//    
//
//    
//
//    
//    $wp_customize->add_setting( svg_text_footer_box_01 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_01, array(
//        'label'	=> 'SVGボックス01',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_01,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_footer_box_02 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_02, array(
//        'label'	=> 'SVGボックス02',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_02,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_footer_box_03 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_03, array(
//        'label'	=> 'SVGボックス03',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_03,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_footer_box_04 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_04, array(
//        'label'	=> 'SVGボックス04',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_04,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_footer_box_05 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_05, array(
//        'label'	=> 'SVGボックス05',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_05,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_footer_box_06 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_footer_box_06, array(
//        'label'	=> 'SVGボックス06',
//        'section'	=> footer_section,
//        'settings'	=> svg_text_footer_box_06,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    
//    $wp_customize->add_setting( footer_pref_input_03 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_input_03, array(
//        'label'	=> 'コーポレートサイトへのリンク文言',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_input_03,
//        'description' => '会社概要などのページへの文言を設定して下さい。',
//    ) ) );
//    $wp_customize->add_setting( footer_pref_input_04 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_input_04, array(
//        'label'	=> 'コーポレートサイトへのリンク設定',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_input_04,
//        'description' => 'コーポレートサイトへのリンク先を設定して下さい。',
//    ) ) );
//    
//    $wp_customize->add_setting( footer_pref_input_01 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_input_01, array(
//        'label'	=> '会社アクセス用（緯度）',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_input_01,
//        'description' => 'googlemapから会社の緯度を調べて記載して下さい。',
//    ) ) );
//    $wp_customize->add_setting( footer_pref_input_02 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_pref_input_02, array(
//        'label'	=> '会社アクセス用（経度）',
//        'section'	=> footer_section,
//        'settings'	=> footer_pref_input_02,
//        'description' => 'googlemapから会社の経度を調べて記載して下さい。',
//    ) ) );
    
    $wp_customize->add_setting( footer_address_pref );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, footer_address_pref, array(
        'label'	=> 'アドレスタグ内の指定',
        'section'	=> footer_section,
        'settings'	=> footer_address_pref,
        'description' => 'アドレスタグ内の文言（コーポレート設定）をしましょう。',
    ) ) ); 
}
add_action( 'customize_register', 'theme_footer_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_footer_temp_select(){
 return get_theme_mod( footer_temp_select);
}