<?php
/*
 * ショートコード
 */
//投稿内での使用方法
//[custom_fild_temp_single post_id=記事id custom_name=カスタムリール度の記事名]
//チェックボックスなどの配列で帰る場合は、<ul></li></li></ul>となります。

require_once(dirname(__FILE__) .  '/theme_customizer/config/get_custom.php');
function custom_fild_temp_single($atts) {
    extract(shortcode_atts(array(
        'post_id' => 0,
        'custom_name' => 0,
    ), $atts));
    $custom = get_custom_field_template($post_id);
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

add_shortcode('single_custom', 'custom_fild_temp_single');

?>