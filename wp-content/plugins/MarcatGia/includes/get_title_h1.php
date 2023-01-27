<?php


/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * SEO基準設定
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
//タイトル指定
function get_the_site_title() {    
    if(is_home()){
        $title = get_bloginfo('name');
    }elseif(is_page()) {
        $title = get_the_title(get_the_ID()).'|'.get_bloginfo('name');
    }elseif(is_single()) {
        if(is_singular( 'post' )) {
            $title = get_the_title(get_the_ID()).'|'.get_bloginfo('name');
        }else {
            $title = get_the_title(get_the_ID()).'|'.get_post_type_object(get_post_type())->name.'|'.get_bloginfo('name');
        }
    }elseif (is_category()) {
        $title = single_cat_title("", false).'|'.get_bloginfo('name');
    }elseif(is_tag()){
        $title = single_tag_title("", false).'|'.get_bloginfo('name');
    }elseif(is_archive()){
           $post_type = get_query_var('post_type');
           if($post_type=='post'){
               $title = '記事一覧|'.get_bloginfo('name');
           }else {
               $posttypeoj = get_post_type_object($post_type);
               $title = $posttypeoj->label.'一覧|'.get_bloginfo('name');
           }           
    }elseif(is_tax()) {
        $title = single_term_title("", false).'|'.get_bloginfo('name');
    }elseif(is_404()) {
        $title = '404エラーページ|'.get_bloginfo('name');
    }elseif(is_search()) {
        $title = get_search_query().'での検索ページ|'.get_bloginfo('name');
    }else {
        $title = get_bloginfo('name');
    }
    return $title;
}

//h1
function get_the_h1_name($defaultname) {    
    if(is_home()){
        $title = $defaultname;
    }elseif(is_page()) {
        $title = get_the_title(get_the_ID()).'|'.$defaultname;
    }elseif(is_single()) {
        if(is_singular( 'post' )) {
            $title = get_the_title(get_the_ID()).'|'.$defaultname;
        }else {
            $title = get_the_title(get_the_ID()).'|'.get_post_type_object(get_post_type())->name.'|'.$defaultname;
        }
    }elseif (is_category()) {
        $title = single_cat_title("", false).'|'.$defaultname;
    }elseif(is_tag()){
        $title = single_tag_title("", false).'|'.$defaultname;
    }elseif(is_archive()){
           $post_type = get_query_var('post_type');
           if($post_type=='post'){
               $title = '記事一覧|'.$defaultname;
           }else {
               $posttypeoj = get_post_type_object($post_type);
               $title = $posttypeoj->label.'一覧|'.$defaultname;
           }           
    }elseif(is_tax()) {
        $title = single_term_title("", false).'|'.$defaultname;
    }elseif(is_404()) {
        $title = '404エラーページ|'.get_bloginfo('name');
    }elseif(is_search()) {
        $title = get_search_query().'での検索ページ|'.$defaultname;
    }else {
        $title = $defaultname;
    }
    return $title;
}

?>