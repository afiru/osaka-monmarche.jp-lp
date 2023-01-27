/**
 * MTS Simple Booking オプション設定項目タイプ操作
 *
 * @Filename	mtssb-option-admin.js
 * @Date		2012-10-03
 * @Author		S.Hayashi
 * @Version		1.1.0
 *
 * Updated to 1.4.0 on 2013-01-29
 */
var fieldtype_operation = function($) {
	var field_frame;

	// フィールドアイテム数
	var itemno = 1;

	// フィールドアイテム行削除
	this.del_item = function(rowno) {
		$("#field-item-table").find("#field-item-row" + rowno).remove();

		return false;
	};



	$(document).ready(function() {

		// オプショングループの選択画面
		if ($("#select-option-group").length > 0) {

			// 新規追加ボタン
			$("#add-group-button").click(function() {
				$("#select-group-ope").css('display', 'none');
				$("#edit-group-name-box").css('display', 'block');
				$("#select-ope-edit-box").css('display', 'none');
				$("#select-ope-add-box").css('display', 'block');

				$("#option-group-name").val('');
				$("#option-group-title").val('');
				return false;
			});

			// 編集ボタン
			$("#edit-group-button").click(function() {
				$("#select-group-ope").css('display', 'none');
				$("#edit-group-name-box").css('display', 'block');
				$("#select-ope-add-box").css('display', 'none');
				$("#select-ope-edit-box").css('display', 'block');

				var sel_name = $("#select-group-name option:selected").val();
				$("#option-group-name").val(sel_name);
				$("#option-group-title").val($("#select-group-name option:selected").text());
				$("#option-group-sel-name").val(sel_name);
				return false;
			});

			// 戻るボタン
			$("#return-group-button").click(function() {
				$("#edit-group-name-box").css('display', 'none');
				$("#select-group-ope").css('display', 'block');
				return false;
			});

		} else {
			var field_type = ['radio', 'select', 'check'];

			// フィールドの入力フォーマットを取得する
			field_frame = $("#option-field-frame-row").html();

			// フィールドアイテム行数のカウント
			itemno = $("#field-item-table tbody td").length;


			// フィールドアイテム操作ブロックの初期表示
			if (0 <= $.inArray($("#opt-type").val(), field_type)) {
				$("#field-item").css('display', 'block');
			}

			// フィールドアイテム入力域の表示制御
			$("#opt-type").change(function() {
				if (0 <= $.inArray($("#opt-type").val(), field_type)) {
					$("#field-item").css('display', 'block');
				} else {
					$("#field-item").css('display', 'none');
				}
			});

			// フィールドアイテム入力行の追加
			$("#add-field-button").click(function() {
				itemno++;
				$newfield = $("<tr></tr>").html(field_frame);
				$newfield.find(".option-field-item.key input").attr('name', 'mts_simple_booking_option[field][' + itemno + '][key]');
				$newfield.find(".option-field-item.label input").attr('name', 'mts_simple_booking_option[field][' + itemno + '][label]');
				$newfield.find(".option-field-item.price input").attr('name', 'mts_simple_booking_option[field][' + itemno + '][price]');
				$newfield.find(".option-field-item.time input").attr('name', 'mts_simple_booking_option[field][' + itemno + '][time]');
				$newfield.find("button").attr('onclick', 'fieldop.del_item(' + itemno + '); return false;');
				$newfield.attr('id', 'field-item-row' + itemno);
				$("#field-item-table tbody").append($newfield);
				return false;
			});
		}
	});
};

var optionAdmin = new fieldtype_operation(jQuery);

//var fieldop = new fieldtype_operation(jQuery);
