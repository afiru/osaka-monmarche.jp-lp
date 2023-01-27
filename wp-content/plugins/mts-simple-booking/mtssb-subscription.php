<?php
if (!class_exists('MTSSB_Booking')) {
	require_once(dirname(__FILE__) . '/mtssb-booking.php');
}
/**
 * MTS Simple Booking 予約確認フロント処理モジュール
 *
 * @Filename	mtssb-subscription.php
 * @Date		2013-05-22
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-04-21
 * Updated to 1.31.0 on 2019-05-17
 * Updated to 1.29.0 on 2018-03-28
 * Updated to 1.26.0 on 2017-04-27
 * Updated to 1.25.0 on 2016-10-28
 * Updated to 1.22.0 on 2015-06-30
 * Updated to 1.21.0 on 2015-01-05
 * Updated to 1.19.0 on 2014-10-30
 * Updated to 1.15.2 on 2014-07-08
 * Updated to 1.15.0 on 2014-04-28
 */

class MTSSB_Subscription extends MTSSB_Booking
{
	const PAGE_NAME = MTS_Simple_Booking::PAGE_SUBSCRIPTION;

	// 予約条件パラメータ
	public $controls;
	public $charge;

	// 顧客データのカラム情報
	private $reserve;		// 各種設定　予約メール

	// 予約日時に関する情報
	private $this_year;		// 現在年
	private $this_month;	// 現在月
	private $this_day;		// 現在日
	private $today_time;	// 現在年月日 Unix Time

	// 予約品目
	private $article_id;
	public $article;

	// 予約データ配列
	private $bookings = array();

	// 予約ID検索入力データ
	private $search = array(
		'reserve_id' => 0,		// reserve_id入力値
		'client_email' => '',	// email入力値
		'bookingid' => 0,			// 下3桁のbooking_id
		'rsvdtime' => 0,		// 予約日 unix time
	);

	// エラーメッセージ
	private $err_message = '';
	private $errmsg = array();

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		// 予約条件パラメータのロード
		$this->controls = get_option($this->domain . '_controls');
		$this->charge = get_option($this->domain . '_charge');

		// 時間情報の取得
		$this->this_year = date_i18n('Y');
		$this->this_month = date_i18n('n');
		$this->this_day = date_i18n('j');
		$this->today_time = mktime(0, 0, 0, $this->this_month, $this->this_day, $this->this_year);

