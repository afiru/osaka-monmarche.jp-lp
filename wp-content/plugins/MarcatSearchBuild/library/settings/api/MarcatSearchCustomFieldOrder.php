<?php
//カテゴリーのand検索機能を実装（API側）
add_action( 'rest_api_init', 'categoies_and_search' );
function categoies_and_search() {
    register_rest_route( 'wp/v2/', '/categoies_and_search/(?P<cat_ids>[a-zA-Z0-9-_,]+)/(?P<metakey>[a-zA-Z0-9-_]+)/(?P<orderby>[a-zA-Z0-9-]+)/(?P<order>[a-zA-Z0-9-]+)/(?P<posts_per_page>[0-9]+)/(?P<paged>[0-9]+)/', array(
        'methods' => 'GET',
        'callback' => 'categoies_and_search_func',
    ) );
}
//restの処理
function categoies_and_search_func($data) {
    $cat_ids = $data['cat_ids'];
    if($data['metakey'] === 'null' ) {
        $metakey = ""; 
    }else{
        $metakey = $data['metakey']; 
    }
    $orderby = $data['orderby'];
    $order = $data['order'];
    $posts_per_pager = $data['posts_per_pager'];
    $paged = $data['paged'];

    $pagedeta = categoies_and_serch_replace($cat_ids,$metakey,$orderby,$posts_per_pager,$paged);
    return $pagedeta;
    
}

//取得の方法
function categoies_and_serch_replace($cat_ids = '', $metakey = '',$orderby = '',$order = '',$posts_per_page = '',$page = 0){
    if(!empty($metakey)) {
        $args = Array(
            'post_type' => 'post',
            'category__and' => array( $cat_ids ),
            'posts_per_page' => $posts_per_page,
            'meta_key' => $metakey,
            'orderby' => 'meta_value',
            'order' => $order,
            'paged' => $page
        );
    }else {
        $args = Array(
            'post_type' => 'post',
            'category__and' => array( $cat_ids ),
            'posts_per_page' => $posts_per_page,
            'orderby' => 'date',
            'order' => $order,
            'paged' => $page
        );
    }

    $the_query = new WP_Query( $args );
    if ( $the_query->have_posts() ) :
        $custom['count'] = $the_query->found_posts;
        $i = 0;
        while ( $the_query->have_posts() ) : $the_query->the_post();
          $custom[$i]['title']['nomal'] = get_the_title($the_query->post->ID);
          $custom[$i]['title']['tagnone'] = titletagnasi_rest($the_query->post->ID);
          
          $custom[$i]['contents'] = get_the_content();
          $custom[$i]['contents'] = apply_filters( 'the_content', $custom[$i]['contents'] );
          
          $custom[$i]['excerpt']['nomal'] = get_the_title($the_query->post->ID);
          $custom[$i]['excerpt']['tagnone'] = honbuntagnasi_rest($the_query->post->ID,60);
          
          $custom[$i]['thumbs'] = get_thumbs_url_pass($the_query->post->ID);
          $custom[$i]['link'] = get_the_permalink($the_query->post->ID);
          $custom[$i]['minithumbs'] = get_thumbs_mini_url_pass_rt($the_query->post->ID);
          
          
          $cats = get_the_category();
          $x =0 ;
          foreach ($cats as $cat) {
              $custom[$i]['categories']['cat_id'][$x] = $cat->term_id;
              $custom[$i]['categories']['cat_name'][$x] = $cat->name;
              $custom[$i]['categories']['cat_slug'][$x] = $cat->slug;
              $custom[$i]['categories']['parent'][$x] = $cat->parent;
              $custom[$i]['categories']['cat_links'][$x] = get_category_link( $cat->term_id );
              $x++;
          }
          
          //カスタムフィールド取得
          $customfdata = get_custom_field_template($the_query->post->ID);
          foreach($customfdata as $key => $val) {
              $custom[$i]['post_meta'][$key] = $val;
          }
          $i++;
        endwhile;
    else:
        $custom['count'] = '0';
    endif;
    wp_reset_postdata();
    return $custom;
}

?>