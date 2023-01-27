/**
 * 郵便番号検索支援モジュール
 *
 * @Filename    mts-postcodejp.js
 * @Author      S.Hayashi
 * @Code        2019-05-19 Ver.1.0.0
 */
var MtsPostcodeJP = function($)
{
    var iconId;

    // ローディングアイコン表示 ON OFF
    var loadingIcon = function(loading)
    {
        var attr = loading ? 'inline-block' : 'none';

        $('#mts-postcode-loading').css('display', attr);
    };

    /**
     * 郵便番号検索
     */
    this.findByPostcode = function()
    {
        loadingIcon(true);
    };

    /**
     * 英数字全角半角変換
     */
    this.zenToHan = function(postcode)
    {
        var str = postcode.replace(/ー/g, '－');

        str = str.replace(/[０-９Ａ-Ｚａ-ｚ－]/g, function(s) {
            return String.fromCharCode(s.charCodeAt(0) - 0xfee0)
        });

        return str;
    };

    $(document).ready(function ()
    {
        var apiKey = $("#mts-postcode-button").data('api_key');
        var postcode = $("#mts-postcode-button").data('postcode');
        var address = $("#mts-postcode-button").data('address');

        // APIキーを指定して住所補完サービスを作成
        var postcodeJp = new postcodejp.address.AutoComplementService(apiKey);

        // 自動補完を無効にする。
        postcodeJp.setAutoComplement(false);

        // 郵便番号テキストボックスを指定
        postcodeJp.setZipTextbox(postcode);

        // 住所補完フィールドを追加
        postcodeJp.add(new postcodejp.address.StateTownStreetTextbox(address));
        //postcodeJp.add(new postcodejp.address.StateSelectbox('customer-pref').byText());
        //postcodeJp.add(new postcodejp.address.TownTextbox('customer-city'));
        //postcodeJp.add(new postcodejp.address.StreetTextbox('customer-town'));

        // 住所が存在しない場合に住所フィールドをクリアする
        postcodeJp.setClearAddressIfNotFound(true);

        // 検索ボタンのidまたはエレメントを設定する
        postcodeJp.setComplementButton('mts-postcode-button');

        // フィールド設定後のコールバック
        postcodeJp.setAdditionalCallback(function(data){
            loadingIcon(false);
        });

        // 住所が存在しない場合のコールバック
        postcodeJp.setNotFoundCallback(function(){
            loadingIcon(false);
        });

        postcodeJp.observe();
    });

};

var mts_assistance = new MtsPostcodeJP(jQuery);
