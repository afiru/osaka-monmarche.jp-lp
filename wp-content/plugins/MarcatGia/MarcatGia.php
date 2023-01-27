<?php
    /*
    Plugin Name: MarcatGia
    Plugin URI: なし
    Description: カスタムフィールドテンプレートの値をずれなく取得することができるプラグイン $costom = get-custom-field-template($post->ID)で利用できます。また、投稿した内容をrest-api上で確認することも可能です。
    Version: 1.0
    Author: MarcatCube
    Author URI:https://www.marcatcube.com/
    License: MarcatCube
    */

?>
<?php
//設定用ファイル
require_once(dirname(__FILE__) . '/includes/settings/MarcatGiaSetting.php');

//基本ファイル
require_once(dirname(__FILE__) . '/includes/bass_plugin.php');

//API側の処理（カスタム投稿などを追加する場合はこちらに記載するようにしましょう）
require_once(dirname(__FILE__) . '/includes/api_library_function.php');


//サムネイルなどの情報を取得するプラグイン
require_once(dirname(__FILE__) . '/includes/thumbnail_output.php');

//カテゴリーの画像をカスタマイズするプラグイン
require_once(dirname(__FILE__) . '/includes/custom_category_add_images.php');


//カスタマイザーの機能を拡張するプラグイン
require_once(dirname(__FILE__) . '/includes/add_theme-customizer.php');

//タイトルとh1の設定を行うプラグイン
require_once(dirname(__FILE__) . '/includes/get_title_h1.php');

//ショートコード読み込みプラグイン
require_once(dirname(__FILE__) . '/includes/short_codes.php');


function marcatgia($postid, $keys = array(), $matrix = false){
    global $wpdb;
    $query = "SELECT * FROM $wpdb->postmeta WHERE post_id = ". $postid ." ORDER BY meta_id ASC";
    $queryresults = $wpdb->get_results($query, ARRAY_A);
    foreach( $queryresults as $queryresult ){
        $keyname = trim($queryresult['meta_key']);
        if ( $keyname[0] != '_' ) {
            $rows = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = '" . $postid . "' AND meta_key = '" . $queryresult['meta_key'] . "' ORDER BY meta_id ASC");
            if($rows==1 && !$matrix) {
                if(strpos($queryresult['meta_key'],'imglinks') !== false){
                    $results[$queryresult['meta_key']] = get_thumbs_url_return($queryresult['meta_value']);
                }
                elseif(strpos($queryresult['meta_key'],'pdflinks') !== false){
                    $pdfpath = get_post_meta($postid, $queryresult['meta_key'], false);
                    foreach($pdfpath as $pdfpath){
                        $results[$queryresult['meta_key']] = wp_get_attachment_url($pdfpath);
                    }
                }
                else {
                    $results[$queryresult['meta_key']] = $queryresult['meta_value'];
                }

            } else {
                if(strpos($queryresult['meta_key'],'imglinks') !== false){
                    $results[$queryresult['meta_key']][] = get_thumbs_url_return($queryresult['meta_value']);
                }
                elseif(strpos($queryresult['meta_key'],'pdflinks') !== false){
                    $pdfpath = get_post_meta($postid, $queryresult['meta_key'], false);
                    foreach($pdfpath as $pdfpath){
                        $results[$queryresult['meta_key']][] = wp_get_attachment_url($pdfpath);
                    }
                }else {
                    $results[$queryresult['meta_key']][] = $queryresult['meta_value'];
                }
            }
        }
    }
    if(!empty($results)):
        if(empty($keys) || empty($results)){
            $customs = $results;
        } else {
            foreach($keys as $key){
                if(array_key_exists($key, $results)) $customs[$key] = $results[$key];
            }
        }
    else:
        $customs = array();
    endif;

    $image_id = get_post_thumbnail_id($postid);
    $image_url = wp_get_attachment_url($image_id, true);
    $customs['post_thumbs_list_thumbnail'] = wp_get_attachment_image_src( get_the_post_thumbnail($postid), 'thumbnail' );
    $customs['post_thumbs_list_medium'] = wp_get_attachment_image_src( get_the_post_thumbnail($postid), 'medium' );
    $customs['post_thumbs_list_large'] = wp_get_attachment_image_src( get_the_post_thumbnail($postid), 'large' );
    if(!empty($image_url)) {
        $customs['post_thumbs_list'] = $image_url;
    }
    else {
        $customs['post_thumbs_list'] = "";
    }
    $customs['date_dotto'] = get_the_date('Y.m.d',$postid);
    return $customs;
}

global $custom;



function rest_api_js_read(){
    $filepass = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
}
add_action('wp_footer', 'rest_api_js_read');


function home_url_page() {
    return home_url();
}
add_shortcode('home_url', 'home_url_page');

//スラッグからcategory名を取得
function get_the_link_parmalinks($type = 'page',$slub = 'slug'){
    //固定ページから
    if($type == 'page') {
        $post_id = get_page_by_path($slub, "OBJECT", "page");
        $link_about = get_the_permalink($post_id->ID);
    }
    if($type == 'post') {
        $post_id = get_posts("name=$slub");
        $link_about = get_the_permalink($post_id[0]->ID);
    }
    if($type == 'category') {
        $cat_id = get_category_by_slug($slub);
        $cat_id = $cat_id->term_id;
        $link_about = get_category_link($cat_id);
    }
    
    return $link_about;
}
//画像のリンク先を探す
function theme_img_page() {
    $list = get_bloginfo('template_url'). '/img';
    return $list;
}
add_shortcode('theme_img', 'theme_img_page');
//souseとして利用する場合
function themes_img_sauce_fc($atts) {
    extract(shortcode_atts(array(
        'class' =>'default',
        'id' =>'default',        
        'type'=>'default',
        'media'=>'default',
        'fifle_pass' => 'default',
    ), $atts));
    $img_pass = get_bloginfo('template_url'). '/img/'.$fifle_pass;
    $output = '<source id="'.$id.'" class="'.$class.'" type="'.$tipe.'" media="'.$media.'" data-srcset="'.$img_pass.'" />';
    return $output;
}
add_shortcode('themes_img_sauce', 'themes_img_sauce_fc');


