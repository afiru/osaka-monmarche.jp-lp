/**
 * MTS Simple Booking 管理画面スケジュール編集操作
 *
 * @Filename	mtssb-schedule-admin.js
 * @Date		2012-06-xx
 * @Author		S.Hayashi
 * @Version		1.0.0
 *
 * Updated to 1.33.0 on 2020-04-28
 * Updated to 1.32.0 on 2019-08-01
 * Updated to 1.14.0 on 2014-01-18
 * Updated to 1.6.0 on 2013-03-18
 */
const ScheduleAdmin = function($)
{
    let weeks = ['日', '月', '火', '水', '木', '金', '土'];

    // 年月日曜日
    let dateWeek = function (oDay)
    {
        return oDay.getFullYear() + '年' + (oDay.getMonth() + 1) + '月' + oDay.getDate() + '日 (' + weeks[oDay.getDay()] + ')';
    };

    let getDayData = function ($dayMemo)
    {
        return {
            'open' : $dayMemo.attr('data-open'),
            'delta' : $dayMemo.attr('data-delta'),
            'class' : $dayMemo.attr('data-class'),
            'note' : $dayMemo.attr('data-note')
        };
    };

    // 指定日付のスケジュールデータをダイアログにセットする
    let setDialogData = function ($td)
    {
        // データセグメント
        let $dayMemo = $td.find('.day-memo');
        let data = getDayData($dayMemo);

        // 編集日時の表示設定
        let day = new Date($dayMemo.attr('data-datetime') * 1000);
        $("#shop-day-date").text(dateWeek(day));

        // 予約受付チェックボックス設定
        $("input[name=dialog_open]").val([data.open]);

        // 予約受付増減数の設定
        $("input[name=dialog_delta]").val(data.delta);

        // class属性設定
        $("input[name=dialog_class]").val(data.class);

        // 注記設定
        $("input[name=dialog_note]").val(data.note);
    };

    // スケジュールデータを更新する
    let updateSchedule = function ($td)
    {
        // データセグメント
        let $dayMemo = $td.find('.day-memo');

        // 更新前データ
        let oldData = getDayData($dayMemo);

        // 更新データ
        let newData = {
            'open'  : parseInt($("input[name=dialog_open]:checked").val()),
            'delta' : parseInt($("#dialog-delta").val()),
            'class' : $("#dialog-class").val(),
            'note'  : $("#dialog-note").val()
        };

        // 編集日のtd class書き換え
        if (newData.open === 1) {
            $td.removeClass('close');
            $td.addClass('open');
        } else {
            $td.removeClass('open');
            $td.addClass('close');
        }

        // 増減値表示の書き換え
        $td.find('.day-delta').text(newData.delta);

        // 設定クラスの書き換え
        $td.removeClass(oldData.class);
        $td.addClass(newData.class);

        // 注記の書き換え
        $td.find('.day-memo').text(newData.note);

        // データを書き換える
        $dayMemo.attr('data-open', newData.open);
        $dayMemo.attr('data-delta', newData.delta);
        $dayMemo.attr('data-class', newData.class);
        $dayMemo.attr('data-note', newData.note);
    };

    // スケジュール編集ダイアログ
    let scheduleDialog = function ($td)
    {
        // ダイアログのパラメータ
        let param = {
            'title': 'スケジュールの設定',
            'width': 400,
            'dialogClass': 'wp-dialog',
            'modal': true,
            'autoOpen': false,
            'closeOnEscape': true,
            'buttons': [
                {
                    'text': '変更する',
                    'class': 'button-secondary',
                    'click': function () {
                        updateSchedule($td);
                        $(this).dialog('close');
                    }
                },
                {
                    'text': 'キャンセル',
                    'class': 'button-secondary',
                    'click': function () {
                        // ダイアログボックスをクローズする
                        $(this).dialog('close');
                    }
                }
            ]
        };

        // ダイアログデータの設定
        setDialogData($td);

        // ダイアログを表示する
        $("#shop-day-dialog").dialog(param).dialog('open');
    };

    // カレンダーの指定月・曜日の予約受付を変更する
    let alterOpen = function (booking, month, week)
    {
        let seg;

        if (0 < month) {
            seg = "#month-table-" + month + " td";
        } else {
            seg = ".month-table" + " td";
        }

        if (week !== 'all') {
            seg += week === 'all' ? '' : ("." + week);
        }

        if (booking === 'close') {
            $(seg + ":not(.no-day)").each(function () {
                $(this).removeClass('open');
                $(this).addClass('close');
                $(this).find(".day-memo").attr('data-open', 0);
            });
        } else {
            $(seg + ":not(.no-day)").each(function () {
                $(this).removeClass('close');
                $(this).addClass('open');
                $(this).find(".day-memo").attr('data-open', 1);
            });
        }
    };

    // 祝祭日を取り込む
    let getHolidays = function ()
    {
        // 祝祭日データを取り込む
        let holidays = $("#national-holidays").val();

        // 祝祭日データが空なら終了する
        if (holidays === '') {
            alert($("#check-holidays").text());
            return;
        }

        // オブジェクトに変換する
        holidays = JSON.parse(holidays);

        // 祝祭日カレンダーにセットする
        $.each(holidays, function(index, value) {
            let $dayMemo = $(".day-memo[data-datetime='" + index + "']");
            if ($dayMemo) {
                $dayMemo.parent('td').addClass('holiday');
                $dayMemo.attr('data-class', 'holiday');
                $dayMemo.attr('data-note', value);
                $dayMemo.text(value);
            }
        });
    };

    /**
     * スケジュールデータを送信前にセットする
     */
    this.setScheduleData = function ()
    {
        let schedule = {};

        $(".admin-eigyo-calendar .day-memo").each(function (idx) {
            schedule[$(this).attr('data-datetime')] = {
                'open' : $(this).attr('data-open'),
                'delta' : $(this).attr('data-delta'),
                'class' : $(this).attr('data-class'),
                'note' : $(this).attr('data-note')
            };
        });

        $("#article_schedule_data").val(JSON.stringify(schedule));

        return true;
    };


    $(document).ready(function ()
    {
        // カレンダー日の編集
        $(".admin-eigyo-calendar td").click(function() {
            if ($(this).hasClass('no-day')) {
                return;
            }

            scheduleDialog($(this));
        });

        // 特定月・曜日を一括で予約受付中止に変更する
        $("#alter-close").click(function() {
            alterOpen('close', $("#alter-day-month").val(), $("#alter-day-week").val());
        });

        // 特定月・曜日を一括で予約受付状態に変更する
        $("#alter-open").click(function() {
            alterOpen('open', $("#alter-day-month").val(), $("#alter-day-week").val());
        });

        // 祝祭日データを取り込む
        $("#get-holidays").click(function() {
            getHolidays();
        });


        // Noteダイアログ定義
        var noteDialog = function()
        {
            var param = {
                //'autoOpen' : false,
                'dialogClass' : 'wp-dialog',
                'modal' : true,
                'width' : 300,
                'title' : $("#schedule-note .title").text(),
                'closeOnEscape' : true,
                'buttons' : [
                    {
                        'text' : 'OK',
                        'class' : 'button-secondary',
                        'click' : function() {
                            $input.val($("#schedule-note-input").val());
                            $(this).dialog("close");
                        }
                    },
                    {
                        'text' : 'キャンセル',
                        'class' : 'button-secondary',
                        'click' : function() {
                            $(this).dialog("close");
                        }
                    }
                ]
            };

            $("#schedule-note").dialog(param).dialog('open');

        };

        // Note ダイアログ表示
        $(".schedule-note input").focus(function() {
            // 入力にダイアログを利用する設定か確認する
            if ($("#schedule-dialog").val() != 0) {
                if ($("#focus-flag").val() == 0) {
                    $("#focus-flag").val(1);
                    $input = $(this);
                    $("#schedule-note-input").val($input.val());
                    noteDialog();
                } else {
                    $("#focus-flag").val(0);
                }
            }
        });

        // 全日付チェック操作
        $("#schedule-check-all").change(function() {
            if ($(this).get(0).checked) {
                $(".schedule-open input").attr('checked', 'checked').parent().addClass('open');
                $(".schedule-box.column-title label input").attr('checked', 'checked');
            } else {
                $(".schedule-open input").removeAttr('checked').parent().removeClass('open');
                $(".schedule-box.column-title label input").removeAttr('checked');
            }
        });

        // 特定曜日日付チェック操作
        $(".schedule-box.column-title input").change(function() {
            var week = $(this).attr('class');

            if ($(this).get(0).checked) {
                $(".schedule-open ." + week).attr('checked', 'checked').parent().addClass('open');
            } else {
                $(".schedule-open ." + week).removeAttr('checked').parent().removeClass('open');
            }
        });

        // 特定日付チェック操作
        $(".schedule-open input").change(function() {
            if ($(this).get(0).checked) {
                $(this).parent().addClass('open');
            } else {
                $(this).parent().removeClass('open');
            }
        });
    });

};

const mtssb_schedule_admin = new ScheduleAdmin(jQuery);
