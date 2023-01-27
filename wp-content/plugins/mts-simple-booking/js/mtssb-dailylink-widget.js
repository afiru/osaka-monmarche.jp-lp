/**
 * Widget ミックス予約カレンダーリンク
 *
 * @Filename	mtssb-dailylink-widget.js
 * @Date		2013-11-27
 * @Author		S.Hayashi
 *
 * @License		GPL2 or MIT
 *
 * Updated on 2014-07-16
 */
var mtssb_dailylink_widget = function($) {

	/**
	 * 月予約カレンダーの切り換え表示
	 *
	 */
	var paging_calendar = function()
    {
		// 年月パラメータの確認
		var regym = /ym=(\d{4})-(\d{1,2})/g;
		var ym = regym.exec($(this).attr('href'));
		if (isNaN(ym[1]) || isNaN(ym[2])) {
			return false;
		}

		// パラメータの設定とAJAX準備
		var $winfo = $(this).closest('.mtssb-dailylink-calendar').next();
		var param = {
			action: 'mtssb_dailylink_calendar',
			nonce: $winfo.find(".mtssb-nonce").text(),
            param: $winfo.find(".mtssb-param").text(),
            class: $winfo.find(".mtssb-class").text(),
            pgurl: $winfo.find(".mtssb-mix-calendar").text(),
            ym: ym[1] + '-' + ym[2],
		};

        // WordPress AJAX entry point
    	var posturl = $winfo.find(".mtssb-ajaxurl").text();

        // AJAX処理中のGIF画像表示
		var $warea = $winfo.prev();
		$warea.children(".ajax-calendar-loading-img").css('display', 'block');

        // AJAX通信でミックスカレンダーを取得する
		$.post(posturl, param, function(data)
        {
            $warea.children(".wrap-" + param.class).html(data);
			$warea.find(".monthly-prev-next a").bind('click', paging_calendar);

            // GIF画像を非表示にする
            $warea.children(".ajax-calendar-loading-img").css('display', 'none');
		});

		return false;
	}

	/**
	 * 予約日時間割カレンダーの表示
	 *
	 */
	var daytime_calendar = function()
    {
		// 年月日Unix timeパラメータの確認
		var regymd = /ymd=(\d*)/g;
		var ymd = regymd.exec( $(this).attr('href') );
		if (isNaN(ymd[1])) {
			return false;
		}

        // パラメータの取得
        var $winfo = $(this).closest('.mtssb-dailylink-calendar').next();
        var linkurl = $winfo.find(".mtssb-mix-calendar").text();

        // FORM送信を使って日付カレンダーを表示する
        $('<form/>', {action: linkurl, method: 'post'})
            .append($('<input>').attr({type: 'hidden', name: 'ymd', value: ymd[1]}))
            .appendTo(document.body)
            .submit();

        return false;
	}

	$(document).ready(function() {
		$(".mtssb-dailylink-calendar .monthly-prev-next a").click(paging_calendar);
	});

}

var oMtssbDailylinkWidget = new mtssb_dailylink_widget(jQuery);
