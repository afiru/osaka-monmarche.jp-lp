<?php
if (!class_exists('MTSSB_Booking')) {
	require_once(dirname(__FILE__) . '/mtssb-booking.php');
}
/**
 * MTS Simple Booking Calendar 予約カレンダー管理モジュール
 *
 * @Filename	mtssb-calendar-admin.php
 * @Date		2012-05-05
 * @Author		S.Hayashi
 *
 * Updated to 1.28.1 on 2018-01-25
 * Updated to 1.21.0 on 2015-01-19
 * Updated to 1.15.4 on 2014-09-05
 * Updated to 1.12.3 on 2014-04-08
 * Updated to 1.11.0 on 2013-10-28
 * Updated to 1.9.0 on 2013-07-19
 * Updated to 1.8.5 on 2013-07-08
 * Updated to 1.7.1 on 2013-06-26
 * Updated to 1.7.0 on 2013-05-08
 * Updated to 1.6.5 on 2013-04-29
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.4.5 on 2013-02-21
 * Updated to 1.4.0 on 2013-02-08
 * Updated to 1.3.0 on 2013-01-21
 * Updated to 1.2.0 on 2012-12-26
 * Updated to 1.1.0 on 2012-10-11
 */

class MTSSB_Calendar_Admin extends MTSSB_Booking
{
	const PAGE_NAME = MTS_Simple_booking::ADMIN_MENU;

	private static $iCal = null;

	private $controls;				// 予約入力条件

	private $articles = null;		// 予約品目
	private $schedule = array();	// 予約スケジュール

	private $clcols;				// 顧客入力項目

	// 操作対象データ
	private $themonth = 0;		// 当該カレンダーのunix time
	private $action = '';		// none or montly

	private $today_time;		// 本日0時0分のunix time

	private $message = '';
	private $errflg = false;

	/**
	 * インスタンス化
	 *
	 */
	static function get_instance() {
		if (!isset(self::$iCal)) {
			self::$iCal = new MTSSB_Calendar_Admin();
		}

		return self::$iCal;
	}

	public function __construct() {
		global $mts_simple_booking;

		parent::__construct();

		// CSSロード
		$mts_simple_booking->enqueue_style();

		// 予約条件パラメータのロード
		$this->controls = get_option($this->domain . '_controls');

		// 予約品目を読込む
		$this->articles = MTSSB_Article::get_all_articles();

		// 本日0時0分のUNIX Time
		$this->today_time = strtotime(date_i18n('Y-n-j'));
	}

	/**
	 * 管理画面メニュー処理
	 *
	 */
	public function calendar_page() {

		$this->errflg = false;
		$this->message = '';

		// 日付けを指定されていれば当該日の予約リスト表示へ
		if (isset($_GET['dt'])) {
			return $this->day_page(intval($_GET['dt']));
		}
		// 予約IDが指定されていれば当該予約詳細表示へ
		else if (isset($_GET['bid'])) {
			return $this->booking_page(intval($_GET['bid']));
		}
		// 予約表示で削除指定
		else if (isset($_GET['action']) && $_GET['action'] == 'delete') {
			if (wp_verify_nonce($_GET['nonce'], self::PAGE_NAME . '_delete')) {
				$booking = $this->get_booking(intval($_GET['booking_id']));
				if ($booking) {
					if ($this->del_booking($booking['booking_id'])) {
						$this->message = sprintf(__('Booking ID:%d was deleted.', $this->domain), $booking['booking_id']);
					} else {
						$this->message = sprintf(__('Deleting the booking data was failed.', $this->domain));
						$this->errflg = true;
					}
					return $this->day_page($booking['booking_time'] - $booking['booking_time'] % 86400);
				}
			}
		}

		// カレンダー対象年月
		if (isset($_GET['year']) && isset($_GET['month'])) {
			$this->themonth = mktime(0, 0, 0, $_GET['month'], 1, $_GET['year']);
		} else {
			$this->themonth = mktime(0, 0, 0, date_i18n('n'), 1, date_i18n('Y'));
		}

		// 対象年月のスケジュールを読込む
		foreach ($this->articles as $article_id => $article) {
			$key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->themonth);
			$this->schedule[$article_id] = get_post_meta($article_id, $key_name, true);
		}

