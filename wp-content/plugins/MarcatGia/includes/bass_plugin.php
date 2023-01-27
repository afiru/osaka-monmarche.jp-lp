<?php
//タイトルにタグなどが入らないようにする
function titletagnasi_rest($postid){
	$postsbu = backupposts_rest();	// 現投稿情報(リスト含)のバックアップ
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
	backupposts_rest($postsbu);	// 投稿情報(リスト含)の原状復帰
	return $output;
}

function honbuntagnasi_rest($postid, $length){
	$postsbu = backupposts_rest();	// 現投稿情報(リスト含)のバックアップ
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
	backupposts_rest($postsbu);	// 投稿情報(リスト含)の原状復帰
	return $output;
}

function excerpttagnasi_rest($postid, $length){
	$postsbu = backupposts_rest();	// 現投稿情報(リスト含)のバックアップ
	// 指定投稿情報の取得
	$post = get_post($postid);
	setup_postdata($post);
	// 投稿記事文章の抽出
	$output = get_the_excerpt();
	$output = strip_tags($output);
	$output = stripslashes($output);
	$output = preg_replace('/(\s\s|　)/','',$output);
	$output = preg_replace( "/^\xC2\xA0/", "", $output );  
	$output = str_replace("&nbsp;", '', $output);	
	$output = mb_strimwidth($output, 0, $length, "...", "UTF-8");	
	backupposts_rest($postsbu);	// 投稿情報(リスト含)の原状復帰
	return $output;
}
add_filter('excerpttagnasi_rest', 'new_excerpt_more');

function backupposts_rest($buposts = array()) {
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
?>