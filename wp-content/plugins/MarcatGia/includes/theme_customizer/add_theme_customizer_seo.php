<?php

define('seo_tab_section', 'seo_tab_section'); //セクションIDの定数化
define('seo_title', 'seo_title'); //セクションIDの定数化
define('h1_text', 'h1_text'); //セクションIDの定数化
define('keywords', 'keywords');
define('description', 'description');
define('google_analytics', 'google_analytics');

define('body_after_code', 'body_after_code');
define('thanks_cvc_tag', 'thanks_cvc_tag');

function theme_seo_customizer( $wp_customize ) {
    $wp_customize->add_section( seo_tab_section , array(
        'title' => 'SEO関連設定', //セクション名
        'priority' => 30, //カスタマイザー項目の表示順
        'description' => 'SEO関連設定', //セクションの説明
    ) );
    $wp_customize->add_setting( seo_title );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, seo_title, array(
        'label'	=> 'SEOタイトル',
        'section'	=> seo_tab_section,
        'settings'	=> seo_title,
        'description' => 'SEOタイトル設定を行いましょう。',
    ) ) );
    
    $wp_customize->add_setting( h1_text );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, h1_text, array(
        'label'	=> 'h1の設定',
        'section'	=> seo_tab_section,
        'settings'	=> h1_text,
        'description' => 'h1の設定を行いましょう。',
    ) ) );
    
    $wp_customize->add_setting( keywords );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, keywords, array(
        'label'	=> 'キーワード',
        'section'	=> seo_tab_section,
        'settings'	=> keywords,
        'type'          => 'textarea',
        'description' => '共通のSEO関連キーワードがある場合はこちらで設定を行いましょう。',
    ) ) );
    
    $wp_customize->add_setting( description );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, description, array(
        'label'	=> 'ディスクリプション',
        'section'	=> seo_tab_section,
        'settings'	=> description,
        'type'          => 'textarea',
        'description' => '共通のディスクリプションがある場合はこちらで設定を行いましょう。',
    ) ) );

    $wp_customize->add_setting( google_analytics );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, google_analytics, array(
        'label'	=> 'Googleアナリティクス',
        'section'	=> seo_tab_section,
        'settings'	=> google_analytics,
        'type'          => 'textarea',
        'description' => '共通のGoogleアナリティクスがある場合はこちらで設定を行いましょう。',
    ) ) );
    
    $wp_customize->add_setting( body_after_code );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, body_after_code, array(
        'label'	=> 'body直下に入れるコード',
        'section'	=> seo_tab_section,
        'settings'	=> body_after_code,
        'type'          => 'textarea',
        'description' => 'body直下に入れるコードがある場合はこちらで設定を行いましょう。',
    ) ) );
    
    $wp_customize->add_setting( thanks_cvc_tag );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, thanks_cvc_tag, array(
        'label'	=> 'サンクスページだけの計測タグ',
        'section'	=> seo_tab_section,
        'settings'	=> thanks_cvc_tag,
        'type'          => 'textarea',
        'description' => 'サンクスページだけの計測タグがある場合はこちらで設定を行いましょう。',
    ) ) );
}
add_action( 'customize_register', 'theme_seo_customizer' );//カスタマイザーに登録