		// 対象年月の予約カウントデータを読込む
		$this->reserved = $this->get_reserved_count(date_i18n('Y', $this->themonth), date_i18n('n', $this->themonth));

?>
	<div class="wrap">

		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php _e('Reservation Calendar', $this->domain) ?></h2>

		<?php $this->_reservation_months_link() ?>

		<?php $this->_reservation_calendar() ?>


	</div>
    <?php
	}

	/**
	 * 予約カレンダーの表示出力
	 */
	private function _reservation_calendar() {
		$weeks = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

		$this_time = mktime(0, 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('Y'));

		// カレンダー生成パラメータ
		$theyear = date_i18n('Y', $this->themonth);
		$themonth = date_i18n('n', $this->themonth);

		$days = (mktime(0, 0, 0, $themonth + 1, 1, $theyear) - $this->themonth) / 86400;

		$starti = date_i18n('w', $this->themonth);
		$endi = $starti + $days + 5 - date_i18n('w', mktime(0, 0, 0, $themonth, $days, $theyear));


?>
	<div class="reservation-table">
	<table>
		<tr>
			<?php foreach ($weeks as $wname) {
				$week = strtolower($wname);
				echo "<th class=\"column-title $week\">" . __($wname) . "</th>";
			} ?>
		</tr>

		<?php
			for ($i = 0, $day = 1 - $starti; $i <= $endi ; $i++, $day++) {
				// 行終了
				if ($i % 7 == 0) {
					echo (0 < $i ? "</tr>\n" : '') . "<tr>\n";
				}

				if (0 < $day && $day <= $days) {
					$week = strtolower($weeks[$i % 7]);
					$day = sprintf("%02d", $day);
					echo "<td class=\"calendar-box $week\">";
					echo "<div class=\"calendar-day $week\">$day</div>";
					echo '<div class="reservation-view">';
					$viewstr = __('View Booking', $this->domain);
					if (empty($this->schedule)) {
						echo $viewstr;
					} else {
						echo '<a href="?page=' . self::PAGE_NAME . '&amp;dt=' . mktime(0, 0, 0, $themonth, $day, $theyear) . "\">$viewstr</a>";
					}
					echo '</div>';
					$this->_reservation_of_the_day($theyear, $themonth, $day, $this_time);
				} else {
					echo '<td class="calendar-box no-day"> ';
				}
				echo "</td>\n";
			}
		?>
	</table>
	</div><!-- reservation-table -->

<?php
	}

	/**
	 * 指定日の予約情報を出力
	 *
	 */
	private function _reservation_of_the_day($year, $month, $day, $this_time) {

		$thetime = mktime(0, 0, 0, $month, $day, $year);

		foreach ($this->articles as $article_id => $article) {
			// 予約品目の表示
			echo '<br /><div class="reservation-item">';
			if (!empty($this->schedule[$article_id]) && $this->schedule[$article_id][$day]['open'] == 1) {
				echo '<div class="item-name">';
				if ($this_time <= $thetime) {
					echo "<a href=\"?page=simple-booking-booking&amp;dt={$thetime}&amp;article_id={$article_id}\">" . rawurldecode($article['name']) . '</a>';
				} else {
					echo rawurldecode($article['name']);
				}
				echo ':</div><div class="item-count">';
				if (isset($this->reserved[$thetime][$article_id])) {
				    echo $article['restriction'] == 'capacity'
				        ? $this->reserved[$thetime][$article_id]['number'] : $this->reserved[$thetime][$article_id]['count'];
				} else {
					echo '0';
				}
				echo ' / ';
			    echo (($article['restriction'] == 'capacity'
			        ? $article['capacity'] : $article['quantity']) + $this->schedule[$article_id][$day]['delta']) * count($article['timetable']);
			} else {
				echo '<div class="item-name closed">' . rawurldecode($article['name']) . ":</div>"
				 . '<div class="item-count closed">0 / 0';
			}
			echo '</div></div>';
		}

	}

	/**
	 * スケジュール月ページリンク
	 */
	private function _reservation_months_link() {
/*
		$this_year = date_i18n('Y');
		$this_month = date_i18n('n');
		$this_time = mktime(0, 0, 0, $this_month, 1, $this_year);
*/
		$theyear = date_i18n('Y', $this->themonth);
		$themonth = date_i18n('n', $this->themonth);

		// リンク
		$prev_month = mktime(0, 0, 0, $themonth - 1, 1, $theyear);
		$prev_str = date_i18n('Y-m', $prev_month);
		$next_month = mktime(0, 0, 0, $themonth + 1, 1, $theyear);
		$next_str = date_i18n('Y-m', $next_month);

?>
	<div id="reservation-prev-next">
		<h3><?php echo date_i18n('Y-m', $this->themonth) ?></h3>
		<ul class="subsubsub">
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;year=" . date_i18n('Y', $prev_month)
					 . "&amp;month=" . date_i18n('n', $prev_month) . "&amp;action=monthly\">$prev_str</a>"; ?> | </li>
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;year=" . date_i18n('Y', $next_month)
						 . "&amp;month=" . date_i18n('n', $next_month) . "&amp;action=monthly\">$next_str</a>"; ?></li>
		</ul>
		<div class="clear"> </div>
	</div>

<?php
	}

	/**
	 * リストオブジェクトからのレコード件数取得コール関数
	 *
	 */
	public function list_count() {

		if ($this->action == 'monthly') {
			return $this->get_booking_count_monthly(date_i18n('Y', $this->themonth), date_i18n('n', $this->themonth));
		} else {
			return $this->get_booking_count();
		}
	}

	/**
	 * リストオブジェクトからのデータ取得コール関数
	 *
	 */
	public function read_data($offset, $limit, $order) {

		if ($this->action == 'monthly') {
			$conditions = 'booking_time>=' . mktime(0, 0, 0, date_i18n('n', $this->themonth), 1, date_i18n('Y', $this->themonth))
				. ' AND booking_time<' . mktime(0, 0, 0, date_i18n('n', $this->themonth) + 1, 1, date_i18n('Y', $this->themonth));
			return $this->get_booking_list($offset, $limit, $order, $conditions);
		}

		return $this->get_booking_list($offset, $limit, $order);
	}

	/**
	 * 指定日予約一覧
	 *
	 */
	public function day_page($daytime)
    {
		// NONCE
		$nonce = wp_create_nonce(self::PAGE_NAME . '_adjustment');

		// 予約調整データの処理
		if (isset($_POST['action']) && $_POST['action'] == 'adjust' && $_POST['nonce'] == $nonce) {
			$article_id = intval($_POST['booking']['article_id']);
			// 予約品目があるか確認して調整処理を実行する
			if (array_key_exists($article_id, $this->articles)) {
				foreach ($_POST['booking']['booking_time'] as $booking_time => $number) {
					if ($this->adjust_booking($article_id, $this->articles[$article_id]['restriction'], intval($booking_time), intval($number)) === false) {
						$this->message = __('To adjust booking has been failed.', $this->domain);
						$this->errflg = true;
						break;
					} else {
						$this->message = __('Booking has been adjusted right.', $this->domain);
					}
				}
			}
		}

		// 当該日のUnix Time
		$daytime = $daytime - $daytime % 86400;
		$prevtime = $daytime - 86400;
		$nexttime = $daytime + 86400;

?>
	<div class="wrap">

		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php _e('Reservation Calendar > Day', $this->domain) ?></h2>

		<?php if (!empty($this->message)) : ?><div class="<?php echo $this->errflg ? 'error' : 'updated' ?>">
			<p><?php echo $this->message ?></p>
		</div><?php endif; ?>

		<ul class="subsubsub">
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;year=" . date_i18n('Y', $daytime)
					 . "&amp;month=" . date_i18n('n', $daytime) . '&amp;action=monthly">' . __('Reservation Calendar', $this->domain) . '</a>'; ?> | </li>
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;dt=$prevtime\">" . __('Previous Day', $this->domain) ?></a> | </li>
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;dt=$nexttime\">" . __('Next Day', $this->domain) ?></a></li>
		</ul>
		<div class="clear"> </div>

		<h3><?php echo date_i18n(__('F j, Y'), $daytime) . ' (' . __(date_i18n('D', $daytime)) . ')' ?></h3>
		<?php if ($this->today_time <= $daytime) : ?><p><?php _e('The number of booking can be adjusted by setting a number to reduce to a text box.', $this->domain) ?></p><?php endif; ?>

		<?php foreach ($this->articles as $article_id => &$article) {
			echo $this->_day_article($article_id, $daytime, $nonce);
		} ?>

	</div>

<?php
	}

	/**
	 * 予約品目1件について表示
	 *
	 */
	protected function _day_article($article_id, $daytime, $nonce)
    {
		$article = $this->articles[$article_id];

		// 収容人数ならtrue, 予約件数ならfalse
		$capacity = $article['restriction'] == 'capacity' ? true : false;

		// 予約カレンダー指定日 予約者情報表示の追加情報確認
		$info = array('article_id' => $article_id, 'adding' => false, 'float' => true, 'item' => array(), 'option' => array(), 'separator' => ',');
		$newi = apply_filters('mtssb_admin_calendar_info', $info);
		if (is_array($newi) && $newi['adding']) {
			$info = array_merge($info, $newi);
		}

		// 対象年月のスケジュールを読込む
		$key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $daytime);
		$schedule = get_post_meta($article_id, $key_name, true);

		ob_start();
