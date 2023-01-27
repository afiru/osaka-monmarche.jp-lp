<?php
//ウィジェットの項目を増やしたい場合はここで増やしていく
$wiget_header = array('header_01','header_02','header_03');
$wiget_index = array('index_01','index_02','index_03','index_04','index_05','index_06');
$wiget_sidebar = array('sidebar_01','sidebar_02','sidebar_03','sidebar_04');
$wiget_footer = array('footer_01','footer_02','footer_03','footer_04');
$wiget_footer = array('sitemap');
$wiget_array = array_merge($wiget_header,$wiget_index,$wiget_sidebar,$wiget_footer);


//ここから下は触らないで！
//ウィジェット作成
foreach($wiget_array as $key => $val) {
	register_sidebar( array(
		'name' => __( $val ),
		'id' => $val,
                'before_widget' => '<div id="%1$s" class="menu2 %2$s">',
                'after_widget' => '</div>',
                'before_title' => '<h3>',
                'after_title' => '</h3>',
		'description' => __( $val.'エリアのwidget' ),
	) );
}

add_action( 'rest_api_init', 'widget_hooks' );


//restAPI読み込み
function widget_hooks() {
    register_rest_route( 'wp/v2/', 'widget_data/', array(
        'methods' => 'GET',
        'callback' => 'get_the_widget_customizer_api',
    ) );
}
function get_the_widget_customizer_api($data) {
	global $wiget_array;
	foreach($wiget_array as $key => $val) {
		if ( is_active_sidebar( $val ) ) {
			ob_start();
			dynamic_sidebar($val);
			$datas = ob_get_clean();
			$datas = ltrim($datas);
			$rest_data[$val] = $datas;
		}
		else {
		  $rest_data[$val] = null;
		}
	}

	$response = new WP_REST_Response($rest_data);
	$response->set_status(200);

	$domain = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"];
	$response->header( 'Location', $domain );
	return $response;
}

?>