<?php

require_once(dirname(__FILE__) . '/custom_add_image_plugin.php');

/*■各カテゴリーに画像項目を追加■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■*/
add_action ( 'category_edit_form_fields', 'extra_category_fields');

function extra_category_fields( $tag ) {
    $t_id = $tag->term_id;
    $cat_meta = get_option( "cat_$t_id");
?>
    <tr class="form-field">
        <th><label for="SEO_Title">SEO_Title(タイトル)</label></th>
        <td><input type="text" name="Cat_meta[SEO_Title]" id="SEO_Title" size="25" value="<?php if(isset ( $cat_meta['SEO_Title'])) echo esc_html($cat_meta['SEO_Title']) ?>" /></td>
    </tr>
    <tr class="form-field">
        <th><label for="SEO_Description">SEO_Description(ディスクリプション)</label></th>
        <td><input type="text" name="Cat_meta[SEO_Description]" id="SEO_Title" size="25" value="<?php if(isset ( $cat_meta['SEO_Description'])) echo esc_html($cat_meta['SEO_Description']) ?>" /></td>
    </tr>
    <tr class="form-field">
        <th><label for="SEO_Kyeword">SEO_Kyeword(キーワード)</label></th>
        <td><input type="text" name="Cat_meta[SEO_Kyeword]" id="SEO_Title" size="25" value="<?php if(isset ( $cat_meta['SEO_Kyeword'])) echo esc_html($cat_meta['SEO_Kyeword']) ?>" /></td>
    </tr>
    <tr class="form-field">
        <th><label for="SEO_Title">OGimage(All in one SEOパッケージのog_imageをコピーして貼り付ける。)</label></th>
        <td><input type="text" name="Cat_meta[OGimage]" id="OGimage" size="25" value="<?php if(isset ( $cat_meta['OGimage'])) echo esc_html($cat_meta['OGimage']) ?>" /></td>
    </tr>
    <?php for ($count = 0; $count < 2; $count++): $cat_metakey = 'extra_text_0'.$count; ?>
    <tr class="form-field">
        <th><label for="extra_text_0<?php echo $count; ?>">1行テキスト-0<?php echo $count; ?></label></th>
        <td><input type="text" name="Cat_meta[<?php echo $cat_metakey; ?>]" id="extra_text_0<?php echo $count; ?>" size="25" value="<?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?>" /></td>
    </tr>
    <?php endfor; ?>

    <?php for ($count = 0; $count < 1; $count++): $cat_metakey = 'extra_textarea_0'.$count; ?>
    <tr class="form-field">
        <th><label for="extra_textarea_0<?php echo $count; ?>">テキストエリア-0<?php echo $count; ?></label></th>
        <td>
            <textarea id="extra_textarea_0<?php echo $count; ?>" name="Cat_meta[<?php echo $cat_metakey; ?>]"><?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?></textarea>
        </td>
    </tr>
    <?php endfor; ?>

    <?php for ($count = 0; $count < 2; $count++): $cat_metakey = 'upload_image_0'.$count; ?>
    <tr class="form-field">
        <th><label for="upload_image_0<?php echo $count; ?>">画像追加-0<?php echo $count; ?></label></th>
        <td>
            <div class="upload_image_0<?php echo $count; ?>_thumbs">
                <?php if(isset ( $cat_meta[$cat_metakey])): ?>
                    <img src="<?php echo esc_html($cat_meta[$cat_metakey]); ?>" style="margin: 1% 0; max-width: 60%; height: auto;">
                <?php endif; ?>
            </div>
            <input id="upload_image_0<?php echo $count; ?>" type="text" size="36" name="Cat_meta[<?php echo $cat_metakey; ?>]" value="<?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?>" /><br />
            画像を追加: <img src="images/media-button-other.gif" alt="画像を追加"  id="picker_img" data-thumbs_class="upload_image_0<?php echo $count; ?>_thumbs" data-input_id="upload_image_0<?php echo $count; ?>" value="Upload Image" style="cursor:pointer;" />
        </td>
    </tr>
    <?php endfor; ?>



<?php
}
add_action ( 'edited_term', 'save_extra_category_fileds');
function save_extra_category_fileds( $term_id ) {
    if ( isset( $_POST['Cat_meta'] ) ) {
       $t_id = $term_id;
       $cat_meta = get_option( "cat_$t_id");
       $cat_keys = array_keys($_POST['Cat_meta']);
          foreach ($cat_keys as $key){
          if (isset($_POST['Cat_meta'][$key])){
             $cat_meta[$key] = nl2br($_POST['Cat_meta'][$key]);
          }
       }
       update_option( "cat_$t_id", $cat_meta );
    }
}
add_action('admin_print_scripts', 'my_admin_scripts');
add_action('admin_print_styles', 'my_admin_styles');
add_action('admin_print_styles', 'taxt_reach_contents');
function taxt_reach_contents() {
    global $taxonomy;
    if( 'category' == $taxonomy ) {
        wp_enqueue_style('text_chenge_css',plugins_url() .'/MarcatGia/css/jquery.cleditor.css');
        //wp_register_script('jquery_box','//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js');
        wp_register_script('text_chenge_base_js',plugins_url() .'/MarcatGia/js/jquery.cleditor.min.js');
        wp_register_script('text_chenge_box_js',plugins_url() .'/MarcatGia/js/text_area_box.js');
        wp_enqueue_script('jquery_box');
        wp_enqueue_script('text_chenge_base_js');
        wp_enqueue_script('text_chenge_box_js');
        wp_enqueue_style('text_chenge_css');
        wp_enqueue_script( 'media-lib-uploader-js' );
    }
}
/*
 * rest-api出力
 */
