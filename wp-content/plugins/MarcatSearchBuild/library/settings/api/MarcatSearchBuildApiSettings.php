<?php
class MarcatSearchBuildApi {
    /*api_base_settings*/
    private $data_choice;
    private $data_switch;
    private $data_searchlists;
    private $data_per_pages;
    private $data_paged;
    private $clumes_settings;
    private $api_out_put_ids;
    private $output_data;
    
    public $sal;
    /*api_settings*/
    public function __construct() {
        add_action( 'rest_api_init',  array( $this, 'MarcatSearchBuildApiFc' ));
    }
    /*api_parameter_settings*/
    public function MarcatSearchBuildApiFc() {
        register_rest_route( 'wp/v2', '/marcat_search_build/', array(
            'methods' => 'GET',
            'callback' => array( $this, 'marcat_search_build_datasettings' ),
            'args' => array(
                'choice' => array(
                    'required' => true,
                    'default'=> 'and',
                    'validate_callback' => function($param, $request, $key) {                        
                        return $this->choice_check( $param );
                    },
                    'sanitize_callback' => function($param) {
                        return esc_attr( $param );
                    }
                ),
                'switch' => array(
                    'required' => true,
                    'default'=> 'and',
                    'validate_callback' => function($param, $request, $key) {                        
                        return $this->switch_check( $param );
                    },
                    'sanitize_callback' => function($param) {
                        return esc_attr( $param );
                    }
                ),
                'searchlists' => array(
                    'required' => true,
		),
                'per_pages' => array(
                    'required' => true,
                    'default'=>get_option('posts_per_page'),
                    'validate_callback' => function($param, $request, $key) { 
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => function($param) {
                        return esc_attr( $param );
                    }                    
                ),
                'paged' => array(
                    'required' => true,
                    'default'=>0,
                    'validate_callback' => function($param, $request, $key) { 
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => function($param) {
                        return esc_attr( $param );
                    }                    
                ),
            )
        ) );
    }
    /*api_parameter_validate_settings*/
    private function choice_check($param) {
        if($param === 'search' or $param === 'count'){
            return true;
        }else {
            return false;
        }
    }
    private function switch_check($param) {
        if($param === 'and' or $param === 'or'){
            return true;
        }else {
            return false;
        }
    }
    /*api_rest_set*/
    public function marcat_search_build_datasettings($data) {
        $this->data_choice = htmlspecialchars(esc_attr($data['choice']));
        $this->data_switch = htmlspecialchars(esc_attr($data['switch']));
        if(is_array($data['searchlists'])){
            foreach($data['searchlists'] as $key=>$val){
                $shin_data[$key] = htmlspecialchars(esc_attr($val));
            }
        }
        $this->data_searchlists = $shin_data;
        $this->data_per_pages = (int)htmlspecialchars(esc_attr($data['per_pages']));
        $this->data_paged = (int)htmlspecialchars(esc_attr($data['paged']));
        
        if($this->data_choice ==="search") {
            if($this->data_switch==="and"){
                $this->api_out_put_ids = $this->marcat_serch_bild_type_and();
            }elseif($this->data_switch==="or"){
                $this->api_out_put_ids = $this->marcat_serch_bild_type_or();
            }
            $outputdatas = $this->marcat_serch_bild_ouputdata_settings();
            $data = $outputdatas;
        }else {
            if($this->data_switch==="and"){
                $this->api_out_put_ids = $this->marcat_serch_bild_type_and_count();
            }elseif($this->data_switch==="or"){
                $this->api_out_put_ids = $this->marcat_serch_bild_type_or_count();
            }
            $outputdatas = $this->marcat_serch_bild_ouputdata_settings();
            $data = $outputdatas;
        }
        $response = new WP_REST_Response($data);
        //$response->set_status(200);
        //$domain = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"];
        //$response->header( 'Location', $domain );
        return $response;
    }
    /*検索設定*/
        /*api_search_type_or_settings*/
        private function marcat_serch_bild_type_or() {
            global $wpdb;
            /*or clumes settings*/
            $length = count($this->data_searchlists);
            $count = 0;
            $or_sqlsettings = "";
            if(is_array($this->data_searchlists)){
                foreach ($this->data_searchlists as $key=>$val) {
                    $count++;
                    if($count !== $length){
                        $or_sqlsettings .= "`term_taxonomy_id` IN ($val) or ";
                    }else{
                        $or_sqlsettings .= "`term_taxonomy_id` IN ($val)";                    
                    }
                }
            }
            $list = $this->marcat_serch_bild_limit_offset();
            $or_search_result = $wpdb->get_results("SELECT DISTINCT object_id FROM $wpdb->term_relationships WHERE $or_sqlsettings $list");
            if(!empty($or_search_result) and is_array($or_search_result)) {
                foreach($or_search_result as $key=>$val){
                    $out_putid[] = $or_search_result[$key]->object_id;
                }
            }else {
                $out_putid = "";
            }
            return $out_putid;
        }
        /*api_serche_type_and_settings*/
        private function marcat_serch_bild_type_and() {
            global $wpdb;               
            $as_settings ="";
            $join_setings ="";
            $whear_settings = "";
            if(is_array($this->data_searchlists)){
                foreach ($this->data_searchlists as $key=>$val) {
                    /*first*/
                    if ($val === reset($this->data_searchlists)) {
                        $as_settings .= $wpdb->term_relationships .".term_taxonomy_id AS ".$key."_terms,";

                        $whear_settings .= "WHERE ".$wpdb->term_relationships.".term_taxonomy_id IN($val) AND ";
                    }
                    /*last_settings*/
                    elseif($val === end($this->data_searchlists)){
                        $as_settings .=  $key."_table.term_taxonomy_id AS ".$key."_terms";

                        $join_setings .="LEFT JOIN $wpdb->term_relationships "."AS ".$key."_table ";
                        $join_setings .="ON ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $join_setings .="AND ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";

                        $whear_settings .= $key."_table.term_taxonomy_id IN($val) ";
                    }
                    else {
                        $as_settings .=  $key.".term_taxonomy_id AS ".$key."_terms,";

                        $join_setings .="LEFT JOIN $wpdb->term_relationships "."AS ".$key."_table ";
                        $join_setings .="ON ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $join_setings .="AND ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $whear_settings .= $key."_table.term_taxonomy_id IN($val) AND ";
                    }
                }
            }
            $and_sql_settings   ="SELECT ". $wpdb->term_relationships. ".object_id,"; 
            $from_settings      = " FROM $wpdb->term_relationships ";
            $group_settings     = "GROUP BY ".$wpdb->term_relationships.".object_id";
            $page_settings      = $this->marcat_serch_bild_limit_offset();
            $and_sql = $and_sql_settings.$as_settings.$from_settings.$join_setings.$whear_settings.$group_settings.$page_settings;
            $this->sql = $and_sql;
            $and_search_result = $wpdb->get_results($and_sql);
            $out_putid =array();
            if(!empty($and_search_result) and is_array($and_search_result)) {
                foreach($and_search_result as $key=>$val){
                    $out_putid[] = $and_search_result[$key]->object_id;
                }
            }else {
                $out_putid = "";
            }
            return $out_putid;
        }
    /*件数取得設定*/
        /*api_search_type_or_settings*/
        private function marcat_serch_bild_type_or_count() {
            global $wpdb;
            /*or clumes settings*/
            $length = count($this->data_searchlists);
            $count = 0;
            $or_sqlsettings = "";
            if(is_array($this->data_searchlists)){
                foreach ($this->data_searchlists as $key=>$val) {
                    $count++;
                    if($count !== $length){
                        $or_sqlsettings .= "`term_taxonomy_id` IN ($val) or ";
                    }else{
                        $or_sqlsettings .= "`term_taxonomy_id` IN ($val)";                    
                    }
                }
            }
            $list = $this->marcat_serch_bild_limit_offset();
            $or_search_result = $wpdb->get_results("SELECT DISTINCT object_id FROM $wpdb->term_relationships WHERE $or_sqlsettings");
            $counter = 0;
            if(!empty($or_search_result) and is_array($or_search_result)) {
                foreach($or_search_result as $key=>$val){
                    $counter++;
                }
            }else {
                $out_putid = "";
            }
            $out_putid['count'] = $counter;
            return $out_putid;
        }
        /*api_serche_type_and_settings*/
        private function marcat_serch_bild_type_and_count() {
            global $wpdb;               
            $as_settings ="";
            $join_setings ="";
            $whear_settings = "";
            if(is_array($this->data_searchlists)){
                foreach ($this->data_searchlists as $key=>$val) {
                    /*first*/
                    if ($val === reset($this->data_searchlists)) {
                        $as_settings .= $wpdb->term_relationships .".term_taxonomy_id AS ".$key."_terms,";

                        $whear_settings .= "WHERE ".$wpdb->term_relationships.".term_taxonomy_id IN($val) AND ";
                    }
                    /*last_settings*/
                    elseif($val === end($this->data_searchlists)){
                        $as_settings .=  $key."_table.term_taxonomy_id AS ".$key."_terms";

                        $join_setings .="LEFT JOIN $wpdb->term_relationships "."AS ".$key."_table ";
                        $join_setings .="ON ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $join_setings .="AND ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";

                        $whear_settings .= $key."_table.term_taxonomy_id IN($val) ";
                    }
                    else {
                        $as_settings .=  $key.".term_taxonomy_id AS ".$key."_terms,";

                        $join_setings .="LEFT JOIN $wpdb->term_relationships "."AS ".$key."_table ";
                        $join_setings .="ON ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $join_setings .="AND ".$wpdb->term_relationships.".object_id = ".$key."_table.object_id ";
                        $whear_settings .= $key."_table.term_taxonomy_id IN($val) AND ";
                    }
                }
            }
            $and_sql_settings   ="SELECT ". $wpdb->term_relationships. ".object_id,"; 
            $from_settings      = " FROM $wpdb->term_relationships ";
            $group_settings     = "GROUP BY ".$wpdb->term_relationships.".object_id";
            $page_settings      = $this->marcat_serch_bild_limit_offset();
            $and_sql = $and_sql_settings.$as_settings.$from_settings.$join_setings.$whear_settings.$group_settings;     
            
            $and_search_result = $wpdb->get_results($and_sql);
            $out_putid =array();
            $counter = 0;
            if(!empty($and_search_result) and is_array($and_search_result)) {
                foreach($and_search_result as $key=>$val){
                    $counter++;
                }
            }else {
            }
            $out_putid['count'] = $counter;
            return $out_putid;
        }
    
    /*limit_offset_settings*/
    private function marcat_serch_bild_limit_offset() {
        $this->clumes_settings="";
        /*limit_settings*/
        if(!empty($this->data_per_pages)) {
            $this->clumes_settings .= " LIMIT $this->data_per_pages";
        }
        /*page_settings→offset*/
        if(!empty($this->data_paged)) {
            $this_list = $this->data_per_pages * $this->data_paged;
            $this->clumes_settings .= " OFFSET $this_list";            
        }
        return $this->clumes_settings;
    }
    
    /*ouputdata_settings*/
    private function marcat_serch_bild_ouputdata_settings($data=0) {
        if($this->data_choice ==="search") {
            if(!empty($this->api_out_put_ids) and is_array($this->api_out_put_ids)) {
                foreach($this->api_out_put_ids as $key=>$val){
                    $out_putid[$key]['id'] = $val;
                    $out_putid[$key]['title'] = get_the_title($val);
                    $out_putid[$key]['date']['ymd'] = get_the_date( 'Y-m-d', $val );
                    $out_putid[$key]['date']['ymd_j'] = get_the_date( 'Y年m月d日', $val );
                    $week = array ( '日', '月', '火', '水', '木', '金', '土' );
                    $out_putid[$key]['date']['ymd_j_yobi'] = get_the_date( 'Y年m年d日', $val  ) . ' (' . $week[date ( 'w', get_the_date( 'U', $val ) )] . ')';
                    
                    $week = array ( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
                    $out_putid[$key]['date']['ymd_e_yobi'] = get_the_date( 'Y.m.d', $val  ) . ' ' . $week[date ( 'w', get_the_date( 'U', $val ) )] . '';
                    
                    $post = get_post( $val);
                    
                    $cats = get_the_category($val);
                    if(!empty($cats) and is_array($cats)) {
                        $i =1;
                        foreach ($cats as $cat){
                            $out_putid[$key]['category'][$cat->term_id]['term_id'] = $cat->term_id;
                            $out_putid[$key]['category'][$cat->term_id]['name'] = $cat->name;
                            $out_putid[$key]['category'][$cat->term_id]['slug'] = $cat->slug;
                            $out_putid[$key]['category'][$cat->term_id]['link'] = get_category_link($cat->term_id);
                            $out_putid[$key]['category'][$cat->term_id]['term_group'] = $cat->term_group;
                            $out_putid[$key]['category'][$cat->term_id]['term_taxonomy_id'] = $cat->term_taxonomy_id;
                            $out_putid[$key]['category'][$cat->term_id]['description'] = $cat->description;
                            $out_putid[$key]['category'][$cat->term_id]['parent'] = $cat->parent;
                            $out_putid[$key]['category'][$cat->term_id]['count'] = $cat->count;
                            $out_putid[$key]['category'][$cat->term_id]['object_id'] = $cat->object_id;
                            $out_putid[$key]['category'][$cat->term_id]['cat_ID'] = $cat->cat_ID;
                            $out_putid[$key]['category'][$cat->term_id]['category_count'] = $cat->category_count;
                            $out_putid[$key]['category'][$cat->term_id]['category_description'] = $cat->category_description;
                            $out_putid[$key]['category'][$cat->term_id]['cat_name'] = $cat->cat_name;
                            $out_putid[$key]['category'][$cat->term_id]['category_nicename'] = $cat->category_nicename;
                            $out_putid[$key]['category'][$cat->term_id]['category_parent'] = $cat->category_parent;
                            $i++;
                        }
                        
                    }
                    
                    $out_putid[$key]['contents'] = $post->post_content;
                    $contenttaguhasi = wp_strip_all_tags( $post->post_content );
                    $contenttaguhasi = remove_shortcode( $contenttaguhasi );
                    
                    $out_putid[$key]['contents_tagnashi'] = $contenttaguhasi;
                    $out_put_customs = marcatgia($val);
                    if(!empty($out_put_customs) and is_array($out_put_customs)) {
                        foreach($out_put_customs as $key2=>$val2){
                            $out_putid[$key][$key2] = $val2;
                        }
                    }
                }
            }else {
                $out_putid = "";
            }
            return $out_putid;
        }else {
            $out_putid = $this->api_out_put_ids;
            return $out_putid;
        }
    }
}

$MarcatSearchBuildApiobj = new MarcatSearchBuildApi();

