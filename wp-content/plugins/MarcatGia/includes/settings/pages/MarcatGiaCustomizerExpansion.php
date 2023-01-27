<?php
function MarcatGiaCustomizerExpansion() {
?>
<div class="MarcatGiaSettingWapper">
    <h2>MarcatGiaについて</h2>
    <p>メニューやウィジェットやカスタマイザーの入力項目増やしたり、カテゴリーの編集画面に入力項目を追加することができます。</p>

    <?php require_once(dirname(__FILE__) . '/MarcatGiaSettingNav.php');//ナビデータの呼び込み ?>
</div>
<?php
}