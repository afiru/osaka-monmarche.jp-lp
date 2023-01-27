<?php

/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * 本文区切り
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
// 引  数 ／ $post_id : 対象記事ID [必須]
//           $length : 抽出バイト数 [必須]
// 返り値 ／ 記事本文より指定バイト数分抽出した文字列
function honbuntagnasi($postid, $length){
	$postsbu = backupposts();	// 現投稿情報(リスト含)のバックアップ
	// 指定投稿情報の取得
	$post = get_post($postid);
	setup_postdata($post);
	// 投稿記事文章の抽出
	$output = get_the_content();
	$output = strip_tags($output);
	$output = stripslashes($output);
	$output = preg_replace('/(\s\s|　)/','',$output);
	$output = preg_replace( "/^\xC2\xA0/", "", $output );  
	$output = str_replace("&nbsp;", '', $output);	
	$output = mb_strimwidth($output, 0, $length, "...", "UTF-8");	
	backupposts($postsbu);	// 投稿情報(リスト含)の原状復帰
	return $output;
}


/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * タイトル取得
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
// 引  数 ／ $post_id : 対象記事ID [必須]
//           $length : 抽出バイト数 [必須]
// 返り値 ／ 記事本文より指定バイト数分抽出した文字列
function titletagnasi($postid, $length){
	$postsbu = backupposts();	// 現投稿情報(リスト含)のバックアップ
	// 指定投稿情報の取得
	$post = get_post($postid);
	setup_postdata($post);
	// 投稿記事文章の抽出
	$output = get_the_title($postid);
	$output = strip_tags($output);
	$output = stripslashes($output);
	$output = preg_replace('/(\s\s|　)/','',$output);
	$output = preg_replace( "/^\xC2\xA0/", "", $output );  
	$output = str_replace("&nbsp;", '', $output);	
	$output = mb_strimwidth($output, 0, $length, "...", "UTF-8");	
	backupposts($postsbu);	// 投稿情報(リスト含)の原状復帰
	return $output;
}

/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * バックアップ
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
// 使用例：dateflug(get_the_date($post->ID); 
function backupposts($buposts = array()) {
    global $posts, $post;
    if(empty($buposts)) {
            $bu['posts'] = $posts;
            $bu['post'] = $post;
            return $bu;
    } else {
            $posts = $buposts['posts'];
            $post = $buposts['post'];
            setup_postdata($post);
    }
}
/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * カテゴリーID取得
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
function GetCategoryID( $post_ID ){
    foreach( (get_the_category( $post_ID ) ) as $obj){
        $cat_ID = $obj->category_parent;
        if( strcmp($cat_ID,'0')==0 ){
            $cat_ID = $obj->cat_ID;
        }
    }
    return $cat_ID;
}

//投稿日比較
$TODAY = strtotime(date('Y-m-d'));
function check_new_post($date) {
  global $TODAY;
  $date = strtotime($date);
  $dayDiff = abs($TODAY - $date) / 86400; //(60 * 60 * 24)
  return ($dayDiff < 14);
}
function get_new_flug($date) {
  if(check_new_post( get_post_time('Y-m-d') )) {
    echo '<span class="new"><img src="'. get_bloginfo('template_url') .'/img/icon_new.png"></span>';
  }
}
function get_new_flug_2($date) {
  if(check_new_post( get_post_time('Y-m-d') )) {
    echo '<span class="new">NEW</span>';
  }
}

?>