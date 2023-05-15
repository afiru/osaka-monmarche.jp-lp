<?php
add_filter( 'big_image_size_threshold', '__return_false' );
//jetpackで読まれているCSSを削除
add_filter('jetpack_implode_frontend_css','__return_false' );

/* インラインスタイル削除 */
function remove_recent_comments_style() {
    global $wp_widget_factory;
    remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
}
add_action( 'widgets_init', 'remove_recent_comments_style' );
add_theme_support( 'post-thumbnails' ); //サムネイルをサポートさせる。


//勝手に読み込まれるJSを削除


function dequeue_css_header() {
  wp_dequeue_style('wp-pagenavi');
  wp_dequeue_style('bodhi-svgs-attachment');
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('dashicons');
  wp_dequeue_style('addtoany');
  
}
add_action('wp_enqueue_scripts', 'dequeue_css_header');
//CSSファイルをフッターに出力
function enqueue_css_footer(){

  wp_enqueue_style('wp-block-library');

  wp_enqueue_style('addtoany');
}
add_action('wp_footer', 'enqueue_css_footer');

if(is_admin()) {    
}
else {

    function my_delete_local_jquery() {
        wp_deregister_script('jquery');
    }
    add_action( 'wp_enqueue_scripts', 'my_delete_local_jquery' );
}

//レンダリングをブロックするのを止めましょう。
if (!(is_admin() )) {
function add_defer_to_enqueue_script( $url ) {
    if ( FALSE === strpos( $url, '.js' ) ) return $url;
    if ( strpos( $url, 'jquery.min.js' ) ) return $url;
    return "$url' defer charset='UTF-8";
}
add_filter( 'clean_url', 'add_defer_to_enqueue_script', 11, 1 );
}

remove_action('wp_head','rest_output_link_wp_head');
remove_action('wp_head','wp_oembed_add_discovery_links');
remove_action('wp_head','wp_oembed_add_host_js');

//子カテゴリーも親カテゴリーと同様の設定を行う
add_filter( 'category_template', 'my_category_template' );
function my_category_template( $template ) {
    $category = get_queried_object();
    if ( $category->parent != 0 &&
        ( $template == "" || strpos( $template, "category.php" ) !== false ) ) {
        $templates = array();
        while ( $category->parent ) {
                $category = get_category( $category->parent );
                if ( !isset( $category->slug ) ) break;
                $templates[] = "category-{$category->slug}.php";
                $templates[] = "category-{$category->term_id}.php";
        }
        $templates[] = "category.php";
        $template = locate_template( $templates );
    }
    return $template;
}


//子カテゴリーで抽出を行う方法
function post_is_in_descendant_category( $cats, $_post = null ){
   foreach ( (array) $cats as $cat ) {
        $descendants = get_term_children( (int) $cat, 'category');
        if ( $descendants && in_category( $descendants, $_post ) )
        return true;
   }
   return false;
}


//アクセス数の取得
function get_post_views( $postID ) {
    $count_key = 'post_views_count';
    $count     = get_post_meta( $postID, $count_key, true );
    if ( $count == '' ) {
        delete_post_meta( $postID, $count_key );
        add_post_meta( $postID, $count_key, '0' );

        return "0 views";
    }

    return $count . '';
}

//アクセス数の保存
function set_post_views( $postID ) {
    $count_key = 'post_views_count';
    $count     = get_post_meta( $postID, $count_key, true );
    if ( $count == '' ) {
        $count = 0;
        delete_post_meta( $postID, $count_key );
        add_post_meta( $postID, $count_key, '0' );
    } else {
        $count ++;
        update_post_meta( $postID, $count_key, $count );
    }
}

function my_wp_kses_allowed_html( $tags, $context ) {
	$tags['img']['srcset'] = true;
        $tags['source']['srcset'] = true;
        $tags['source']['data-srcset'] = true;
	return $tags;
}

function my_admin_style() {
  echo '<style>
      .edit-post-visual-editor {
      display: none !important;
      }
      .edit-post-visual-editor__content-area {
      display: none !important;
      }
     .editor-styles-wrapper {
        display: none !important;
     }
  </style>'.PHP_EOL;
}
add_action('admin_print_styles', 'my_admin_style');
function get_post_thumbsdata($postID) 
{
    $thumbnail_id = get_post_thumbnail_id($postID); //アタッチメントIDの取得
    $image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
    return $image;
}


function get_post_custom_thumbsdata($thumbnail_id) 
{
    $image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
    return $image;
}
function get_scf_img_url($name) {
    $cf_sample = SCF::get($name);
    $cf_sample = wp_get_attachment_image_src($cf_sample,'full');
    return $cf_sample;
}
function get_scf_img_loop_url($name) {
    $cf_sample = wp_get_attachment_image_src($name,'full');
    return $cf_sample;
}
function get_scf_img_url_id($name,$post_id) {
    $cf_sample = SCF::get($name);
    $cf_sample = wp_get_attachment_image_src($cf_sample,'full');
    return $cf_sample;
}
function get_scf_img_loop_url_id($name) {
    $cf_sample = wp_get_attachment_image_src($name,'full');
    return $cf_sample;
}
function add_admin_css_js() {
  wp_enqueue_style( 'admin_style', get_template_directory_uri() . '/css/editostyle.css' );
}
add_action( 'admin_enqueue_scripts', 'add_admin_css_js' );