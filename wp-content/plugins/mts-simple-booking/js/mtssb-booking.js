/**
 * MTS Simple Booking フロント予約操作
 *
 * @Filename	mtssb-booking.js
 * @Date		2013-01-15
 * @Author		S.Hayashi
 * @Version		1.0.0
 */
var booking_form_operation = function($) {

	// 予約ボタンを利用できる・できないようにする
	$(document).ready(function() {
		// チェックボックスを操作されたときの処理
		$("#terms-accedence").change(function() {
			if ($(this).attr('checked')) {
				$("#action-button-cover").css('display', 'none');
			} else {
				$("#action-button-cover").css('display', 'block');
			}
		});

		// 初期設定
		if ($("#terms-accedence").attr('checked')) {
			$("#action-button-cover").css('display', 'none');
		}
	});
};

var booking_op = new booking_form_operation(jQuery);
