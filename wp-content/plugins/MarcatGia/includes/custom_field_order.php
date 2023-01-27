<?php
//カスタムフィールドでの並び替えを可能にする
function my_filter_meta_value( $valid_vars ) {
	$valid_vars = array_merge( $valid_vars, array( 'meta_key', 'meta_value' ) );
	return $valid_vars;
}
add_filter( 'json_query_vars', 'my_filter_meta_value' );


//カスタムフィールドで送られたものを元に並び替えをするapiを作る
add_action( 'rest_api_init', 'shop_custom_orders' );
function shop_custom_orders() {
    register_rest_route( 'wp/v2/', '/custom_order/(?P<post_type>[a-zA-Z0-9-_]+)/(?P<taxonomy_name>[a-zA-Z0-9-_]+)/(?P<taxonomy_slugposts>[a-zA-Z0-9-_]+)/(?P<metakey>[a-zA-Z0-9-_]+)/(?P<order>[a-zA-Z0-9-]+)/(?P<posts_per_page>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'show_shop_listshow_item_id',
    ) );
}
function show_shop_listshow_item_id($data){
    $list[post_type] =  $data[post_type];
    $list[taxonomy_name] =  $data[taxonomy_name];
    $list[taxonomy_slugposts] =  $data[taxonomy_slugposts];
    $list[metakey] =  $data[metakey];
    $list[order] =  $data[order];
    $list[posts_per_page] =  $data[posts_per_page];
    $args = Array(
                'post_type' => $list[post_type],
            	'tax_query' => array(
            		array(
            			'taxonomy' => $list[taxonomy_name],
            			'field'    => 'slug',
            			'terms'    => $list[taxonomy_slugposts],
            		),
            	),
                'posts_per_page' => $data[posts_per_page],
                'meta_key' => $data[metakey],
                'orderby' => 'meta_value',
                'order' => $data[order]
            );
    $the_query = new WP_Query( $args );
    if ( $the_query->have_posts() ) :
        $i = 0;
        while ( $the_query->have_posts() ) : $the_query->the_post();
          $custom[$i]['title'] = get_the_title($the_query->post->ID);
          $custom[$i]['thumbs'] = get_thumbs_url_pass($the_query->post->ID);
          $custom[$i]['minithumbs'] = get_thumbs_mini_url_pass_rt($the_query->post->ID);
          $custom[$i]['link'] = get_the_permalink($the_query->post->ID);
          $cats = get_the_terms($the_query->post->ID, $list[taxonomy_name]);
          $x =0 ;
          foreach ($cats as $cat) {
              $custom[$i]['cat_id'][$x] = $cat->term_id;
              $custom[$i]['cat_name'][$x] = $cat->name;
              $custom[$i]['cat_slug'][$x] = $cat->slug;
              $custom[$i]['parent'][$x] = $cat->parent;
              $custom[$i]['cat_links'][$x] = get_term_link( $cat->slug, $list[taxonomy_name] );
              $x++;
          }
          
          //カスタムフィールド取得
          $customfdata = get_custom_field_template($the_query->post->ID);
          foreach($customfdata as $key => $val) {
              $custom[$i][$key] = $val;
          }
          $i++;
        endwhile;
    else:
        $custom = "";
    endif;
    wp_reset_postdata();
    return $custom;
}

//PHP版での取得
function get_shop_custom_loop($post_type,$taxonomy_slug,$taxonomy_genre_slug,$metakey,$order,$posts_per_page) {
    $args = Array(
                'post_type' => $post_type,
            	'tax_query' => array(
            		array(
            			'taxonomy' => $taxonomy_slug,
            			'field'    => 'slug',
            			'terms'    => $taxonomy_genre_slug,
            		),
            	),
                'posts_per_page' => $posts_per_page,
                'meta_key' => $metakey,
                'orderby' => 'meta_value',
                'order' => $order
            );
    $the_query = new WP_Query( $args );
    if ( $the_query->have_posts() ) :
        $i = 0;
        while ( $the_query->have_posts() ) : $the_query->the_post();
          $custom[$i]['title'] = get_the_title($the_query->post->ID);
          $custom[$i]['thumbs'] = get_thumbs_url_pass($the_query->post->ID);
          $custom[$i]['link'] = get_the_permalink($the_query->post->ID);
          $custom[$i]['minithumbs'] = get_thumbs_mini_url_pass_rt($the_query->post->ID);
          $cats = get_the_terms($the_query->post->ID, $taxonomy_slug);
          $x =0 ;
          foreach ($cats as $cat) {
              $custom[$i]['cat_id'][$x] = $cat->term_id;
              $custom[$i]['cat_name'][$x] = $cat->name;
              $custom[$i]['cat_slug'][$x] = $cat->slug;
              $custom[$i]['parent'][$x] = $cat->parent;
              $custom[$i]['cat_links'][$x] = get_term_link( $cat->slug, $taxonomy_slug );
              $x++;
          }
          
          //カスタムフィールド取得
          $customfdata = get_custom_field_template($the_query->post->ID);
          foreach($customfdata as $key => $val) {
              $custom[$i][$key] = $val;
          }
          $i++;
        endwhile;
    else:
        $custom = "";
    endif;
    wp_reset_postdata();
    return $custom;
}
?>