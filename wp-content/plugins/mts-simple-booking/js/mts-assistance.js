/**
 * 入力支援モジュール
 *
 * @Filename    mts-assistance.js
 * @Author      S.Hayashi
 * @Code        2015-12-08 Ver.1.0.0
 *
 * Updated to 1.2.0 on 2019-05-17
 * Updated to 1.1.2 on 2017-08-07
 * Updated to 1.1.1 on 2017-07-19
 * Updated to 1.1.0 on 2017-03-09
 */
var MtsAssistance = function($)
{

    // ローディングアイコン表示 ON OFF
    var loadingIcon = function(loading)
    {
        var attr = loading ? 'inline-block' : 'none';

        $('#mts-postcode-loading').css('display', attr);
    };

    /**
     * 郵便番号検索
     *
     */
    this.findByPostcode = function()
    {
        var setsegs = [];
        var apiKey = arguments[0];

        for (var i = 1; i < arguments.length; i++) {
            setsegs[i - 1] = arguments[i];
        }

        // セットするセグメントを確認
        if (setsegs.length <= 0) {
            return;
        }

        loadingIcon(true);

        $.ajax({
            type : 'get',
            url : 'https://maps.googleapis.com/maps/api/geocode/json',
            crossDomain : true,
            dataType : 'json',
            data : {
                address : this.zenToHan($("#" + arguments[1]).val()),
                language : 'ja',
                sensor : false,
                key : apiKey,
            },
            success : function(ret){
                loadingIcon(false);
                if (ret.status == "OK" && isDomestic(ret.results[0].formatted_address)) {
                    setAddress(setsegs, ret.results[0].address_components);
                } else {
                    clearInput(setsegs, 0);
                }
                return false;
            }
        });
    };

    // 国内の住所か確認する
    var isDomestic = function(fmtaddr)
    {
        return fmtaddr.match(/日本/);
    };

    /**
     * 住所を指定セグメントにセットする
     *
     * @setseg  フォームのセグメントID配列
     * @addr    Google Geocoding APIの検索結果(都道府県は配列降順、最後は郵便番号)
     */
    var setAddress = function(setseg, addr)
    {
        var idx = 1;

        // 未設定の入力項目をクリアする
        clearInput(setseg, idx);

        locality = addr.pop();

        // 先頭が「日本」でない場合は検索結果なしとする
        if (isDomestic(locality.long_name)) {
            for (var i = addr.length; 0 < i; i--) {
                locality = addr.pop();
                if (!locality.long_name.match(/\d{3}-\d{4}/)) {
                    var $addseg = $('#' + setseg[idx]);
                    $addseg.val($addseg.val() + locality.long_name);
                    if (idx + 1 < setseg.length) {
                        idx++;
                    }
                }
            }
        }
    };

    // 未設定の入力項目をクリアする
    var clearInput = function(setseg, idx)
    {
        for ( ; idx < setseg.length; idx++) {
            $('#' + setseg[idx]).val('');
        }
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

    /**
     * $(document).ready
     */
    $(document).ready(function() {
    });

};

var mts_assistance = new MtsAssistance(jQuery);
