<?php
define('index_section', 'index_section'); //セクションIDの定数化
define('index_temp_select', 'index_temp_select'); //セクションIDの定数化
define('index_main_thumbs_img_pc', 'index_main_thumbs_img_pc'); //セクションIDの定数化
define('index_main_thumbs_img_sp', 'index_main_thumbs_img_sp'); //セクションIDの定数化
define('index_text_pref_box', 'index_text_pref_box'); //セクションIDの定数化
define('index_more_button_thumbs_img', 'index_more_button_thumbs_img'); //セクションIDの定数化
define('index_more_link_special_contents', 'index_more_link_special_contents'); //セクションIDの定数化
define('index_special_contents_back_image', 'index_special_contents_back_image'); //セクションIDの定数化

define('svg_text_index__box_01', 'svg_text_index__box_01');
define('svg_text_index__box_02', 'svg_text_index__box_02');
define('svg_text_index__box_03', 'svg_text_index__box_03');
define('svg_text_index__box_04', 'svg_text_index__box_04');
define('svg_text_index__box_05', 'svg_text_index__box_05');
define('svg_text_index__box_06', 'svg_text_index__box_06');

function theme_index_customizer( $wp_customize ) {
    $wp_customize->add_section( index_section , array(
        'title' => 'Index（トップページ）部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => 'Index（トップページ）部分', //セクションの説明
    ) );
//    $wp_customize->add_setting( index_main_thumbs_img_pc );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, index_main_thumbs_img_pc, array(
//        'label' => 'スクロール画像', //設定ラベル
//        'section' => index_section, //セクションID
//        'settings' => index_main_thumbs_img_pc, //セッティングID
//        'description' => 'スクロール画像を変更しましょう。',
//    ) ) );
////    $wp_customize->add_setting( index_main_thumbs_img_sp );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, index_main_thumbs_img_sp, array(
//        'label' => 'indexメイン画像（SP）', //設定ラベル
//        'section' => index_section, //セクションID
//        'settings' => index_main_thumbs_img_sp, //セッティングID
//        'description' => 'indexメイン画像（SP）をアップロードして変えましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( index_text_pref_box );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, index_text_pref_box, array(
//        'label'	=> 'index_テキスト設定',
//        'section'	=> index_section,
//        'settings'	=> index_text_pref_box,
//        'type'          => 'textarea',
//        'description' => 'index_テキスト設定入力項目エリアの設定しましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( index_more_button_thumbs_img );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, index_more_button_thumbs_img, array(
//        'label' => 'indexmoreボタン', //設定ラベル
//        'section' => index_section, //セクションID
//        'settings' => index_more_button_thumbs_img, //セッティングID
//        'description' => 'indexmoreボタンをアップロードして変えましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( index_special_contents_back_image );
//    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, index_special_contents_back_image, array(
//        'label' => 'スペシャルコンテンツ背景画像', //設定ラベル
//        'section' => index_section, //セクションID
//        'settings' => index_special_contents_back_image, //セッティングID
//        'description' => 'スペシャルコンテンツ背景画像をアップロードして変えましょう。',
//    ) ) );
//    
//    
//    $wp_customize->add_setting( index_more_link_special_contents );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, index_more_link_special_contents, array(
//        'label'	=> 'スペシャルコンテンツのリンク先',
//        'section'	=> index_section,
//        'settings'	=> index_more_link_special_contents,
//        'description' => 'スペシャルコンテンツのリンク先の設定を行いましょう。',
//    ) ) );
//    
//    $wp_customize->add_setting( svg_text_index__box_01 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_01, array(
//        'label'	=> 'SVGボックス01',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_01,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_index__box_02 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_02, array(
//        'label'	=> 'SVGボックス02',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_02,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_index__box_03 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_03, array(
//        'label'	=> 'SVGボックス03',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_03,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_index__box_04 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_04, array(
//        'label'	=> 'SVGボックス04',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_04,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_index__box_05 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_05, array(
//        'label'	=> 'SVGボックス05',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_05,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
//    $wp_customize->add_setting( svg_text_index__box_06 );
//    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, svg_text_index__box_06, array(
//        'label'	=> 'SVGボックス06',
//        'section'	=> index_section,
//        'settings'	=> svg_text_index__box_06,
//        'type'          => 'textarea',
//        'description' => 'SVGのコードを入力して下さい',
//    ) ) );
}
add_action( 'customize_register', 'theme_index_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_index_temp_select(){
 return get_theme_mod( index_temp_select);
}

?>