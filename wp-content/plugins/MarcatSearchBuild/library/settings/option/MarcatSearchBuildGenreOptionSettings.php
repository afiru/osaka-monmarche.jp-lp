<?php

add_action('admin_menu', 'MarcatSearchBuildOptionOutlineSettingsFc');
function MarcatSearchBuildOptionOutlineSettingsFc() {
    //create new top-level menu
    add_menu_page('カスタム分類の設定', '分類の設定', 'administrator', __FILE__, 'MarcatSearchBuildOptionSettingPage',plugins_url('/img/icon.png', dirname(__FILE__,3) ));
    //call register settings function
    add_action( 'admin_init', 'MarcatSearchBuildOptionSettingsFc' );
}
function MarcatSearchBuildOptionSettingsFc() {
    register_setting( 'MarcatSearchBuildOptionSettings', 'MarcatSearchBuildOptionSettingsGenreNames' );
    register_setting( 'MarcatSearchBuildOptionSettings', 'MarcatSearchBuildOptionSettingsGenreSlugs' );
    register_setting( 'MarcatSearchBuildOptionSettings', 'MarcatSearchBuildOptionSettingsGenrePostTypes' );
}
function MarcatSearchBuildOptionSettingPage() {
    ?>
    <style>
        .MarcatSearchBuildOptionWrap h2 {
            font-size: 25px;
            padding: 15px 0;
            border-bottom: 2px solid #000;
            text-align: center;
        }
        .MarcatSearchBuildOptionWrap h3 {
            font-size: 20px;
            padding: 15px 0 15px 10px;
            margin: 25px 0 10px 0;
            border-bottom: 1px solid #CCC;
            border-top: 1px solid #CCC;
            background: #FFF;
        }
        .MarcatSearchBuildOptionWrap table {
            table-layout: fixed;
            width: 100%;
        }
        .MarcatSearchBuildOptionWrap table th:first-child {
            width: 5%;
        }
        .MarcatSearchBuildOptionWrap table th {
            width: 20%;
            vertical-align: middle;
        }
        .MarcatSearchBuildOptionWrap table td {
            width: 30%;
            vertical-align: middle;
        }
        .MarcatSearchBuildOptionWrap table td input,select {
            width: 100%;
            padding: 10px;
        }
        .MarcatSearchBuildOptionSettingsWapper {
            text-align: right;
            margin: 0 0 2% 0;
        }
        .MarcatSearchBuildOptionSettingsWapper .MarcatSearchBuildOptionSettingsButton {
            background: #0085ba;
            border-color: #0073aa #006799 #006799;
            box-shadow: 0 1px 0 #006799;
            color: #fff;
            text-decoration: none;
            text-shadow: 0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 26px;
            height: 28px;
            margin: 0;
            padding: 0 10px 1px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            -webkit-appearance: none;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        
        .MarcatSearchBuildOptionWrap .text_center {
            text-align: center;
        }
        .MarcatSearchBuildOptionWrap .bigfont {
            text-align: center;
            font-size: 20px;
        }
        .MarcatSearchBuildOptionWrap .bigfont a {
            text-decoration: none;
            color: #000;
        }
        
        .MarcatSearchBuildOptionApiWapper {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .MarcatSearchBuildOptionApiWapper th {
            padding: 1%;
            border: 1px solid #CCC;
            border-collapse: collapse;
        }
        .MarcatSearchBuildOptionApiWapper td {
            padding: 1%;
            border: 1px solid #CCC;
            border-collapse: collapse;
            background: #FFF;
        }
        .MarcatSearchBuildOptionApiWapper .parameter {
            width: 15% !important;
        }
        .MarcatSearchBuildOptionApiWapper .required {
            width: 10%;
        }
        .MarcatSearchBuildOptionApiWapper .type {
            width: 15%;
        }
        .MarcatSearchBuildOptionApiWapper .description {
            width: 50%;
        }
        .MarcatSearchBuildOptionApiWapper .example {
            width: 30%;
        }
        .MarcatSearchBuildOptionApiWapper th {
            text-align: center;
            color: #FFF;
            background: #0085ba;
        }
        .MarcatSearchBuildOptionApiWapper th {
            text-align: center;
            color: #FFF;
            background: #0085ba;
        }
    </style>
    <script>
        jQuery(function($){
            $('.MarcatSearchBuildOptionSettingsButton').click(function(){
                $('#MarcatSearchBuildOptionSettingsTable').append(
                '<tr valign="top">\n\
                    <th scope="row">分類名</th>\n\
                    <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreNames[]" value="" /></td>\n\
                    <th scope="row">分類スラッグ名</th>\n\
                    <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreSlugs[]" value="" /></td>\n\
                    <th scope="row">PostType設定</th>\n\
                    <td>\n\
                        <select name="MarcatSearchBuildOptionSettingsGenrePostTypes[]" size="">\n\
                            <option value="">--選択して下さい--</option><option value="post" >post</option><?php  $args = array('public'   => true,'_builtin' => false);  $output = 'names';  $operator = 'and';  $allpost_types = get_post_types( $args, $output, $operator ); foreach ( $allpost_types  as $allpost_type ) { ?><option value="<?php echo $allpost_type; ?>"><?php echo esc_html($allpost_type); ?></option><?php } ?></select></td></tr>'
                ).hide().fadeIn(500);
            });
            $('#section_deleat').click(function(){
                console.log($("[name=delete]:checked").val());
            });
        });
    </script>
    <div class="MarcatSearchBuildOptionWrap">
        <h2>カスタム分類の設定</h2>
        <p>サーチで使用するカスタム分類の設定を行います。</p>
        <form method="post" action="options.php" id="MarcatSearchBuildOptionSettingForm">
            <?php settings_fields( 'MarcatSearchBuildOptionSettings' ); ?>
            <?php do_settings_sections( 'MarcatSearchBuildOptionSettingsFc' ); ?>
            <?php
                //配列の中の空要素を削除する
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
                
            ?>
            <div class="wappers">
                <section class="custom_field_GPS_input_api_setting">
                    <table class="form-table" id="MarcatSearchBuildOptionSettingsTable">
                        <?php if(!empty($MarcatSearchBuildOptionSettingsGenrePostTypes)): ?>
                            <?php if(is_array($MarcatSearchBuildOptionSettingsGenreNames) or is_array($MarcatSearchBuildOptionSettingsGenreSlugs) or is_array($MarcatSearchBuildOptionSettingsGenrePostTypes)): ?>
                                <?php foreach($MarcatSearchBuildOptionSettingsGenrePostTypes as $key => $val): ?>

                                    <tr valign="top">

                                        <th scope="row">分類名</th>
                                        <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreNames[]" value="<?php echo get_option('MarcatSearchBuildOptionSettingsGenreNames')[$key]; ?>" /></td>
                                        <th scope="row">分類スラッグ名</th>
                                        <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreSlugs[]" value="<?php echo get_option('MarcatSearchBuildOptionSettingsGenreSlugs')[$key]; ?>" /></td>
                                        <th scope="row">PostType設定</th>
                                        <td>
                                            <select name="MarcatSearchBuildOptionSettingsGenrePostTypes[]" size="">
                                                <option value="">--選択して下さい--</option>
                                                <option value="post" <?php if(!empty($val)){ if($val==='post'){ echo 'selected'; } } ?>>
                                                    post
                                                </option>
                                                <?php
                                                    $args = array(
                                                       'public'   => true,
                                                       '_builtin' => false
                                                    );
                                                    $output = 'names'; // names or objects, note names is the default
                                                    $operator = 'and'; // 'and' or 'or'
                                                    $allpost_types = get_post_types( $args, $output, $operator ); 
                                                    foreach ( $allpost_types  as $allpost_type ) { ?>
                                                        <option value="<?php echo $allpost_type; ?>" <?php if($val===$allpost_type){ echo 'selected'; } ?>>
                                                            <?php echo esc_html($allpost_type); ?>
                                                        </option>
                                                    <?php }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>  
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr valign="top">

                                    <th scope="row">分類名</th>
                                    <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreNames[]" value="<?php echo esc_url(get_option('MarcatSearchBuildOptionSettingsGenreNames')); ?>" /></td>
                                    <th scope="row">分類スラッグ名</th>
                                    <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreSlugs[]" value="<?php echo esc_url(get_option('MarcatSearchBuildOptionSettingsGenreSlugs')); ?>" /></td>
                                    <th scope="row">PostType設定</th>
                                    <td>
                                        <select name="MarcatSearchBuildOptionSettingsGenrePostTypes[]" size="">
                                            <option value="">--選択して下さい--</option>
                                            <option value="post" <?php if(!empty(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes'))){ if(esc_url(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes'))==='post'){ echo 'selected'; } } ?>>
                                                post
                                            </option>
                                            <?php
                                                $args = array(
                                                   'public'   => true,
                                                   '_builtin' => false
                                                );
                                                $output = 'names'; // names or objects, note names is the default
                                                $operator = 'and'; // 'and' or 'or'
                                                $allpost_types = get_post_types( $args, $output, $operator ); 
                                                foreach ( $allpost_types  as $allpost_type ) { ?>
                                                    <option value="<?php echo $allpost_type; ?>">
                                                        <?php echo esc_html($allpost_type); ?>
                                                    </option>
                                                <?php }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr valign="top">
                                <th scope="row">分類名</th>
                                <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreNames[]" value="" /></td>
                                <th scope="row">分類スラッグ名</th>
                                <td><input type="text" name="MarcatSearchBuildOptionSettingsGenreSlugs[]" value="" /></td>
                                <th scope="row">PostType設定</th>
                                <td>
                                    <select name="MarcatSearchBuildOptionSettingsGenrePostTypes[]" size="">
                                        <option value="">--選択して下さい--</option>
                                        <option value="post" <?php if(!empty(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes'))){ if(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes')==='post'){ echo 'selected'; } } ?>>
                                            post
                                        </option>
                                        <?php
                                            $args = array(
                                               'public'   => true,
                                               '_builtin' => false
                                            );
                                            $output = 'names'; // names or objects, note names is the default
                                            $operator = 'and'; // 'and' or 'or'
                                            $allpost_types = get_post_types( $args, $output, $operator ); 
                                            foreach ( $allpost_types  as $allpost_type ) { ?>
                                                <option value="<?php echo $allpost_type; ?>" <?php if(get_option('MarcatSearchBuildOptionSettingsGenrePostTypes')===$allpost_type){ echo 'selected'; } ?>>
                                                    <?php echo esc_html($allpost_type); ?>
                                                </option>
                                            <?php }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    <div class="MarcatSearchBuildOptionSettingsWapper">
                        <span class="MarcatSearchBuildOptionSettingsButton">入力項目を追加する</span>
                    </div>

                </section>
            </div> 
            <?php submit_button(); ?>
        </form>
    </div>
    <div class="MarcatSearchBuildOptionWrap">
        <h2>APIの解説</h2>
        <p class="text_center">Rest-Api時の解説です。こちらを参考にカテゴリーのand検索やor検索のパラメータの作成を行って下さい。</p>
        <p class="bigfont">
            リクエストURL：<a href="<?php echo home_url('/wp-json/wp/v2/marcat_search_build/'); ?>"><?php echo home_url('/wp-json/wp/v2/marcat_search_build/'); ?></a>
        </p>
        <h3>リクエストパラメーター</h3>
        <table class="MarcatSearchBuildOptionApiWapper">
            <tr>
                <th class="parameter">パラメータ</th>
                <th class="required">必須</th>
                <th class="type">型</th>
                <th class="description">説明</th>
                <th class="example">入力例</th>
            </tr>
            <tr>
                <td class="parameter">choice</td>
                <td class="required">必須</td>
                <td class="type">文字列<br>(search/count)のみ入力。</td>
                <td class="description">検索を行うのか、カウントの取得を行うのかの調査</td>
                <td class="example">?choice=search</td>
            </tr>
            <tr>
                <td class="parameter">switch</td>
                <td class="required">必須</td>
                <td class="type">文字列<br>(and/or)のみ入力。</td>
                <td class="description">且つ検索かまたは検索の設定。「and」「or」どちらかを入力して下さい。</td>
                <td class="example">&switch=and</td>
            </tr>
            <tr>
                <td class="parameter">searchlists</td>
                <td class="required">必須</td>
                <td class="type">多次元配列</td>
                <td class="description">
                    choiceで設定をした分類名に関する、抽出条件を入力して下さい。<br>
                    入力方法は、$searchlists[choiceで設定した分類名（分類スラッグ名）]&$searchlists[choiceで設定した分類名（分類スラッグ名）]…
                    ※必ず、choiceで設定した数分入力して下さい。
                </td>
                <td class="example">
                    &$searchlists[choiceで設定した分類名（分類スラッグ名）]=1,8,6&$searchlists[choiceで設定した分類名（分類スラッグ名）]=6,8,7
                </td>
            </tr>
            <tr>
                <td class="parameter">per_pages</td>
                <td class="required">任意</td>
                <td class="type">数値</td>
                <td class="description">
                    1ページあたりの表示数の設定を入力して下さい。<br>
                    何も設定していない場合は、設定→表示設定→1ページに表示する最大投稿数　にて設定している件数となります。
                </td>
                <td class="example">&per_pages=10</td>
            </tr>
            <tr>
                <td class="parameter">paged</td>
                <td class="required">任意</td>
                <td class="type">数値</td>
                <td class="description">
                    表示するページ数になります。<br>
                    何も設定していない場合は0となり、1ページ目を表示するようになります。
                </td>
                <td class="example">&paged=0</td>
            </tr>
        </table>
        
        <h3>レスポンスフィールド</h3>
        <table class="MarcatSearchBuildOptionApiWapper">
            <tr>
                <th class="parameter">フィールド</th>
                <th class="type">型</th>  
                <th class="description">説明</th>                              
                <th class="example">例</th>
            </tr>
            <tr>
                <td class="parameter">id</td>
                <td class="type">半角英数字</td>  
                <td class="description">WordPress記事ID</td>                              
                <td class="example">1</td>
            </tr>
            <tr>
                <td class="parameter">title</td>
                <td class="type">文字列</td>  
                <td class="description">タイトルの出力（HTMLをタイトルに入力している場合はそのまま出力されます）</td>                              
                <td class="example">テストタイトル</td>
            </tr>
            <tr>
                <td class="parameter">date</td>
                <td class="type">配列</td>  
                <td class="description">
                    日付になります。
                    <br>date['ymd']→"0000-00-00"
                    <br>date['ymd_j']→"0000年00月00日"
                    <br>date['ymd_j_yobi']→"0000年00年00日 (曜日※例を参照)"
                    <br>date['ymd_j_yobi']→"0000.00.00 曜日※例を参照"
                </td>                              
                <td class="example">
                    date['ymd']→"2018-03-26"
                    <br>date['ymd_j']→"2018年03月26日"
                    <br>date['ymd_j_yobi']→"2018年03年26日 (月)"
                    <br>date['ymd_j_yobi']→"2018.03.26 Mon"
                </td>
            </tr>
            <tr>
                <td class="parameter">contents</td>
                <td class="type">本文<br>ショートコード・HTML含み</td>  
                <td class="description">ショートコード・HTMLを含んだ記事本文</td>                              
                <td class="example">テキストテキストテキストテキストテキストテキストテキストテキストテキスト</td>
            </tr>
            <tr>
                <td class="parameter">contents_tagnashi</td>
                <td class="type">本文<br>ショートコード・HTML無し</td>  
                <td class="description">ショートコード・HTMLを含まない記事本文</td>                              
                <td class="example">テキストテキストテキストテキストテキストテキストテキストテキストテキスト</td>
            </tr>
            <tr>
                <td class="parameter">カスタムフィールド名</td>
                <td class="type">文字列</td>  
                <td class="description">記事で登録したカスタムフィールドの内容が出力されます。</td>                              
                <td class="example">Temper Level: "Low"</td>
            </tr>
            <tr>
                <td class="parameter">post_thumbs_list</td>
                <td class="type">URL</td>  
                <td class="description">記事で登録したアイキャッチ画像のURLが出力されます。</td>                              
                <td class="example"><?php echo home_url(); ?>/wp-content/uploads/2018/01/good_on.png</td>
            </tr>
        </table>
    </div>
<?php
}
?>