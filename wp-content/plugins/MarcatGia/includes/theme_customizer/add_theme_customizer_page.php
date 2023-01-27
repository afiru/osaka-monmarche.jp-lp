<?php
define('page_section', 'page_section'); //セクションIDの定数化
define('page_temp_select', 'page_temp_select'); //セクションIDの定数化
function theme_page_customizer( $wp_customize ) {
    $wp_customize->add_section( page_section , array(
        'title' => '固定ページ部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => '固定ページ部分', //セクションの説明
    ) );
    
    $wp_customize->add_setting( page_temp_select );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, page_temp_select, array(
        'label'	=> '固定ページデフォルトテンプレート選択',
        'section'	=> page_section,
        'settings'	=> page_temp_select,
        'description'   => '固定ページデフォルトのテンプレート選択をしましょう。下記から選んでください。',
        'type'          => 'select',
        'choices'        => array(
            '01'   => __( 'テンプレートno_01' ),
            '02'   => __( 'テンプレートno_02' ),
            '03'   => __( 'テンプレートno_03' ),
            '04'   => __( 'テンプレートno_04' ),
        )
    ) ) );
}
add_action( 'customize_register', 'theme_page_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_page_temp_select(){
 return get_theme_mod( page_temp_select);
}


define('single_section', 'single_section'); //セクションIDの定数化
define('single_temp_select', 'single_temp_select'); //セクションIDの定数化
function theme_single_customizer( $wp_customize ) {
    $wp_customize->add_section( single_section , array(
        'title' => '記事ページ部分', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => '記事ページ部分', //セクションの説明
    ) );
    
    $wp_customize->add_setting( single_temp_select );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, single_temp_select, array(
        'label'	=> '固定ページデフォルトテンプレート選択',
        'section'	=> single_section,
        'settings'	=> single_temp_select,
        'description'   => '記事ページデフォルトのテンプレート選択をしましょう。下記から選んでください。',
        'type'          => 'select',
        'choices'        => array(
            '01'   => __( 'テンプレートno_01' ),
            '02'   => __( 'テンプレートno_02' ),
            '03'   => __( 'テンプレートno_03' ),
            '04'   => __( 'テンプレートno_04' ),
        )
    ) ) );
}
add_action( 'customize_register', 'theme_single_customizer' );//カスタマイザーに登録
 
//ヘッダーテンプレートの取得
function get_the_single_temp_select(){
 return get_theme_mod( single_temp_select);
}

?>