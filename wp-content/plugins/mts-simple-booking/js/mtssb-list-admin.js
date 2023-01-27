/**
 * MTS Simple Booking 予約リスト管理画面操作処理
 *
 * @Filename    mtssb-list-admin.js
 * @Date        2014-01-15
 * @Author      S.Hayashi
 * @Version     1.0.0
 *
 * Updated to 1.29.0 on 2018-05-22
 * Updated to 1.26.0 on 2017-06-07
 * Updated to 1.21.0 on 2015-03-25
 * Updated to 1.15.0 on 2014-01-31
 */
var booking_list_operation = function($)
{
    // Ajaxパラメータ
    var aparam = {
        'action' : 'admin_ajax_assist',
        'nonce'  : '',
        'module' : 'mtssb_list_admin',
        'method' : '',
        'booking_id' : '',
        'mail' : {}
    };

    // 操作対象 td tag object
    var $tdtag;

    // 予約確認マーク img tag html
    var tickimg;

    /**
     * リストの予約未確認マーククリックで予約確認とメール送信を実行する
     *
     */
    this.confirm = function(atag, booking_id)
    {
        // 捜査対象となるリストの確認カラムtd
        $tdtag = $(atag).parent();

        // AJAX通信パラメータの設定
        aparam.nonce = $("#nonce_ajax").val();
        aparam.method = 'confirm';
        aparam.booking_id = booking_id;

        // AJAXを利用して予約データ・メールのJSONデータを取得する
        $.post(ajaxurl, aparam, function(data) {
            if (data.result) {
                // チェックマーク
                tickimg = data.tickimg;

                // 予約データの表示
                bookingDialog(data);
            } else {
                alert(data.message);
            }
        });

        return false;
    };

    /**
     * 予約表示ダイアログ
     *
     */
    var bookingDialog = function(data)
    {
        var dlg = $("<div id='booking-data-dialog' />").html(data.content).appendTo("body");

        var param = {
            'title' : '予約情報の確認',
            'width' : 520,
            'dialogClass' : 'wp-dialog',
            'modal' : true,
            'autoOpen' : false,
            'closeOnEscape' : true,
            'buttons' : [
                {
                    'text' : 'チェック',
                    'class' : 'button-secondary',
                    'click' : function() {
                        $(this).dialog('close');
                        check_booking(data.booking_id);
                    }
                },
                {
                    'text' : 'キャンセル',
                    'class' : 'button-secondary',
                    'click' : function() {
                        $(this).dialog('close');
                    }
                }
            ]
        };

        var mail_button = {
            'text' : 'メール',
            'class' : 'button-secondary',
            'click' : function() {
                $("input[name='action']").val("mail");
                $(this).dialog('close');
                mailDialog(data.mailform);
                //$(this).find('form').submit();
            }
        };

        // 予約完了メール送信データがあればメールボタンを追加
        if (data.mailform) {
            param.buttons.unshift(mail_button);
        }

        dlg.dialog(param).dialog('open');
    };

    /**
     * メール編集送信ダイアログ処理
     *
     */
    var mailDialog = function(mailform)
    {
        var param = {
            'title' : '予約確認のメール',
            'dialogClass' : 'wp-dialog',
            'modal' : true,
            'draggable' : true,
            'resizable' : true,
            'width' : 520,
            'autoOpen' : false,
            'closeOnEscape' : true,
            'buttons' : [
                {
                    'text' : '送信する',
                    'class' : 'button-secondary',
                    'click' : function() {
                        // AJAX通信パラメータの設定
                        aparam.nonce = $("#nonce_ajax").val();
                        aparam.method = 'send';
                        aparam.booking_id = $(this).find("#check-mail-booking-id").val();
                
                        // メール内容
                        aparam.mail = {
                            'subject' : $(this).find("#check-mail-subject").val(),
                            'body' : $(this).find("#check-mail-body").val()
                        };

                        $(this).dialog('close');
                        send_mail();
                    }
                },
                {
                    'text' : 'キャンセル',
                    'class' : 'button-secondary',
                    'click' : function() {
                        $(this).dialog('close');
                    }
                }
            ]
        };

        var dlg = $("<div id='booking-mail-dialog' />").html(mailform).appendTo("body");
        dlg.dialog(param).dialog('open');
    };

    /**
     * 予約データの確認済みフラグ更新
     *
     */
    var check_booking = function(booking_id)
    {
        // AJAX通信パラメータの設定
        aparam.nonce = $("#nonce_ajax").val();
        aparam.method = 'check';
        aparam.booking_id = booking_id;

        // AJAXを利用して予約データ・メールのJSONデータを取得する
        $.post(ajaxurl, aparam, function(data) {
            if (data.result) {
                // 確認完了チェックマーク
                $tdtag.html(tickimg);
            } else {
                alert(data.message);
            }
        });
    };

    /**
     * メール送信処理
     *
     */
    var send_mail = function()
    {
        // AJAXを利用して予約データ・メールのJSONデータを取得する
        $.post(ajaxurl, aparam, function(data) {
            if (data.result) {
                // 確認完了チェックマーク
                $tdtag.html(tickimg);
            } else {
                alert(data.message);
            }
        });
    };
};

var mtssb_list_op = new booking_list_operation(jQuery);