?>

	<div class="article-each">
<?php if (empty($schedule)) : ?>
        <h4><?php echo $article['name'] . " (" . __('Not scheduled', $this->domain) . ')' ?></h4>
<?php else : ?>
		<h4><?php
            echo $article['name'] . " (" . __(ucfirst($article['restriction']), $this->domain) . ":" . ($capacity ? $article['capacity'] : $article['quantity']);
            echo ' + ' . __('Inc & Dec:', $this->domain) . $schedule[date_i18n('d', $daytime)]['delta'] . ')';
        ?></h4>
		<form method="post" action="<?php echo '?page=' . self::PAGE_NAME . "&amp;dt={$daytime}" ?>" name="article_<?php echo $article_id ?>">
			<?php foreach ($article['timetable'] as $time) :
				$thetime = $daytime + $time;
				$reserved = $this->get_booking_of_thetime($thetime, $article_id);

				// 予約数をcapacity,quantity別で計算する
				$number = 0;
				foreach ($reserved as &$booking) {
					$number += $capacity ? intval($booking['number']) : 1;
				}
				$adjustment = $this->_retrieve_adjustment($reserved);
			?><div class="article-time">
				<div class="booking-time"><?php echo date_i18n('H:i', $time) ?></div>
				<?php if ($daytime < $this->today_time) {
					echo '<div class="booking-number-adjusted">' . $number;
				} else {
					echo '<div class="booking-number">';
					echo "<input type=\"text\" class=\"small-text\" name=\"booking[booking_time][$thetime]\" value=\"" . (empty($adjustment) ? '0' : ($capacity ? $adjustment[0]['number'] : count($adjustment))) . "\" />";
					echo " $number";
				} ?></div>
				<div class="booking-each"><?php
				if (!empty($reserved)) {
					foreach ($reserved as &$reserve) {
						echo '<div class="client-name"' . ($info['float'] ? '' : ' style="float:none;"') . '>';
						if ($reserve['parent_id'] <= 0) {
							$client = &$reserve['client'];
							echo '<a href="?page=' . self::PAGE_NAME . "&amp;bid={$reserve['booking_id']}\">";
							echo empty($client['name']) ? __('No Name', $this->domain) : esc_html($client['name']);
							echo "</a>({$reserve['number']})";
							// 予約者情報表示の追加情報
							if ($info['adding']) {
								$this->_adding_info($info, $reserve);
							}
						} else {
							$client = &$reserve['parent'];
							echo empty($client['name']) ? __('No Name', $this->domain) : esc_html($client['name']);
							echo "({$reserve['number']})";
						}
						echo "</div>";
					}
				} else {
					echo '<div class="client-name">' . __('Nobody Reserved', $this->domain) . '</div>';
				}

				if (!empty($adjustment)) {
					echo '<div class="client-name">' . __('Adjusted', $this->domain) . '</div>';
				} ?>

				</div><!-- booking-each -->
				<div class="clear"> </div>
			</div><!-- article-time --><?php endforeach; ?>

			<?php if ($this->today_time <= $daytime) : ?>
				<div class="booking-adjustment">
					<button type="submit" name="action" value="adjust" class="button button-secondary"><?php _e('Adjust', $this->domain) ?></button>
				</div>
				<input type="hidden" name="booking[article_id]" value="<?php echo $article_id ?>" />
				<input type="hidden" name="nonce" value="<?php echo $nonce ?>" />
			<?php endif; ?>
		</form>
    <?php endif; ?></div>