		// 顧客データのカラム利用設定情報を読込む
		$this->reserve = get_option($this->domain . '_reserve');
	}

	/**
	 * キャンセルのwpアクション処理
	 *
	 * return	リダイレクト先
	 */
	public function cancelWp()
	{
		// 予約キャンセルメールの送信
        //if (isset($_POST['action']) && $_POST['action'] == 'send') {
        if (isset($_POST['cancel_booking']) && isset($_POST['nonce'])) {
            // NONCEチェック
            if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
                $this->err_message = 'NONCE_ERROR';
                return '';
            }

            $bookingId = intval($_POST['booking_id']);
            $this->booking = $this->get_booking($bookingId);
            // 予約IDの不正書き換えチェック
            if (!$this->booking || $_POST['client_email'] != $this->booking['client']['email']) {
                return '';
            }

            // メール方式
            if ($this->controls['cancel'] == 1) {
                // 予約キャンセルのリンクURIを取得する
                $cancel_url = $this->cancel_url();

                if ($cancel_url) {
                    // メール送信
                    if ($this->cancel_mail($cancel_url)) {
                        $next_url = $this->_getPage(MTS_Simple_Booking::PAGE_CANCEL_SEND, 'send');
                        return $next_url;
                    }
                }
            }

            // ボタン実行
            elseif ($this->controls['cancel'] == 2) {
                if ($this->cancel_booking()) {
                    if ($this->cancel_mail() && $this->remove_mail()) {
                        $next_url = $this->_getPage(MTS_Simple_Booking::PAGE_CANCEL_THANKS, 'thanks');
                        return $next_url;
                    }
                }
            }

		// 予約キャンセル実行(メールのリンクアドレス)
		} elseif (isset($_GET['action']) && $_GET['action'] == 'cancel') {
            $bookingId = intval($_GET['bid']);
            $this->booking = $this->get_booking($bookingId);
            if (!$this->booking) {
                return '';
            }

            // キャンセルキーの確認
            $key = $this->_cancel_key($bookingId, $this->booking['client']['email']);
            if ($_GET['key'] !== $key) {
                $this->err_message = 'CANCEL_UNAVAILABLE';
            } elseif ($this->_check_cancel_limit() <= 0) {
                $this->err_message = 'UNACCEPTABLE_TIME';
            } elseif ($this->cancel_booking()) {
				if ($this->remove_mail()) {
                    $next_url = $this->_getPage(MTS_Simple_Booking::PAGE_CANCEL_THANKS, 'thanks');
					return $next_url;
				}
			}
		}

		return '';
	}

    // 実行後の表示ページ取得
    private function _getPage($pageName, $action='')
    {
        $pageUrl = '';

        $page = get_page_by_path($pageName);

        if (empty($page)) {
            $pageUrl = add_query_arg(array('action' => $action), get_permalink());
        } else {
            $pageUrl = get_permalink($page);
        }

        return $pageUrl;
    }

	/**
	 * キャンセルキーの取得
	 */
	public function cancel_url()
	{
		// キャンセルが有効か確認する
		$canceltime = $this->_check_cancel_limit();

		if ($canceltime <= 0) {
			$this->err_message = 'UNACCEPTABLE_TIME';
			return false;
		}

		// キャンセル実行のURIを戻す
		return add_query_arg(
			array(
				'action' => 'cancel',
				'bid' => $this->booking['booking_id'],
				'key' => $this->_cancel_key($this->booking['booking_id'], $this->booking['client']['email']),
			),
			MTS_Simple_Booking::get_permalink_by_slug(self::PAGE_NAME)
		);
	}

	/**
	 * 予約キャンセルのメール
	 *
	 * @cancel_url		キャンセル実行のリンク
	 */
	public function cancel_mail($cancel_url='')
	{
		global $mts_simple_booking;

		// メール送信モジュール
		$mail = $mts_simple_booking->_load_module('MTSSB_Mail');

		// 埋込み変数
		$vars = array_merge(array('%CANCEL_URL%' => $cancel_url), $mail->setTempVar($this->article, $this->booking));

		// メールテンプレートの読込み
		$template = get_option($this->domain . '_reserve');

		// 施設情報の埋め込み
		$cancel_content = $mail->replaceVariable($template['cancel_body'], $vars);

		// メールタイトル
		$subject = $mail->replaceVariable($template['cancel_title'], $vars);

        // メール送信前フィルター
        $param = apply_filters('mtssb_mail_exchange', array(
            'state' => 'cancel',
            'aid' => $this->booking['article_id'],
            'to' => $this->booking['client']['email'],
            'subject' => $subject,
            'body' => $cancel_content,
            'from' => '',
            'header' => array(),
        ));

		// メール送信
		if (!empty($this->booking['client']['email'])) {
			if ($mail->templateMail($param['to'], $param['subject'], $param['body'], $param['from'], $param['header'])) {
				return true;
			}
			die('template mail error');
		}

		// メール送信エラー
		$this->error_send_mail();
		return false;
	}

	/**
	 * キャンセル処理実行
	 */
	public function cancel_booking()
	{
		// 予約データを確認
		if (empty($this->booking)) {
			$this->err_message = 'CANCEL_UNAVAILABLE';
		} else {
            if ($this->_check_cancel_limit() <= 0) {
				$this->err_message = 'UNACCEPTABLE_TIME';
			} else {
				// 予約データの削除
				if ($this->del_booking($this->booking['booking_id'])) {
					return true;
				}
				$this->err_message = 'CANCEL_FAILED';
			}
		}

		return false;
	}

	/**
	 * 予約キャンセル実行のお知らせメール
	 *
	 */
	public function remove_mail()
	{
		global $mts_simple_booking;

		// メール送信モジュール
		$mail = $mts_simple_booking->_load_module('MTSSB_Mail');

		// 予約品目データ
		$article = MTSSB_Article::get_the_article($this->booking['article_id']);

		// 埋込み変数
		$vars = $mail->setTempVar($article, $this->booking);

		// 本文
		$content = "予約キャンセルが実行されました。\n\n"
			. "[キャンセル日時] " . date_i18n('Y-m-d H:i:s') . "\n"
			. "[予約ID] %RESERVE_ID%\n"
			. "[予約] %CLIENT_NAME% 様\n"
			. " %ARTICLE_NAME%\n"
			. " %BOOKING_DATE% %BOOKING_TIME%\n\n";
		$content = apply_filters('mtssb_mail_cancel_mybody', $content);
		$body = $mail->replaceVariable($content, $vars);

		// 予約状況
		$oStatus = $this->getBookingStatus($this->booking['article_id'], $this->booking['booking_time']);

		// 予約状況の追加
		$bookingInfo = $mail->bookingStatusInfo($this->booking['booking_time'], $oStatus, $article);
		$body .= $bookingInfo;

		$subject = apply_filters('mtssb_mail_cancel_mysubject', "【予約キャンセル】", 'cancel');
		$subject = $mail->replaceVariable($subject, $vars);

        // メール送信前フィルター
        $param = apply_filters('mtssb_mail_exchange', array(
            'state' => 'canceled',
            'aid' => $this->booking['article_id'],
            'to' => $mail->getShopEmail(),
            'subject' => $subject,
            'body' => $body,
            'from' => '',
            'header' => array(),
        ));

		// ショップへキャンセル実行送信
		return $mail->templateMail($param['to'], $param['subject'], $param['body'], $param['from'], $param['header']);
	}

	/**
	 * ハッシュキーを戻す
	 *
	 * @booking_id
	 * @email
	 */
	private function _cancel_key($booking_id, $email)
	{
		return sha1($booking_id . $email
		 . (defined('AUTH_KEY') ? AUTH_KEY : __FILE__));
	}

	/**
	 * メールの送信エラーメッセージをセット
	 *
	 */
	public function error_send_mail() {
		$this->err_message = 'ERROR_SEND_MAIL';
	}

	/**
	 * ステータス別予約フォーム処理
	 * the_content フィルター処理
	 */
	public function content($content)
    {
		// 上位でエラーの場合はエラー表示する
		if (!empty($this->err_message)) {
			return $this->_out_errorbox();
		}

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

		// SUBMIT処理
		if (isset($_POST['action'])) {

			// NONCEチェック
			if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
				$this->err_message = 'NONCE_ERROR';
				return $this->_out_errorbox();
			}

			if ($action == 'search') {
				// 予約ID、メールアドレスの入力チェック
				if ($this->_search_validation()) {
					// 予約データを検索する
					$this->bookings = $this->find_by_reserveid(
					 $this->search['bookingid'], $this->search['client_email'], $this->today_time);

					if (!empty($this->bookings)) {
						// 対象メールアドレスの確認
						foreach ($this->bookings as $key => $booking) {
							if ($booking['client']['email'] != $this->search['client_email']) {
								unset($this->bookings[$key]);
							}
						}
						// 予約データの表示
						if ($this->bookings) {
							$body = '';
							foreach ($this->bookings as $booking) {
								$body .= $this->_out_booking($booking);
							}
							return apply_filters('subscription_search_body', $body);
						}
					}

					$this->err_message = 'NOT_FOUND';
				}
			}
		} elseif ($action == 'send') {
			return apply_filters('subscription_cancel_send', '<p>キャンセルメールを送信しました。</p>');
		} elseif ($action == 'thanks') {
			return apply_filters('subscription_cancel_thanks', '<p>予約をキャンセルしました。</p>');
		} elseif ($action == 'show') {
            return $this->_showBooking();
        }

		return $this->_confirmation_form() . $content;
	}

    /**
     * カレントユーザーの予約データ表示
     *
     */
    private function _showBooking()
    {
        $bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        // 予約データを取得する
        $booking = $this->get_booking($bookingId);

        // 予約データのuser_idがカレントユーザーか確認する
        if (empty($booking) || $booking['user_id'] != get_current_user_id()) {
            return $this->_out_errorbox('NOT_FOUND');
        }

        // 検索結果表示のためのデータ配列にする
        $bookings[$bookingId] = $booking;

        // 表示データを戻す
        return $this->_out_booking($booking);
    }


	/**
	 * 予約ID入力の正規化と確認
	 *
	 */
	protected function _search_validation()
	{
		// 入力データの正規化
		$reserve_id = trim(mb_convert_kana($_POST['reserve_id'], 'as'));
		$client_email = trim(mb_convert_kana($_POST['client_email'], 'as'));

		// 予約IDの確認(yymmddxxx)
		if ($this->_required_check('reserve_id', $reserve_id)) {
			if (!preg_match("/^[0-9]{9}$/", $reserve_id)) {
				$this->errmsg['reserve_id'] = $this->_err_message('INVALID_INPUT');
			}
		}

		// E-Mailの確認
		if ($this->_required_check('client_email', $client_email)) {
			if (!preg_match("/^[0-9a-z_\.\-]+@[0-9a-z_\-\.]+\.[0-9a-z]+$/i", $client_email)) {
				$this->errmsg['client_email'] = $this->_err_message('INVALID_INPUT');
			}
		}

		// 入力データをセット
		$this->search['reserve_id'] = $reserve_id;
		$this->search['client_email'] = $client_email;

		// 予約ID下3桁をセット
		$this->search['bookingid'] = substr($reserve_id, -3);

		// 予約日のunix timeをセット
		$this->search['rsvdtime'] = mktime(0, 0, 0,
			intval(substr($reserve_id, 2, 2)), intval(substr($reserve_id, 4, 2)), 2000 + intval(substr($reserve_id, 0, 2)));

		// 予約日時の確認
		if ($this->search['rsvdtime'] < $this->today_time && !apply_filters('subscription_past_booking', false)) {
			$this->err_message = 'PAST_RESERVATION';
		}

		return empty($this->errmsg) && empty($this->err_message);
	}

	/**
	 * Requiredチェック
	 *
	 * @item	項目名
	 * @val		対象変数
	 */
	private function _required_check($item, $val)
	{
		if (empty($val)) {
			$this->errmsg[$item] = $this->_err_message('REQUIRED');
		}
		return !isset($this->errmsg[$item]);
	}

	/**
	 * エラーメッセージ
	 *
	 */
	protected function _err_message($err_name) {
		switch ($err_name) {
			case 'NONCE_ERROR':
				return 'ページは表示できません。';
			case 'CANCEL_UNAVAILABLE':
				return 'このページは利用できません。';
			case 'REQUIRED':
				return 'この項目は必ず入力して下さい。';
			case 'INVALID_INPUT':
				return '入力データに誤りがあります。';
			case 'PAST_RESERVATION':
				return 'この予約は対象外です。';
			case 'NOT_FOUND':
				return '予約データが見つかりませんでした。';
			case 'UNACCEPTABLE_TIME':
				return 'キャンセルの受付期限が過ぎました。';
			case 'CANCEL_FAILED':
				return 'キャンセル処理が正常に終了しませんでした。';
			case 'ERROR_SEND_MAIL':
				return 'キャンセルメール送信を失敗しました。電話で確認をお願いします。';
			default :
				return $err_name;
		}
	}

	/**
	 * エラーエレメントの出力
	 *
	 */
	protected function _out_errorbox($err='')
    {
        $errCode = empty($err) ? $this->err_message : $err;
        $errMessage = $this->_err_message($errCode);

		ob_start();
?>
		<div class="error-message error-box">
			<?php echo $errMessage ?>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * 予約確認入力フォームの表示
	 *
	 */
	protected function _confirmation_form()
	{
		$data = array(
			'reserve_id' => empty($this->search['reserve_id']) ? '' : $this->search['reserve_id'],
			'client_email' => $this->search['client_email']
		);

		ob_start();
?>

<div id="mining-form" class="content-form">
<?php
	echo apply_filters('confirmation_form_before', '');
	echo $this->_outform_confirmation($data);
	echo apply_filters('confirmation_form_after', '');
?>
</div>
<?php
		return ob_get_clean();
	}

	/**
	 * 予約確認入力フォーム生成
	 *
	 */
	private function _outform_confirmation($data)
	{
		$url = get_permalink();

		ob_start();
?>

<?php if (!empty($this->err_message)) : ?>
<div class="form-message error">
	<?php echo $this->_err_message($this->err_message) ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo $url ?>">
	<fieldset id="booking-reservation-fieldset">
	<legend><?php echo apply_filters('confirmation_form_title', '予約者情報') ?></legend>
	<?php echo apply_filters('confirmation_form_message', '') ?>
	<table>
		<tr>
			<th><label for="reserve-id"><?php echo apply_filters('confirmation_form_reserve_id', '予約ID') ?>(<span class="required">※</span>)</label></th>
			<td>
				<input id="reserve-id" class="content-text medium" type="text" name="reserve_id" value="<?php echo esc_html($data['reserve_id']) ?>" maxlength="12" />
				<?php echo isset($this->errmsg['reserve_id']) ? ('<div class="error-message">' . $this->errmsg['reserve_id'] . '</div>') : '' ?>
			</td>
		</tr>
		<tr>
			<th><label for="client-email"><?php echo apply_filters('confirmation_form_client_email', 'メールアドレス') ?>(<span class="required">※</span>)</label></th>
			<td>
				<input id="client-email" class="content-text fat" type="text" name="client_email" value="<?php echo esc_html($data['client_email']) ?>" maxlength="120" />
				<?php echo isset($this->errmsg['client_email']) ? ('<div class="error-message">' . $this->errmsg['client_email'] . '</div>') : '' ?>
			</td>
		</tr>
	</table>
	</fieldset>

	<div class="subscription-search">
		<?php echo apply_filters('confirmation_form_send_button', '<input id="subscription-search-button" class="subscription button"type="submit"  name="search_booking" value="予約検索">'); ?>
	</div>
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
	<input type="hidden" name="action" value="search" />
</form>
<?php
		return ob_get_clean();

	}


	/**
	 * 料金の表示
	 *
	 */
	public function money_format($amount)
	{
		// セレクトボックスの空白など値がない場合は表示しないようにする
		if (!is_numeric($amount)) {
			return '';
		}

		if ($this->charge['currency_code'] == 'JPY') {
			return apply_filters('booking_form_money_format', number_format($amount) . '円', $amount, $this->charge['currency_code']);
		}

		return apply_filters('booking_form_money_format', number_format($amount, 2) . 'ドル', $amount, $this->charge['currency_code']);
	}


	/**
	 * キャンセル期限の確認
	 *
	 */
	protected function _check_cancel_limit()
	{
		// 予約品目のキャンセル期限時間を取得する
		if (empty($this->article)) {
			$this->article = MTSSB_Article::get_the_article($this->booking['article_id']);
		}
		$limit = $this->article['addition']->cancel_limit;

        // Cancelが有効か確認する
		if ($limit == 0 || empty($this->controls['cancel'])) {
			return 0;
		}

		// 予約日時がキャンセル期限を下回っていないか確認する
		return $this->booking['booking_time'] - (current_time('timestamp') + $limit * 60);
	}

	/**
     * 予約データの表示
     */
	protected function _out_booking($booking)
    {
		// 表示ページのURL
		$page_url = get_permalink();

		$this->booking = $booking;
		$client = $booking['client'];
		$this->article = MTSSB_Article::get_the_article($booking['article_id']);

		// キャンセルが有効か確認する
		$canceltime = $this->_check_cancel_limit();

        // キャンセルボタンのダイアログメッセージ
        if ($this->controls['cancel'] == 1) {
            $msg = 'キャンセルを実行するためのメールを送信します。よろしいですか？';
        } else {
            $msg = 'キャンセルを実行します。よろしいですか？';
        }

		ob_start();
?>

<div class="content-form">
<table>
	<tr>
		<th class="subscription-header"><form method="post" action="<?php echo $page_url ?>">
			<span class="subscription-title">
			<?php echo apply_filters('booking_form_date', date('Y年n月j日 H:i', $booking['booking_time']), $booking['booking_time']);
				echo " {$this->article['name']}"; ?>
			</span>
			<?php if ($canceltime != 0) {
                echo '<span class="subscription-cancel">';
				if (0 < $canceltime) {
                    echo sprintf('<input id="subscription-cancel-button" class="subscription button" type="submit" name="cancel_booking" onclick="return confirm(\'%s\')" value="%s" />',
                        $msg, apply_filters('subscription_cancel_button_top', 'キャンセル'));
				} else {
					echo apply_filters('subscription_cancel_limit_message', 'キャンセル受付終了');
				}
				echo '</span>';
			} ?>

			<input type="hidden" name="booking_id" value="<?php echo $booking['booking_id'] ?>" />
			<input type="hidden" name="client_email" value="<?php echo $client['email'] ?>" />
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
			<input type="hidden" name="action" value="send" />
		</form></th>
	</tr>
	<tr>
		<td><table>
			<tr>
				<th><?php echo apply_filters('booking_form_people_number', '予約人数', 'subscription') ?></th>
				<td>
					<?php foreach ($this->controls['count'] as $key => $val) : ?><div class="input-number"<?php echo $val != 1 ? ' style="display:none"' : '' ?>><?php
						$title = apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'subscription');
					 	if ($title != '') { echo "$title "; }
							echo esc_html($client[$key]) ?><?php echo apply_filters('booking_form_count_note', '', $key) ?>
					</div><?php endforeach; ?>
				</td>
			</tr>
<?php
		// オプション先方指定の表示
		if ($this->article['addition']->isOption() && $this->article['addition']->position == 0) {
			$this->_out_booking_option($booking);
		}

		// 連絡先の表示
		$this->_out_booking_client($client);

		 // オプション後方指定の表示
		if ($this->article['addition']->isOption() && $this->article['addition']->position == 1) {
			$this->_out_booking_option($booking);
		}
?>

		<tr>
			<td colspan="2"><?php echo apply_filters('booking_form_message_title', 'ご連絡事項') ?></td>
		</tr>
		<tr>
			<th><?php echo apply_filters('booking_form_message_title_sub', '内容') ?></th>
			<td>
				<?php echo nl2br(esc_html($booking['note'])) ?>
			</td>
		</tr>
<?php
		// 請求明細の表示
		if ($this->charge['charge_list'] == 1) {
			$this->_out_booking_bill($this->article['addition']);
		}
?>

		</table></td>
	</tr>
</table>
</div>
<?php
		return ob_get_clean();
	}

	/**
	 * 連絡先の確認フォーム出力
	 *
	 */
	private function _out_booking_client($client)
	{
		// フォーム並び順配列
		$column_order = explode(',', $this->reserve['column_order']);
?>
		<tr>
			<td class="option-confirm-header" colspan="2"><?php echo apply_filters('booking_form_client_title', 'ご連絡先') ?></td>
		</tr>
	<?php foreach ($column_order as $column) : if (0 < $this->reserve['column'][$column]) :
		switch ($column) :
		case 'company' : ?><tr>
			<th><?php echo apply_filters('booking_form_company', '会社名') ?></th>
			<td>
				<?php echo esc_html($client['company']) ?>
			</td>
			<?php break;
		case 'name' : ?><tr>
			<th><?php echo apply_filters('booking_form_name', 'お名前') ?></th>
			<td>
				<?php echo esc_html($client['name']) ?>
			</td>
			<?php break;
        case 'furigana' : ?><tr>
			<th><?php echo apply_filters('booking_form_furigana', 'フリガナ') ?></th>
			<td>
				<?php echo esc_html($client['furigana']) ?>
			</td>
			<?php break;
		case 'birthday' : ?><tr>
			<th><?php echo apply_filters('booking_form_birthday', '生年月日') ?></th>
			<td>
				<?php echo apply_filters('booking_form_birthday_date', $client['birthday']->get_date('j'), $client['birthday']->get_date()) ?>
			</td>
			<?php break;
		case 'gender' : ?><tr>
			<th><?php echo apply_filters('booking_form_gender', '性別') ?></th>
			<td><?php $gender = empty($client['gender']) ? '' : ($client['gender'] == 'male' ? '男性' : '女性');
				echo apply_filters('booking_form_gender_type', $gender, $client['gender']) ?>
			</td>
			<?php break;
		case 'email' : ?><tr>
			<th><?php echo apply_filters('booking_form_email', 'E-Mail') ?></th>
			<td>
				<?php echo esc_html($client['email']) ?>
			</td>
			<?php break;
		case 'postcode' : ?><tr>
			<th><?php echo apply_filters('booking_form_postcode', '郵便番号') ?></th>
			<td>
				<?php echo esc_html($client['postcode']) ?>
			</td>
			<?php break;
		case 'address' : ?><tr>
			<th><?php echo apply_filters('booking_form_address', '住所') ?></th>
			<td>
				<?php echo esc_html($client['address1']) . '<br />' . esc_html($client['address2']) ?>
			</td>
			<?php break;
		case 'tel' : ?><tr>
			<th><?php echo apply_filters('booking_form_tel', '電話番号') ?></th>
			<td>
				<?php echo esc_html($client['tel']) ?>
			</td>
			<?php break;
		case 'newuse' : ?><tr>
			<th><?php echo apply_filters('booking_form_newuse', '新規利用') ?></th>
			<td><?php switch ($client['newuse']) {
						case 1 :
							$newuse_val = apply_filters('booking_form_newuse_yes', 'はい');
							break;
						case 2 :
							$newuse_val = apply_filters('booking_form_newuse_no', 'いいえ');
							break;
						default :
							$newuse_val = '';
							break;
					}
					echo $newuse_val; ?>
			</td>
			<?php break;
		default :
			break;
		endswitch; ?></tr>

<?php
		endif;
		endforeach;

		return;
	}

	/**
	 * オプションの確認フォーム出力
	 *
	 */
	private function _out_booking_option($booking)
	{
?>
		<tr>
			<td class="option-confirm-header" colspan="2"><?php echo apply_filters('booking_form_option_title', 'オプション注文', 'subscription') ?></td>
		</tr>
		<?php foreach ($booking['options'] as $option) : ?><tr>
			<th class="option-confirm-label"><?php echo apply_filters("option_confirm_label", $option->getLabel(), array('name' => $option->keyname)) ?></th>
			<td class="option-confirm-value">
				<?php echo apply_filters("option_confirm_text", esc_html($option->getText()), array('name' => $option->keyname)) ?><span class="option-confirm-note"><?php echo apply_filters("option_confirm_note", $option->getNote(), array('name' => $option->keyname)) ?></span>
			</td>
		</tr><?php endforeach; ?>
<?php
		return;
	}

	/**
	 * 請求明細の表示
	 *
	 * @opflag		オプションの有無
	 */
	private function _out_booking_bill($opflag)
	{
		$bill = $this->make_bill();

?>
		<tr>
			<td class="option-confirm-header" colspan="2"><?php echo apply_filters('booking_form_bill_title', 'ご請求') ?></td>
		</tr>
		<tr>
			<th class="bill-th">明細</th>
			<td class="bill-td">
				<table class="bill-details">
				<?php // 予約料金の表示
					if (0 < $bill->basic_charge) {
						$name = apply_filters('booking_form_charge_booking', $bill->article_name . ' 料金', 'confirm_bill');
						$this->_outconfirm_bill_row($name, 1, $bill->basic_charge);
					}
					// 人数料金の表示
					foreach (array('adult', 'child', 'baby') as $type) {
						if ($bill->number->$type != 0 && $bill->amount->$type != 0) {
							$name = apply_filters('booking_form_charge_count', $bill->article_name . ' ', 'confirm_bill')
							 . apply_filters('booking_form_count_label', __(ucwords($type), $this->domain), 'confirm_bill');
							$this->_outconfirm_bill_row($name, $bill->number->$type, $bill->amount->$type);
						}
					}
					// オプション料金の表示
					if ($opflag) {
						foreach ($bill->option_items as $item) {
							$this->_outconfirm_bill_row($item['name'], $item['number'], $item['price']);
						}
					}
				?>
				</table>
			</td>
		</tr>
		<tr>
		<?php if (0 < $this->charge['tax_notation']) : ?>
			<th class="bill-th">合計</th>
			<td class="bill-td"><div class="bill-total"><?php echo $this->money_format($bill->get_total()) ?></div></td>
		</tr>
		<tr>
			<th class="bill-th"><?php echo $this->charge['tax_notation'] == 1 ? '内' : '' ?>消費税(<?php echo $bill->tax ?>%)</th>
			<td class="bill-td"><div class="bill-tax"><?php echo $this->money_format($bill->get_amount_tax($this->charge['tax_notation'] == 1)) ?></div></td>
		</tr>
		<tr><?php endif; ?>
			<th class="bill-th">総合計</th>
			<td class="bill-td"><div class="bill-total"><?php echo $this->money_format($bill->get_total() + ($this->charge['tax_notation'] == 1 ? 0 : $bill->get_amount_tax())) ?></div></td>
		</tr>
<?php
		return;
	}

	/**
	 * 請求の明細表示
	 *
	 */
	private function _outconfirm_bill_row($title, $number, $unit) //, $cost)
	{ ?>
		<tr>
			<td class="bill-title"><?php echo $title ?></td>
			<td class="bill-number"><?php echo $number ?></td>
			<td class="bill-unit"><?php echo $this->money_format($unit) ?></td>
			<td class="bill-cost"><?php echo $this->money_format($number * $unit) ?></td>
		</tr><?php
	}


}