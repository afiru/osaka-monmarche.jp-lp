<?php
//カスタムフィールド画像のurlを返す
function get_thumbs_url_return($id) {
    $eye_img = wp_get_attachment_image_src( $id,'full');
    return $eye_img[0];
}
//カスタムフィールド画像のurlを出力
function get_thumbs_url($id) {
    $eye_img = wp_get_attachment_image_src( $id,full);
    echo $eye_img[0];
}
//カスタムフィールド画像のurlを出力
function get_thumbs_mini_url($id) {
    $eye_img = wp_get_attachment_image_src( $id,thumbnail);
    echo $eye_img[0];
}
//サムネイル画像のurlを取得
function get_thumbs_url_pass($id) {
    $thumbnail_id = get_post_thumbnail_id($id); 
    $eye_img = wp_get_attachment_image_src( $thumbnail_id,full);
    return $eye_img[0];
}
//サムネイル画像のurlサムネイルurlを取得
function get_thumbs_mini_url_pass($id) {
    $thumbnail_id = get_post_thumbnail_id($id); 
    $eye_img = wp_get_attachment_image_src( $thumbnail_id,thumbnail);
    echo $eye_img[0];
}
function get_thumbs_mini_url_pass_rt($id) {
    $thumbnail_id = get_post_thumbnail_id($id); 
    $eye_img = wp_get_attachment_image_src( $thumbnail_id,thumbnail);
    return $eye_img[0];
}
?>