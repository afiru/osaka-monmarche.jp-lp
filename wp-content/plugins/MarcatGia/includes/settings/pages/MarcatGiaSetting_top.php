<?php
function MarcatGiaSettingStyle(){
    wp_enqueue_style( 'my_admin_style', plugins_url().'/MarcatGia/css/MarcatGiaSettingsConfig.css');
}
add_action( 'admin_enqueue_scripts', 'MarcatGiaSettingStyle' );
function MarcatGiaSettingTop() {
?>
<div class="MarcatGiaSettingWapper">
    <h2>MarcatGiaについて</h2>
    <p>メニューやウィジェットやカスタマイザーの入力項目増やしたり、カテゴリーの編集画面に入力項目を追加することができます。</p>

    <?php require_once(dirname(__FILE__) . '/MarcatGiaSettingNav.php');//ナビデータの呼び込み ?>
    
    <section class="MarcatGiaSettingFunction_sec">
        <h3>製品検証記録</h3>
        <table class="MarcatGiaSettingFunctionKiroku">
            <tr>
                <th class="MarcatGiaSettingFunctionKiroku_th">最新チェック済み<br>WordPressバージョン</th>
                <td>4.7.2</td>
            </tr>
            <tr>
                <th class="MarcatGiaSettingFunctionKiroku_th">最終更新日</th>
                <td>2018/03/28</td>
            </tr>
            <tr>
                <th class="MarcatGiaSettingFunctionKiroku_th">最終更新日</th>
                <td>2018/03/28</td>
            </tr>
        </table>
    </section>
    
    <section class="MarcatGiaSettingFunction_sec">
        <h3>主な機能</h3>
        <ul class="MarcatGiaSettingFunction">
            <li>・メニューの拡張（メニュー登録時のチェックボックス数の調整）</li>
            <li>・ウィジェットの拡張（ウィジェット登録時のウィジェットエリア数の調整）</li>
            <li>・カスタマイザーの拡張（コード入力無く、カスタマイザーで作成する項目を作ることができます。）</li>
            <li>・カテゴリー編集画面の拡張（テキスト入力欄・テキストエリア・画像追加の調整）</li>
        </p>
    </section>

    
    <section class="MarcatGiaSettingFunction_sec">
        <h3>よくあるご質問</h3>
        <ul class="MarcatGiaSettingFunction">
            <li>・メニューの拡張（メニュー登録時のチェックボックス数の調整）</li>
            <li>・ウィジェットの拡張（ウィジェット登録時のウィジェットエリア数の調整）</li>
            <li>・カスタマイザーの拡張（コード入力無く、カスタマイザーで作成する項目を作ることができます。）</li>
            <li>・カテゴリー編集画面の拡張（テキスト入力欄・テキストエリア・画像追加の調整）</li>
        </p>
    </section>
    

    <section class="MarcatGiaSettingFunction_sec">
        <h3>変更履歴</h3>
        <ul class="MarcatGiaSettingHistory">
            <li>
                <h4>Vol 1.2.0 2018/3/28</h4>
                <p>
                    管理画面（トップページ）作成
                </p>
            </li>
            <li>
                <h4>Vol 1.2.0 2018/3/21</h4>
                <p>
                    任意にメニューやウィジェット、カスタマイザーの作成ができる管理画面の開発着手
                </p>
            </li>
            <li>
                <h4>Vol 1.2.0 2018/2/21</h4>
                <p>
                    カスタムフィールドデータのapi化完了
                </p>
            </li>
            <li>
                <h4>Vol 1.2.0 2018/1/12</h4>
                <p>
                    カスタムフィールド抽出プラグインの作成完了<br>
                    $変数名=marcatgia(記事id);<br>
                    にてカスタムフィールドのデータを配列で取得可能
                </p>
            </li>
            <li>
                <h4>Vol 1.1.9 2017/12/30</h4>
                <p>
                    カスタマイザー・メニュー・ウィジェット拡張データのapi化
                </p>
            </li>
            <li>
                <h4>Vol 1.1.8 2017/12/30</h4>
                <p>
                    カスタマイザー・メニュー・ウィジェット拡張データのapi化
                </p>
            </li>
            <li>
                <h4>Vol 1.1.7 2017/11/30</h4>
                <p>
                    カスタマイザーの拡張対応完了
                </p>
            </li>
            <li>
                <h4>Vol 1.1.6 2017/10/21</h4>
                <p>
                    ウィジェットのカスタマイズ対応完了
                </p>
            </li>
            <li>
                <h4>Vol 1.1.5 2017/09/15</h4>
                <p>
                    メニューのカスタマイズの登録
                </p>
            </li>
            <li>
                <h4>Vol 1.1 2017/09/01</h4>
                <p>
                    MarcatGia誕生
                </p>
            </li>
        </ul>
    </section>
</div>

<?php
}