//アイキャッチ
function get_the_thumbs_nail_func() {
    extract(shortcode_atts(array(
        'post_id' => 0,
    ), $atts));
    $image_id = get_post_thumbnail_id ($post_id);
    $image_url = wp_get_attachment_image_src ($image_id, true);
    return $image_url[0];
}
add_shortcode('get_the_thumbs_url', 'get_the_thumbs_nail_func');


function custom_fild_temp_single2($atts) {
    extract(shortcode_atts(array(
        'post_id' => 0,
        'custom_name' => 0,
    ), $atts));
    $custom = marcatgia($post_id);
    if(is_array($custom[$custom_name])) {
        $output = '<ul class="custom_date_loop">';
        foreach ($custom[$custom_name] as $key => $val) {
            $output .= "<li>$val</li>";
        }
        $output .= '</ul>';
        return $output;
    }else {
        return $custom[$custom_name];
    }    
}

add_shortcode('single_custom2', 'custom_fild_temp_single2');


function page_innner_nav_fc($atts) {
    extract(shortcode_atts(array(
        'nav_id' => 0,
    ), $atts));
    $nav_id_class = $nav_id. '_nav';
    $nav_id_ul_class = $nav_id. '_ul';
    ob_start();
    if( has_nav_menu( $nav_id ) ){ 
        wp_nav_menu ( array (
            'menu' => $nav_id,
            'menu_id' => $nav_id,
            'theme_location' => $nav_id, 
            'depth' => 2,
            'fallback_cb'     => 'wp_page_menu',
            'container' => 'nav',
            'container_class'  => $nav_id_class,
            'menu_class' => $nav_id_ul_class
        ));
    }
    $text = ob_get_clean();
    return $text;
}
add_shortcode('page_innner_nav', 'page_innner_nav_fc');



function page_in_other_page_fc($atts){
    extract(shortcode_atts(array(
        'page_id' => 0,
    ), $atts));
    $pref = "";
    $query1 = new WP_Query(array('page_id'=>$page_id));
    if ( $query1->have_posts() ):
	while ( $query1->have_posts() ):$query1->the_post();
            ob_start();
            the_content();
            $pref = ob_get_clean();
        endwhile;
    endif;	
    wp_reset_postdata();
    return $pref;
}
add_shortcode('page_in_other_page', 'page_in_other_page_fc');

//親子を維持
function solecolor_wp_terms_checklist_args( $args, $post_id ){
	if(!empty($args['checked_ontop'])){
	   if ( $args['checked_ontop'] !== false ){
	        $args['checked_ontop'] = false;
	   }
	}

   return $args;
}
add_filter('wp_terms_checklist_args', 'solecolor_wp_terms_checklist_args',10,2);

//カテゴリー一覧だけall in one 以外のタイトルやキーワードをつける
function MarcatGiaCustomAllInOneSEOTitle( $title ) {
    global $cat;
    if ( is_category() ) {        
        $title = SCF::get_term_meta($cat,'category','seo_title');        
    }
    return $title;
}
add_filter('aioseo_title' , 'MarcatGiaCustomAllInOneSEOTitle');
function meta_headcustomtags() {
    global $cat;
    if ( is_category() ) { 
        $catdate = get_cat_tax_img('category',$cat);
        $title = $catdate['SEO_Title'];
        $description = $catdate['SEO_Description'];
        $now_url = get_category_link($cat);
        $blog_title = get_bloginfo('name');
        $aiosp_opengraph_dimg = $catdate['OGimage'];
		$headcustomtag = <<<EOM
		<meta property="og:type" content="website" />
		<meta property="og:title" content="$title" />
		<meta property="og:description" content="$description" />
		<meta property="og:url" content="$now_url" />
		<meta property="og:site_name" content="$blog_title" />
		<meta property="og:image" content="$aiosp_opengraph_dimg" />
		<meta property="og:image:secure_url" content="$aiosp_opengraph_dimg" />
		<meta name="twitter:card" content="summary" />
		<meta name="twitter:title" content="$blog_title" />
		<meta name="twitter:description" content="$description" />
		<meta name="twitter:image" content="$aiosp_opengraph_dimg" />
		EOM;
		echo $headcustomtag;
	}
}
add_action( 'wp_head', 'meta_headcustomtags', 99);

function MarcatGiaCustomAllInOneSEODescription( $description ) {
    global $cat;
    if ( is_category() ) {
        $description = SCF::get_term_meta($cat,'category','seo_description');
    }
    return $description;
}
add_filter('aioseo_description' , 'MarcatGiaCustomAllInOneSEODescription');

function MarcatGiaCustomAllInOneSEOKeyword( $keyword ) {
    global $cat;
    if ( is_category() ) {
        $keyword = SCF::get_term_meta($cat,'category','seo_keyword');
    }
    return $keyword;
}
add_filter('aioseo_keywords' , 'MarcatGiaCustomAllInOneSEOKeyword');


 

?>