add_action( 'rest_api_init','custom_category_add_img_rc');
function custom_category_add_img_rc() {
    register_rest_field(
        'category',        // post type
        'cat_meta',   // rest-apiに追加
        array(
            'get_callback'  => 
            function(  $objectdate, $field_name, $request  ) {            	
            	$catdate_list = get_cat_tax_img('category',$objectdate[ 'id' ]);
                if(!empty($catdate_list)){
                    if(is_array($catdate_list)) {
                        foreach($catdate_list as $key => $val) {
                            if(!empty($val)) {
                                $custombox[$key] = $val;
                            }
                            
                        }
                    }
                }
                //print_r($custombox);
                if(!empty($custombox)){
                    return $custombox;
                }            	
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}


function my_admin_scripts() {
    global $taxonomy;
    if( 'category' == $taxonomy ) {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('my-upload', plugins_url() .'/MarcatGia/js/upload.js');
        wp_enqueue_script('my-upload');
        wp_enqueue_script( 'media-lib-uploader-js' );
    }
}
function my_admin_styles() {
    global $taxonomy;
    if( 'category' == $taxonomy ) {
        wp_enqueue_style('thickbox');
    }
}



/*■カスタム分類に画像項目を追加■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■*/
add_action ( 'events_categories_edit_form_fields', 'extra_taxonomy_fields');
function extra_taxonomy_fields( $tag ) {
    $t_id = $tag->term_id;
    $cat_meta = get_option( "cat_$t_id");
?>
    <?php for ($count = 0; $count < 12; $count++): $cat_metakey = 'extra_text_0'.$count; ?>
    <tr class="form-field">
        <th><label for="extra_text_0<?php echo $count; ?>">1行テキスト-0<?php echo $count; ?></label></th>
        <td><input type="text" name="Cat_meta[<?php echo $cat_metakey; ?>]" id="extra_text_0<?php echo $count; ?>" size="25" value="<?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?>" /></td>
    </tr>
    <?php endfor; ?>

    <?php for ($count = 0; $count < 12; $count++): $cat_metakey = 'extra_textarea_0'.$count; ?>
    <tr class="form-field">
        <th><label for="extra_textarea_0<?php echo $count; ?>">テキストエリア-0<?php echo $count; ?></label></th>
        <td>
            <textarea id="extra_textarea_0<?php echo $count; ?>" name="Cat_meta[<?php echo $cat_metakey; ?>]"><?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?></textarea>
        </td>
    </tr>
    <?php endfor; ?>

    <?php for ($count = 0; $count < 12; $count++): $cat_metakey = 'upload_image_0'.$count; ?>
    <tr class="form-field">
        <th><label for="upload_image_0<?php echo $$count; ?>">画像追加-0<?php echo $count; ?></label></th>
        <td>
            <div class="upload_image_0<?php echo $count; ?>_thumbs">
                <?php if(isset ( $cat_meta[$cat_metakey])): ?>
                    <img src="<?php echo esc_html($cat_meta[$cat_metakey]); ?>" style="margin: 1% 0; max-width: 60%; height: auto;">
                <?php endif; ?>
            </div>
            <input id="upload_image_0<?php echo $count; ?>" type="text" size="36" name="Cat_meta[<?php echo $cat_metakey; ?>]" value="<?php if(isset ( $cat_meta[$cat_metakey])) echo esc_html($cat_meta[$cat_metakey]) ?>" /><br />
            画像を追加: <img src="images/media-button-other.gif" alt="画像を追加"  id="picker_img" data-thumbs_class="upload_image_0<?php echo $count; ?>_thumbs" data-input_id="upload_image_0<?php echo $count; ?>" value="Upload Image" style="cursor:pointer;" />
        </td>
    </tr>
    <?php endfor; ?>
<?php
}
add_action ( 'edited_term', 'save_extra_taxonomy_fields');
function save_extra_taxonomy_fields( $term_id ) {
    if ( isset( $_POST['Cat_meta'] ) ) {
       $t_id = $term_id;
       $cat_meta = get_option( "cat_$t_id");
       $cat_keys = array_keys($_POST['Cat_meta']);
          foreach ($cat_keys as $key){
          if (isset($_POST['Cat_meta'][$key])){
             $cat_meta[$key] = $_POST['Cat_meta'][$key];
          }
       }
       update_option( "cat_$t_id", $cat_meta );
    }
}
add_action('admin_print_scripts', 'my_admin_f_scripts');
add_action('admin_print_styles', 'my_admin_f_styles');

function my_admin_f_scripts() {
    global $taxonomy;
    if( 'events_categories' == $taxonomy ) {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('my-upload', plugins_url() .'/marcatgia/js/upload.js');
        wp_enqueue_script('my-upload');
    }
}


function my_admin_f_styles() {
    global $taxonomy;
    if( 'events_categories' == $taxonomy ) {
        wp_enqueue_style('thickbox');
    }
}

/*■各画像の情報を取得する■■■■■■■■■■■■■■■■■■■■■■■■■■■■■*/
function get_cat_tax_img($term,$tarmsid) {    
    $cat_data = get_option('cat_'.$tarmsid);
    return $cat_data;
}

?>