<?php

function new_walker_nav_menu_start_el($item_output){

 preg_match_all("|<a>(.*)</a>|", $item_output,$data,PREG_PATTERN_ORDER);

 for($i=0; $i<count($data[0]); $i++){

 $match_data=preg_quote($data[0][$i],'/');
 $item_output = preg_replace('/'.$match_data.'/', $data[1][$i], $item_output);
 
}

 return $item_output;

}
add_filter( 'walker_nav_menu_start_el', 'new_walker_nav_menu_start_el');

$nav_list_new_header_pc = array('header_pc_nav_01','header_pc_nav_02','header_pc_nav_03','header_pc_nav_04','header_pc_nav_05','header_pc_nav_06','header_pc_nav_07');
$nav_list_new_header_sp = array('header_sp_nav_01','header_sp_nav_02','header_sp_nav_03','header_sp_nav_04','header_sp_nav_05','header_sp_nav_06','header_sp_nav_07');
$nav_list_new_footer_pc = array('footer_pc_nav_01','footer_pc_nav_02','footer_pc_nav_03','footer_pc_nav_04','footer_pc_nav_05','footer_pc_nav_06','footer_pc_nav_07');
$nav_list_new_footer_sp = array('footer_sp_nav_01','footer_sp_nav_02','footer_sp_nav_03','footer_sp_nav_04','footer_sp_nav_05','footer_sp_nav_06','footer_sp_nav_07');
$nav_list_new_index_pc = array('index_pc_nav_01','index_pc_nav_02','index_pc_nav_03','index_pc_nav_04','index_pc_nav_05','index_pc_nav_06','index_pc_nav_07');
$nav_list_new_index_sp = array('index_sp_nav_01','index_sp_nav_02','index_sp_nav_03','index_sp_nav_04','index_sp_nav_05','index_sp_nav_06','index_sp_nav_07');
$nav_list_new_support_pc = array('support_pc_nav_01','support_pc_nav_02','support_pc_nav_03','support_pc_nav_04','support_pc_nav_05','support_pc_nav_06','support_pc_nav_07');
$nav_list_new_support_sp = array('support_sp_nav_01','support_sp_nav_02','support_sp_nav_03','support_sp_nav_04','support_sp_nav_05','support_sp_nav_06','support_sp_nav_07');
$nav_list_new_category_pc = array('category_pc_nav_01','category_pc_nav_02','category_pc_nav_03','category_pc_nav_04','category_pc_nav_05','category_pc_nav_06','category_pc_nav_07');
$nav_list_new_category_sp = array('category_sp_nav_01','category_sp_nav_02','category_sp_nav_03','category_sp_nav_04','category_sp_nav_05','category_sp_nav_06','category_sp_nav_07');

$nav_list_new = array_merge(
                    $nav_list_new_header_pc,
                    $nav_list_new_header_sp,
                    $nav_list_new_footer_pc,
                    $nav_list_new_footer_sp,
                    $nav_list_new_index_pc,
                    $nav_list_new_index_sp,
                    $nav_list_new_support_pc,
                    $nav_list_new_support_sp,
                    $nav_list_new_category_pc,
                    $nav_list_new_category_sp
                );

//メニューの設定
add_action( 'after_setup_theme', 'register_my_menu' );
function register_my_menu() {
    global $nav_list_new;
    foreach ($nav_list_new as $key => $val) {
        register_nav_menu( $val, __( $val, 'theme-slug' ) );
    }
}
//メニューのクラスを削除していく


//ナビのコンフィグを読み込み
require_once(dirname(__FILE__) . '/config/nav_array.php');

add_action( 'rest_api_init', 'nav_hooks' );
function nav_hooks() {
    register_rest_route( 'wp/v2/', 'nav_data/link_not_empty/', array(
        'methods' => 'GET',
        'callback' => 'get_the_nav_customizer',
    ) );
}
function get_the_nav_customizer() {    
    global $nav_list_new;
    $outputlist =array();
    foreach($nav_list_new as $key => $val){
        $menuid = wp_get_nav_menu_object( $val );
        if(!empty($menuid)) {
            $menu_items = wp_get_nav_menu_items($menuid->term_id);
            if(!empty($menu_items)) {
                $outputlist[$val] = '<ul id="menu-' . $val . '">';
                $count = 0;
                $submenu = false;
                foreach( $menu_items as $menu_item ) {
                    $link = $menu_item->url;
                    $title = $menu_item->title;
                    if ( !$menu_item->menu_item_parent ) {
                        $parent_id = $menu_item->ID;
                        if($menu_item->object=="category") {
                            $cat = get_category($menu_item->object_id);
                            $cat_slug = $cat->slug;
                        }elseif($menu_item->object=="page"){
                            $cat_slug = get_page_uri($menu_item->object_id);
                        }elseif($menu_item->object=="event_news"){
                            $cat_slug = 'event_news';
                        }elseif($menu_item->object=="shop"){
                            $cat_slug = 'shop';
                        }else {
                            if($link == home_url()) {
                                $cat_slug = "top";
                            }
                            $cat_slug = "";
                        }
                        $addclass = "";
                        $outputlist[$val] .= '<li id="nav_cheack_'. $cat_slug .'" class="'.$addclass.'　'. $cat_slug .'"><a id="page_chenge" href="'.$link.'" data-pagetype="'.$menu_item->object.'" data-slug="'. $cat_slug .'" data-number="'.$menu_item->object_id.'" target="'.$menu_item->target.'"><span class="icon_arrow">' ."\n";
                        $outputlist[$val] .= ''.$title.'' ."</a></span>\n";
                    }
                    if ( $parent_id == $menu_item->menu_item_parent ) {
                        if ( !$submenu ) {
                            $submenu = true;
                            $outputlist[$val] .= '<ul class="sub-menu">' ."\n";
                        }
                        if($menu_item->object=="category") {
                            $cat = get_category($menu_item->object_id);
                            $cat = get_category($cat->category_parent);
                            $cat_slug = $cat->slug;
                        }elseif($menu_item->object=="page"){
                            $cat_slug = get_page_uri($menu_item->object_id);
                        }else {
                            $cat_slug = "";
                        }
                        $outputlist[$val] .= '<li id="nav_cheack_'. $cat_slug .'" class="'.$addclass.'"><a href="'.$link.'" id="page_chenge" data-pagetype="'.$menu_item->object.'" data-slug="'. $cat_slug .'" data-number="'.$menu_item->object_id.'" target="'.$menu_item->target.'"><span class="icon_arrow">' ."\n";
                        $outputlist[$val] .= ''.$title.'' ."</a></span></li>\n";
                        if ( $menu_items[ $count + 1 ]->menu_item_parent != $parent_id && $submenu ){
                            $outputlist[$val] .= '</ul></li>' ."\n";
                            $submenu = false;
                        }
                        if ( $menu_items[ $count + 1 ]->menu_item_parent != $parent_id ) { 
                            $outputlist[$val] .= '</li>' ."\n";      
                            $submenu = false;
                        }                        
                    }
                    $count++;
                }
                $outputlist[$val] .= '</ul>';
            }
        }
    }
    return $outputlist;
}



?>