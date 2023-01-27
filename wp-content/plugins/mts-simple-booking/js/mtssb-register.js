/**
 * 予約入力フォーム処理モジュール
 *
 * @Filename    mtssb-register.js
 * @Author      S.Hayashi
 * @Date        2014-12-04
 */
var MtssbRegister = function($)
{

    this.findByPostcode = function()
    {
        var setsegs = arguments;

        // セットするセグメントを確認
        if (setsegs.length <= 0) {
            return;
        }

        $("#post-loading").css('display', 'inline-block');

        $.ajax({
            type : 'get',
            url : getProtocol() + '://maps.googleapis.com/maps/api/geocode/json',
            crossDomain : true,
            dataType : 'json',
            data : {
                address : zenToHan($("input[name$='[postcode]']").val()),
                language : 'ja',
                sensor : false
            },
            success : function(ret){
                $("#post-loading").css('display', 'none');
                if (ret.status == "OK" && isDomestic(ret.results[0].formatted_address)) {
                    setAddress(setsegs, ret.results[0].address_components);
                }
                return false;
            }
        });
    };

    /**
     * 住所を指定セグメントにセットする
     *
     * @setseg  フォームのセグメントID配列
     * @addr    Google Geocoding APIの検索結果(都道府県は配列降順、最後は郵便番号)
     */
    var setAddress = function(setseg, addr)
    {
        // 郵便番号、都道府県、市区郡、区町村
        var iaddr = addr.length - 2;
        var iseg = 0;

        for (var i = 1; i < setseg.length && 1 <= iaddr; i++, iseg++, iaddr--) {
            $('#' + setseg[iseg]).val(addr[iaddr].long_name);
        }

        // 残りの住所を最後のセグメントにセットする
        var address = '';
        while (1 <= iaddr) {
            address += addr[iaddr].long_name;
            iaddr--;
        }

        $('#' + setseg[iseg]).val(address);
    };

    /**
     * 参照プロトコルの取得
     *
     */
    var getProtocol = function()
    {
        matches = location.href.match(/^(https?).*/);

        if (0 < matches.length) {
            return matches[1];
        }

        return 'http';
    };

    /**
     * 全角半角変換
     *
     */
    var zenToHan = function(postcode)
    {
        var str = postcode.replace(/ー/g, '－');

        str = str.replace(/[０-９Ａ-Ｚａ-ｚ－]/g, function(s) {
            return String.fromCharCode(s.charCodeAt(0) - 0xfee0)
        });

        return str;
    };

    /**
     * 国内の住所か確認する
     *
     */
    var isDomestic = function(fmtaddr)
    {
        return fmtaddr.match(/日本/);
    };

    /**
     * プライバシーポリシー同意のチェック
     *
     */
    this.checkAgreement = function()
    {
        var errMessage = '';

        if ($('#policy-agreement-check').attr("checked") != "checked") {
            errMessage = '同意のチェックがされておりません。';
        } else if ($('#guest-email').val() != $('#guest-email2').val()) {
            errMessage = '再入力のメールアドレスが一致しておりません。';
        } else if (0 < $("#aux-check-message").text().length && $("#aux-check").val() == 0) {
            errMessage = $("#aux-check-message").text();
        } else {
            return true;
        }

        alert(errMessage);
        return false;
    };

    // プライバシーポリシー同意チェックによる送信ボタンの状態変更
    var entryButtonStatus = function()
    {
        if (0 < $("#policy-agreement-check").length) {
            if ($("#policy-agreement-check").prop('checked')) {
                $("#mtsac-submit-button").removeAttr('disabled').css('opacity', '');
            } else {
                $("#mtsac-submit-button").attr('disabled', 'disabled').css('opacity', 0.5);
            }
        }
    };

    $(document).ready(function() {
        entryButtonStatus();

        $("#policy-agreement-check").change(function() {
            entryButtonStatus();
        });
    });

};

var mtssb_register = new MtssbRegister(jQuery);