<?php
		return ob_get_clean();
	}

	/**
	 * 予約者情報を追加表示する
	 *
	 */
	private function _adding_info($info, &$reserve)
	{
		$client = &$reserve['client'];

		foreach ($info['item'] as $item) {
			$str = '';
			switch ($item) {
				case 'company' :
					$str = esc_html($client['company']);
					break;
				case 'furigana' :
					$str = esc_html($client['furigana']);
					break;
				case 'email' :
					$str = apply_filters('mtssb_admin_calendar_email', $client['email']);
					break;
				case 'tel' :
					$str = esc_html($client['tel']);
					break;
				case 'postcode' :
					$str = esc_html($client['postcode']);
					break;
				case 'address' :
					$str = esc_html($client['address1'] . (empty($client['address2']) ? '' : " {$client['address2']}"));
					break;
				case 'option' :
					// 予約データをオブジェクトに変換する
					$booking = $this->array_merge_default(
						$this->new_booking($reserve['booking_time'], $reserve['article_id']), $reserve);
					foreach ($info['option'] as $keyname) {
						foreach ($booking['options'] as $option) {
							if ($keyname == $option->keyname) {
								$str .= (empty($str) ? '' : $info['separator'])
								 . ($keyname == 'textarea' ? esc_html($option->getText()) : nl2br(esc_textarea($option->getText())));
								break;
							}
						}
					}
					// オブジェクトを削除
					unset($booking);
					break;
				case 'newuse' :
					$str = apply_filters('mtssb_admin_calendar_newuse', ($client['newuse'] == 1 ? 'Yes' : (empty($client['newuse']) ? '' : 'No')));
					break;
				case 'note' :
					$str = nl2br(esc_textarea($reserve['note']));
					break;
				default :
					break;
			}

			echo $info['separator'] . $str;
		}
	}

	/**
	 * 予約データの中からuser_id=-1の調整予約データを取り出す
	 *
	 */
	private function _retrieve_adjustment(&$abooking) {
		$keys = array();
		$adjustment = array();

		if (!empty($abooking)) {
			// 調整予約データを拾い出す
			foreach ($abooking as $key => $booking) {
				if ($booking['user_id'] == -1) {
					$keys[] = $key;
					$adjustment[] = $booking;
				}
			}

			// 調整予約データを削除する
			foreach ($keys as $key) {
				unset($abooking[$key]);
			}
		}

		// 調整予約データを戻す
		return $adjustment;
	}

	public function booking_page($booking_id) {
		global $mts_simple_booking;

		$this->booking = $booking = $this->get_booking($booking_id);

		// データを読込んで編集データbookingタイプにする
		//$this->booking = $this->array_merge_default($this->new_booking(), $booking);

		if ($booking) {
			$article = MTSSB_Article::get_the_article($booking['article_id']);
			$daytime = $booking['booking_time'] - $booking['booking_time'] % 86400;
			$datestr = date_i18n(__('F j, Y'), $daytime) . ' (' . __(date_i18n('D', $daytime)) . ')';
		}

		// 予約条件パラメータのロード
		$count = $this->controls['count'];

		// 支払データのロード
		$charge = get_option($this->domain . '_charge');
		if ($charge['charge_list']) {
			$bill = $this->make_bill();
			$currency = ' ' . ($bill->currency_code == 'JPY' ? __('Yen', $this->domain) : __('US dollar', $this->domain));
		}

?>
	<div class="wrap">

		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php _e('Reservation Calendar > Day > Booking', $this->domain) ?></h2>

<?php if (!empty($booking)) : ?>
		<ul class="subsubsub">
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;year=" . date_i18n('Y', $daytime)
					 . "&amp;month=" . date_i18n('n', $daytime) . '&amp;action=monthly">' . __('Reservation Calendar', $this->domain) . '</a>'; ?> | </li>
			<li><?php echo '<a href="?page=' . self::PAGE_NAME . "&amp;dt={$daytime}\">" . __('Reservation Day', $this->domain) . '</a>'; ?> | </li>
			<li><?php echo '<a href="?page=' . MTS_Simple_Booking::PAGE_BOOKING . "&amp;booking_id={$booking['booking_id']}&amp;action=edit\">" . __('Edit') .'</a>'; ?> | </li>
			<li><?php echo sprintf("<a href=\"?page=%s&amp;action=delete&amp;booking_id=%d&amp;nonce=%s\" onclick=\"return confirm('%s')\">%s</a>"
						, self::PAGE_NAME, $booking['booking_id'], wp_create_nonce(self::PAGE_NAME . '_delete'), __('Do you really want to delete this booking?', $this->domain), __('Delete')); ?></li>
		</ul>
		<div class="clear"> </div>

		<h3><?php echo $datestr ?></h3>

		<table class="form-table booking-detail">
			<tr>
				<th><?php _e('Article Name', $this->domain) ?></th>
				<td><?php echo $article['name'] ?></td>
			</tr>
			<tr>
				<th><?php _e('Date Time', $this->domain) ?></th>
				<td><?php echo $datestr; ?></td>
			</tr>
			<tr>
				<th><?php _e('Number', $this->domain) ?></th>
				<td><?php echo $booking['number'] ?></td>
			</tr>
			<tr>
				<th><?php _e('Options', $this->domain) ?></th>
				<td><?php
					foreach ($booking['options'] as $option) {
						echo $option->getLabel() . ' : ';
						echo $option->type == 'textarea' ? nl2br(esc_textarea($option->getText())) : esc_html($option->getText());
						echo '<br />';
					}
				?></td>
			</tr>
			<tr>
				<th><?php _e('Company', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['company']) ?></td>
			</tr>
			<tr>
				<th><?php _e('Name') ?></th>
				<td><?php echo empty($booking['client']['name']) ? __('No Name', $this->domain) : esc_html($booking['client']['name']) ?></td>
			</tr>
			<tr>
				<th><?php _e('Furigana', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['furigana']) ?></td>
			</tr>
			<?php if ($booking['client']['birthday']->isSetDate()) : ?><tr>
				<th><?php _e('Birthday', $this->domain) ?></th>
				<td><?php echo $booking['client']['birthday']->get_date('j') ?></td>
			</tr><?php endif; ?>
			<?php if (!empty($booking['client']['gender'])) : ?><tr>
				<th><?php _e('Gender', $this->domain) ?></th>
				<td><?php echo $booking['client']['gender'] == 'male' ? __('Male', $this->domain) : __('Female', $this->domain) ?></td>
			</tr><?php endif; ?>
			<tr>
				<th><?php _e('E-Mail', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['email']) ?></td>
			</tr>
			<tr>
				<th><?php _e('Postcode', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['postcode']) ?></td>
			</tr>
			<tr>
				<th><?php _e('Address', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['address1']) . '<br />' . esc_html($booking['client']['address2']) ?></td>
			</tr>
			<tr>
				<th><?php _e('TEL', $this->domain) ?></th>
				<td><?php echo esc_html($booking['client']['tel']) ?></td>
			</tr>
			<tr>
				<th><?php echo apply_filters('booking_form_newuse', __('Newuse', $this->domain), 'admin') ?></th>
				<td><?php echo empty($booking['client']['newuse']) ? '' : ($booking['client']['newuse'] == 1 ? apply_filters('booking_form_newuse_yes', __('Yes')) : apply_filters('booking_form_newuse_no', __('No'))) ?></td>
			</tr>
			<tr>
				<th><?php _e('Number', $this->domain) ?></th>
				<td><?php foreach ($count as $key => $val) {
						echo '<div class="number-person">' . apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'calendar_admin') . '<br />'
						 . $booking['client'][$key] . '</div>';
					 } ?></td>
				</td>
			</tr>
			<tr>
				<th><?php _e('Message', $this->domain) ?></th>
				<td><?php echo nl2br(esc_html($booking['note'])) ?></td>
			</tr>
			<?php if ($charge['charge_list']) : ?><tr>
				<th><?php _e('Bill', $this->domain) ?></th>
				<td>
					<table>
						<tr>
							<th class="bill-th">明細</th>
							<td class="bill-td">
								<table class="bill-details">
								<?php // 予約料金の表示
									if (0 < $bill->basic_charge) {
										$name = apply_filters('booking_form_charge_booking', $bill->article_name . ' 料金', 'calendar_admin');
										$this->_out_bill_row($name, 1, $bill->basic_charge, $currency);
									}
									// 人数料金の表示
									foreach (array('adult', 'child', 'baby') as $type) {
										if ($bill->number->$type != 0) {
											$name = apply_filters('booking_form_charge_count', $bill->article_name . ' ', 'calendar_admin')
											 . apply_filters('booking_form_count_label', __(ucwords($type), $this->domain), 'calendar_admin');
											$this->_out_bill_row($name, $bill->number->$type, $bill->amount->$type, $currency);
										}
									}
									// オプション料金の表示
									foreach ($bill->option_items as $item) {
										$this->_out_bill_row($item['name'], $item['number'], $item['price'], $currency);
									}
								?>
								</table>
							</td>
						<tr>
						<?php if (0 < $charge['tax_notation']) : ?>
							<th class="bill-th">合計</th>
							<td class="bill-td"><div class="bill-total"><?php echo number_format($bill->get_total()) . $currency ?></div></td>
						</tr>
						<tr>
							<th class="bill-th"><?php echo $charge['tax_notation'] == 1 ? '内' : '' ?>消費税(<?php echo $bill->tax ?>%)</th>
							<td class="bill-td"><div class="bill-tax"><?php echo number_format($bill->get_amount_tax($charge['tax_notation'] == 1)) . $currency ?></div></td>
						</tr>
						<tr><?php endif; ?>
							<th class="bill-th">総合計</th>
							<td class="bill-td"><div class="bill-total"><?php echo number_format($bill->get_total() + ($charge['tax_notation'] == 1 ? 0 : $bill->get_amount_tax())) . $currency ?></div></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<th><?php _e('PayPal Transaction ID', $this->domain) ?></th>
				<td><?php echo isset($booking['client']['transaction_id']) ? $booking['client']['transaction_id'] : '' ?></td>
			</tr><?php endif; ?>
			<tr>
				<th><?php _e('Created', $this->domain) ?></th>
				<td><?php echo $booking['created'] ?></td>
			</tr>
			<tr>
				<th><?php _e('User Agent', $this->domain) ?></th>
				<td><?php echo isset($booking['client']['user_agent']) ? esc_html($booking['client']['user_agent']) : '' ?></td>
			</tr>
			<tr>
				<th><?php _e('Remote Address', $this->domain) ?></th>
				<td><?php echo isset($booking['client']['remote_addr']) ? esc_html($booking['client']['remote_addr']) : '' ?></td>
			</tr>
		</table>
<?php else : ?>
		<?php _e('No Data', $this->domain); ?>
<?php endif; ?>
	</div>

<?php
	}

	/**
	 * 請求の明細表示
	 *
	 */
	private function _out_bill_row($title, $number, $unit, $currency)
	{ ?>
		<tr>
			<td class="bill-title"><?php echo $title ?></td>
			<td class="bill-number"><?php echo $number ?></td>
			<td class="bill-unit"><?php echo number_format($unit) . $currency ?></td>
			<td class="bill-cost"><?php echo number_format($number * $unit) . $currency ?></td>
		</tr><?php
	}

}
