/**
 * MTS Simple Booking 管理画面予約登録・編集操作
 *
 * @Filename	mtssb-booking-admin.js
 * @Date		2012-05-09
 * @Author		S.Hayashi
 * @Version		1.0.0
 *
 * Updated to 1.17.0 on 2014-07-13
 */
var mtssb_booking_admin = function($)
{
    var params = {
        action : 'admin_ajax_assist',
        module : '',
        nonce : ''
    };

    /**
     * 登録時の予約数の確認
     *
     */
    this.checkRemain = function(article_id)
    {
        $("#ajax-checking-saving").css('display', 'block');

        params.module = 'mtssb_booking_count';
        params.nonce = $("#ajax-nonce").val();
        params.method = 'data';     // 予約情報を取得する
        params.article_id = article_id;

        // 予約日Unix Time
        var bd = new Date(Date.UTC($("#booking_time_year").val(),
            $("#booking_time_month").val() - 1, $("#booking_time_day").val()));
        params.day_time = parseInt(bd.getTime() / 1000);

        // Ajax送信
        $.ajax({
            type : 'post',
            url  : ajaxurl,
            data : params
        })
        .done(function(result)
        {
            // ローディングアイコン消去
            if (result === -1) {
                alert('Ajax Error Occured.');
            } else {
                // 予約情報から予約を確認する
                var message = checkSaving(result.message);

                if (message.length <= 0 || confirm(message)) {
                    $("#add-booking").submit();
                }

                $("#ajax-checking-saving").css('display', 'none');
            }
        })
        .fail(function(fail){
                $("#ajax-checking-saving").css('display', 'none');
            alert('異常終了しました。');
        });

        return false;

    };

    // 予約データ登録前チェック
    var checkSaving = function(info)
    {
        // 編集前予約日時
        var oldBookingTime = parseInt($("#booking-time-old").val());

        // 予約時間の取得と新規予約日時
        var hour = parseInt($("#booking-time").val());
        var newBookingTime = hour + info.day_time;

        // 予約数
        var count = 0;
        if (info.booking_table[hour]) {
            count = info.booking_table[hour];
        }

        // 収容人数　または　予約件数
        if (info.restriction == 'capacity') {
            count += parseInt(su_han($("#booking-attendance").val()));
        } else {
            count += 1;
        }
        if ($("input[name='action']").val() == 'save' && oldBookingTime == newBookingTime) {
            count -= info.restriction == 'capacity' ? parseInt(su_han($("#booking-attendance-old").val())) : 1;
        }

        // 予約数が受付可能数を超える場合はメッセージ
        var accepting = info.max + info.delta;
        if (accepting < count) {
            return '予約を実行すると受付可能な数を超えます。';
        }

        // スケジュールチェック
        if (info.open == 0) {
            return 'スケジュールは予約停止の設定です。';
        } else if (info.open < 0) {
            return 'スケジュールが未設定です。';
        }

        return '';
    };

    // 全角数字を半角数字に変換する
    var su_han = function (sustr)
    {
        return sustr.replace(/[０-９]/g, function(zen) {
            return String.fromCharCode(zen.charCodeAt(0) - 0xfee0);
        });
    };

    /**
     * 登録時の予約数の情報表示
     *
     */
    this.infoRemain = function(article_id)
    {
        loader_img(1);

        params.module = 'mtssb_booking_count';
        params.nonce = $("#ajax-nonce").val();
        params.method = 'html';
        params.article_id = article_id;

        // 予約日Unix Time
        var bd = new Date(Date.UTC($("#booking_time_year").val(),
            $("#booking_time_month").val() - 1, $("#booking_time_day").val()));
        params.day_time = parseInt(bd.getTime() / 1000);

        // Ajax送信
        $.ajax({
            type : 'post',
            url  : ajaxurl,
            data : params
        })
            .done(function(result)
            {
                // ローディングアイコン消去
                loader_img(0);
                if (result === -1) {
                    alert('Error Occured.');
                } else {
                    infoDialog(result.message);
                }
            })
            .fail(function(fail){
                loader_img(0);
                alert('異常終了しました。');
            });

        return false;
    };

    // 予約確認の情報ダイアログ
    var infoDialog = function(data)
    {
        var dlg = $("<div id='booking-info-dialog' />").html(data).appendTo("body");

        var param = {
            'title' : '予約情報の確認',
            'width' : 480,
            'dialogClass' : 'wp-dialog',
            'modal' : true,
            'autoOpen' : false,
            'closeOnEscape' : true,
            'buttons' : [
                {
                    'text' : '閉じる',
                    'class' : 'button-secondary',
                    'click' : function() {
                        $(this).dialog('close');
                    }
                }
            ]
        };

        dlg.dialog(param).dialog('open');
    };

    // ローディングイメージの表示
    var loader_img = function(sw)
    {
        if (sw) {
            $("#loader-img").css('display', 'inline-block');
        } else {
            $("#loader-img").css('display', 'none');
        }
    };

    // タイムテーブルAJAX取得
	$(document).ready(function() {

		$("#booking-article").change(function()
        {
            $("#booking-time").css('display', 'none');
            loader_img(1);

            params.module = 'mtssb_get_timetable';
            params.nonce = $("#ajax-nonce").val();
			params.article_id = $("#booking-article option:selected").val();

			$.post(ajaxurl, params, function(data) {
 				loader_img(0);
                $("#booking-time").css('display', 'inline').html(data.message);
			});
			return false;
		});
	});
};

var booking_admin = new mtssb_booking_admin(jQuery);
