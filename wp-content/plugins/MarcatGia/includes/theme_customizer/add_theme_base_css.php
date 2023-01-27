<?php
define('base_css_section', 'base_css_section'); //セクションIDの定数化
define('base_css_temp_select', 'base_css_temp_select'); //セクションIDの定数化
function theme_base_css_customizer( $wp_customize ) {
    $wp_customize->add_section( base_css_section , array(
        'title' => '読み込みCSSの選択部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => '読み込みCSSの選択部分', //セクションの説明
    ) );
    
    $wp_customize->add_setting( base_css_temp_select );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, base_css_temp_select, array(
        'label'	=> '読み込みCSSの選択デフォルトテンプレート選択',
        'section'	=> base_css_section,
        'settings'	=> base_css_temp_select,
        'description'   => '読み込みCSSの選択デフォルトのテンプレート選択をしましょう。下記から選んでください。',
        'type'          => 'select',
        'choices'        => array(
            '01'   => __( 'テンプレートno_01' ),
            '02'   => __( 'テンプレートno_02' ),
            '03'   => __( 'テンプレートno_03' ),
            '04'   => __( 'テンプレートno_04' ),
        )
    ) ) );
}
add_action( 'customize_register', 'theme_base_css_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_base_css_temp_select(){
 return get_theme_mod( base_css_temp_select);
}
?>