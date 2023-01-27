<?php
function MarcatSearchBuildGenreSettingsFc() {
    $post ="post";
    if(is_array(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes'))){
        $MarcatSearchBuildOptionSettingsGenrePostTypes = array_filter(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes'), "strlen");
        $MarcatSearchBuildOptionSettingsGenrePostTypes = array_values($MarcatSearchBuildOptionSettingsGenrePostTypes);
    }else {
        $MarcatSearchBuildOptionSettingsGenrePostTypes  = get_option('MarcatSearchBuildOptionSettingsGenrePostTypes');
    }
    if(is_array(get_option('MarcatSearchBuildOptionSettingsGenreNames'))){
        $MarcatSearchBuildOptionSettingsGenreNames = array_filter(get_option('MarcatSearchBuildOptionSettingsGenreNames'), "strlen");
        $MarcatSearchBuildOptionSettingsGenreNames = array_values($MarcatSearchBuildOptionSettingsGenreNames);
    }else {
        $MarcatSearchBuildOptionSettingsGenreNames      = get_option('MarcatSearchBuildOptionSettingsGenreNames');
    }
    if(is_array(get_option('MarcatSearchBuildOptionSettingsGenreSlugs'))){
        $MarcatSearchBuildOptionSettingsGenreSlugs = array_filter(get_option('MarcatSearchBuildOptionSettingsGenreSlugs'), "strlen");
        $MarcatSearchBuildOptionSettingsGenreSlugs = array_values($MarcatSearchBuildOptionSettingsGenreSlugs);
    }else {
        $MarcatSearchBuildOptionSettingsGenreSlugs  = get_option('MarcatSearchBuildOptionSettingsGenreSlugs');
    }


    if(!empty($MarcatSearchBuildOptionSettingsGenrePostTypes)):
        if(is_array($MarcatSearchBuildOptionSettingsGenreNames) or is_array($MarcatSearchBuildOptionSettingsGenreSlugs) or is_array($MarcatSearchBuildOptionSettingsGenrePostTypes)):
            foreach($MarcatSearchBuildOptionSettingsGenrePostTypes as $key => $val):
                register_taxonomy(
                    $MarcatSearchBuildOptionSettingsGenreSlugs[$key],
                    $val,
                    array(
                        'label' => __( "$MarcatSearchBuildOptionSettingsGenreNames[$key]" ),
                        'hierarchical' => true,
        				'show_in_rest'  => true,
                        'rewrite' => array( 'slug' => $MarcatSearchBuildOptionSettingsGenreSlugs[$key] ),
                        'capabilities' => array(
                            'hierarchical' => true,
                            'public'     => true,
                            'show_ui' => true,
                            'show_in_quick_edit'=> true,
                            'show_admin_column'=> true,
                            'show_in_nav_menus' => true,
                            'update_count_callback' => '_update_post_term_count',
                            'label' => '小分類登録',
                            'singular_label' => 'ジャンルの登録',
                            'public' => true,
                            'show_in_rest'  => true,
                        )                        
                    )
                );
            endforeach;
        else:
        endif;
    endif;


    ?><?php
}
add_action( 'init', 'MarcatSearchBuildGenreSettingsFc' );
function my_head() {
echo <<< EOF
<style type="text/css">
div.tabs-panel{
    max-height: 100% !important;
}
</style>
EOF;
}
add_action('admin_head', 'my_head');




    
?>