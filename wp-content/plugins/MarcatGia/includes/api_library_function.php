<?php

require_once(dirname(__FILE__) .  '/theme_customizer/config/get_custom.php');

//api出力設定
add_action( 'rest_api_init','custom_read_fc');
function custom_read_fc() {
    register_rest_field(
        'post',        // post type
        'post_meta',   // rest-apiに追加するキー
        array(
            'get_callback'  => 
            function(  $objectdate, $field_name, $request  ) {            	
            	$meta = get_custom_field_template($objectdate[ 'id' ]);
            	return $meta;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

add_action( 'rest_api_init','custom_read_fcpage');
function custom_read_fcpage() {
    register_rest_field(
        'page',        // post type
        'post_meta',   // rest-apiに追加するキー
        array(
            'get_callback'  => 
            function(  $objectdate, $field_name, $request  ) {            	
            	$meta = get_custom_field_template($objectdate[ 'id' ]);
            	return $meta;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

add_action( 'rest_api_init','custom_read_fcslider');
function custom_read_fcslider() {
    register_rest_field(
        'slider',        // post type
        'post_meta',   // rest-apiに追加するキー
        array(
            'get_callback'  => 
            function(  $objectdate, $field_name, $request  ) {            	
            	$meta = get_custom_field_template($objectdate[ 'id' ]);
            	return $meta;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}


add_action( 'rest_api_init','custom_read_fc_event');
function custom_read_fc_event() {
    register_rest_field(
        'ai1ec_event',        // post type
        'post_meta',   // rest-apiに追加するキー
        array(
            'get_callback'  => 
            function(  $objectdate, $field_name, $request  ) {            	
            	$meta = get_custom_field_template($objectdate[ 'id' ]);
            	return $meta;
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}


?>