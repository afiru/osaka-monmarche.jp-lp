<?php


/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * スライダー用のカスタムフィールドを設定
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
add_action( 'init', 'slider_init' );
function slider_init() {
    $labels = array(
        'name'               => _x( 'slider', 'post type general name', 'your-plugin-textdomain' ),
        'singular_name'      => _x( 'slider', 'post type singular name', 'your-plugin-textdomain' ),
        'menu_name'          => _x( 'slider', 'your-plugin-textdomain' ),
        'name_admin_bar'     => _x( 'slider', 'add new on admin bar', 'your-plugin-textdomain' ),
        'add_new'            => _x( 'sliderを新規登録', 'blog', 'your-plugin-textdomain' ),
        'add_new_item'       => __( 'sliderを新規登録', 'your-plugin-textdomain' ),
        'new_item'           => __( 'sliderを新規登録', 'your-plugin-textdomain' ),
        'edit_item'          => __( 'sliderを編集', 'your-plugin-textdomain' ),
        'view_item'          => __( 'sliderを見る', 'your-plugin-textdomain' ),
        'all_items'          => __( 'すべてのslider', 'your-plugin-textdomain' ),
        'search_items'       => __( 'sliderを探す', 'your-plugin-textdomain' ),
        'parent_item_colon'  => __( 'Parent slider:', 'your-plugin-textdomain' ),
        'not_found'          => __( 'No books found.', 'your-plugin-textdomain' ),
        'not_found_in_trash' => __( 'No books found in Trash.', 'your-plugin-textdomain' )
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'slider' ),
        'capability_type'    => 'post',
        'show_in_rest' => true,
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 4,
        'supports'           => array( 'title','thumbnail' )
    );  
    register_post_type( 'slider', $args );
}
function create_taxonomy() {
    register_taxonomy(
        'slider_category',
        'slider',
        array(
            'show_in_rest' => true,
            'label' => __( 'スライダー区分' ),//管理画面に表示されるラベル
            'hierarchical' => true //trueだとカテゴリー、falseだとタグ
        )
    );
}
add_action( 'init', 'create_taxonomy' );
/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 * カスタムフィールドを作成
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
 */
add_action('admin_menu', 'my_meta_box');
add_action('save_post', 'my_save_post');
 
function my_meta_box () {
    add_meta_box('MyMetaBox', 'URLの設定', 'my_meta_box_menu', 'slider');
}
 
function my_meta_box_menu () {
    global $post;
 
    echo '<input type="hidden" name="MyMetaBoxMenu" value="' , wp_create_nonce(basename(__FILE__)) , '" />';
 
    $meta = get_post_meta($post->ID, 'url_setting', true);
    $tab_cheack = get_post_meta($post->ID, 'tab_cheack', true);
    echo '<p><label><input type="text" name="url_setting" id="test" value="'.$meta.'" />';
    ?>
        <p>
            <input type="radio" name="tab_cheack" value="not_blank" <?php if(!empty($tab_cheack)){ if($tab_cheack==="not_blank") { echo 'checked="checked"'; }} ?>>同一タブ
            <input type="radio" name="tab_cheack" value="_blank" <?php if(!empty($tab_cheack)){ if($tab_cheack==="_blank") { echo 'checked="checked"'; }} ?>>新規タブ
        </p>
    <?php
}
 
function my_save_post ($post_id) {
    global $post;
    if(!empty($_POST['url_setting'])) {
        // データチェック
        if (array_key_exists('post_type', $_POST) && 'page' == $_POST['url_setting']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }

 
    // カスタムフィールドに保存
    $old = get_post_meta($post_id, 'url_setting', true);
    $new = (isset($_POST['url_setting'])) ? $_POST['url_setting'] : null;
    if ($new && $new != $old) {
        update_post_meta($post_id, 'url_setting', $new);
    } elseif ('' == $new && $old) {
        delete_post_meta($post_id, 'url_setting', $old);
    }
    
    if(!empty($_POST['tab_cheack'])) {
        // データチェック
        if (array_key_exists('post_type', $_POST) && 'page' == $_POST['tab_cheack']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }

 
    // カスタムフィールドに保存
    $old = get_post_meta($post_id, 'tab_cheack', true);
    $new = (isset($_POST['tab_cheack'])) ? $_POST['tab_cheack'] : null;
    if ($new && $new != $old) {
        update_post_meta($post_id, 'tab_cheack', $new);
    } elseif ('' == $new && $old) {
        delete_post_meta($post_id, 'tab_cheack', $old);
    }
    
}

//画像削除
add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );