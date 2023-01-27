<?php
require_once(dirname(__FILE__) .  '../../../thumbnail_output.php');

function get_custom_field_template($postid, $keys = array(), $matrix = false){
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
    $now_posts_time = get_the_time('Y.m.d H-i-s',$postid);
    $customs['now_posts_time'] = $now_posts_time;
    //現在のカテゴリーidの取得
    $get_cat_datas = get_the_category($postid);
    $customs['cat_dates'] = $get_cat_datas;
    
    $now_cats = array();
    foreach ($get_cat_datas as $get_cat_date){
        $now_cats[] = $get_cat_date->term_id;
    }
    $now_cat_list = is_array($now_cats) ? implode(",", $now_cats) : $now_cats;
    
    $customs['cat_ids'] = $now_cat_list;
    
    //次の記事を取得する
    $query = "SELECT object_id, term_taxonomy_id , post_date ,post_status FROM $wpdb->term_relationships
    LEFT OUTER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
    WHERE $wpdb->posts.post_date < '$now_posts_time' AND $wpdb->term_relationships.term_taxonomy_id IN($now_cat_list) AND $wpdb->posts.post_status = 'publish'
    group by $wpdb->term_relationships.object_id
    ORDER BY $wpdb->posts.post_date DESC
    LIMIT 0, 1";  
    
    $queryresults = $wpdb->get_results($query, ARRAY_A);
    if(!empty($queryresults[0]) and isset($queryresults[0])) {
        $customs['next_post_id'] = $queryresults[0]['object_id'];
    }
    
    //前の記事を取得する
    $query = "SELECT object_id, term_taxonomy_id , post_date ,post_status FROM $wpdb->term_relationships
    LEFT OUTER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
    WHERE $wpdb->posts.post_date > '$now_posts_time' AND $wpdb->term_relationships.term_taxonomy_id IN($now_cat_list) AND $wpdb->posts.post_status = 'publish'
    group by $wpdb->term_relationships.object_id
    ORDER BY $wpdb->posts.post_date ASC
    LIMIT 0, 5";  
    
    $queryresults = $wpdb->get_results($query, ARRAY_A);
    if(!empty($queryresults[0]) and isset($queryresults[0])) {
        $customs['prev_post_id'] = $queryresults[0]['object_id'];
    }
    
    $customs['date_dotto'] = get_the_date('Y.m.d',$postid);
    return $customs;
}
global $custom;

?>