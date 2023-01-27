<?php
/**
 * MTS Simple Booking フロント処理モジュール
 *
 * @Filename	mtssb-front.php
 * @Date		2012-05-08
 * @Author		S.Hayashi
 *
 * Updated to 1.32.0 on 2019-11-25
 * Updated to 1.31.1 on 2019-09-30
 * Updated to 1.30.0 on 2019-02-07
 * Updated to 1.29.0 on 2019-01-08
 * Updated to 1.28.3 on 2018-03-01
 * Updated to 1.28.0 on 2017-10-26
 * Updated to 1.23.2 on 2016-06-29
 * Updated to 1.22.0 on 2015-10-28
 * Updated to 1.19.0 on 2014-11-03
 * Updated to 1.17.0 on 2014-07-04
 * Updated to 1.16.0 on 2014-06-09
 * Updated to 1.10.0 on 2013-10-14
 * Updated to 1.7.2 on 2013-07-12
 * Updated to 1.7.1 on 2013-07-05
 * Updated to 1.7.0 on 2013-05-14
 * Updated to 1.6.5 on 2013-04-30
 * Updated to 1.6.0 on 2013-03-18
 * Updated to 1.5.0 on 2013-03-01
 * Updated to 1.2.5 on 2013-02-07
 * Updated to 1.2.0 on 2012-12-26
 * Updated to 1.1.5 on 2012-12-02
 * Updated to 1.1.2 on 2012-11-28
 */
if (!class_exists('MTSSB_Booking')) {
    require_once(__DIR__ . '/mtssb-booking.php');
}
if (!class_exists('MtssbCalendar')) {
    require_once(__DIR__ . '/lib/MtssbCalendar.php');
}
if (!class_exists('MtssbCalendarView')) {
    require_once(__DIR__ . '/lib/MtssbCalendarView.php');
}

class MTSSB_Front extends MTSSB_Booking
{
	// 予約条件設定
	private $controls = array();

    // カレンダー処理オブジェクト
    private $calendar = NULL;

	// 予約カレンダー表示　データベース
	private $articles = array();
	private $article = array();
	private $schedule = array();
	private $reserved = array();
    private $params = array();

    // カレンダー表示ビュー
    private $view = null;

	// 表示ページのURL
	private $this_page = '';

	// 予約フォームページのURL
	private $form_link = '';

	/**
	 * Constructor
	 *
	 */
	public function __construct()
    {
		parent::__construct();

        // 日時の初期設定・カレンダー情報の取得
        $this->calendar = new MtssbCalendar($this->domain);
        $this->view = new MtssbCalendarView;

		// Controlsのロード
		$this->controls = $this->calendar->controls;

		// 表示ページのURL
		$this->this_page = get_permalink();

		// 予約フォームページのURL
		$this->form_link = get_permalink(get_page_by_path(MTS_Simple_Booking::PAGE_BOOKING_FORM));
    }

	/**
	 * 月間予約カレンダー出力
	 *
	 */
	public function monthly_calendar($atts)
    {
		// 予約受付終了状態
		if (empty($this->controls['available'])) {
			return $this->controls['closed_page'];
		}

		// ショートコードパラメータの初期化
        $params = $this->calendar->commonParams();
        $params['class'] = 'monthly-calendar';
		$params = $this->params = shortcode_atts($params, $atts);

        // カレンダーID指定の確認
        if (isset($_GET['cid']) && $_GET['cid'] != $params['calendar_id']) {
            return '';
        }

		// パラメータで年月日が指定された場合は当該日の予約表示をする
		if (0 < $params['day']) {
			$daytime = mktime(0, 0, 0, $params['month'], $params['day'], $params['year']);
            return $this->_dayTimetable($daytime);
		}

		// 日付が指定されたら当該日の予約表示をする
		if (isset($_GET['ymd'])) {
            $daytime = (int) $_GET['ymd'];
            if ($this->calendar->todayTime <= $daytime && $daytime < $this->calendar->openNextMonth) {
                return $this->_dayTimetable($daytime);
            }
		}

        // 予約品目の取得
        $this->articles = MTSSB_Article::get_all_articles($params['id']);
        if (empty($this->articles)) {
        	return __('Not found any articles of reservation.', $this->domain);
        }
        $this->article = array_shift($this->articles);

        // カレンダータイトル
        $params['title'] = isset($atts['title']) ? $atts['title'] : $this->article['name'];

        // 出力月数
        $term = $params['term'];

        ob_start();

        echo apply_filters('mtssb_calendar_before', '', $params['calendar_id']);

        while (0 < $term) :

        // カレンダー表示月を決定する(yyyy年mm月1日)
        $this->calendar->defCalendarTime($params);

        // 翌月のUnix Time
        $nextTime = mktime(0, 0, 0, $this->calendar->calendarMonth + 1, 1, $this->calendar->calendarYear);

		// 対象年月のスケジュールを読込む
		$key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->calendar->calendarTime);
		$this->schedule = get_post_meta($this->article['article_id'], $key_name, true);

		// 対象年月の予約カウントデータを読込む
		$this->reserved = $this->get_reserved_count($this->calendar->calendarYear, $this->calendar->calendarMonth);

        // 先頭曜日と1日の曜日差を求める
        $startWeek = date_i18n('w', $this->calendar->calendarTime);
        $offsetDay = ($startWeek - $this->calendar->startOfWeek + 7) % 7;

        // カレンダー先頭Unix Time
        $startTime = $this->calendar->calendarTime - $offsetDay * 86400;

        // アンカー指定
        $anchor = '';
        if ($params['anchor'] && !$params['widget'] && $term == $params['term']) {
            $anchor = sprintf(' id="%s"', $params['anchor']);
        }

?>
    <div<?php echo sprintf('%s class="%s"', $anchor, $params['class']) ?>>
    <?php // タイトル表示
        echo $this->view->calendarTitle($params); ?>

	<table>
<?php
        // キャプション・月リンク表示
        echo $this->view->captionPagination($params, $this->calendar);

        // 曜日ヘッダー表示
        echo $this->view->weekHeader($startTime, $params['weeks']);

        // カレンダー表示
        for ($i = 0, $dayTime = $startTime; $i <= 42; $i++, $dayTime += 86400) {
            if ($i % 7 == 0) {
                // 行末表示
                echo 0 < $i ? "</tr>\n" : '';
                // 月末チェック
                if ($nextTime <= $dayTime) {
                    break;
                }
                echo '<tr class="week-row">' . "\n";
            }

            if ($this->calendar->calendarTime <= $dayTime && $dayTime < $this->calendar->nextTime) {
                $this->_reservation_of_the_day($dayTime, $params);
            } else {
                echo '<td class="day-box no-day"></td>' . "\n";
            }
        }
?>

	</table>
	<?php if ($params['pagination'] == 1 || $params['pagination'] == 3) {
        echo $this->view->pagination($params, $this->calendar);
	} ?>

	</div>

<?php
        $params['year'] = date_i18n('Y', $this->calendar->nextTime);
        $params['month'] = date_i18n('n', $this->calendar->nextTime);
        $term--;
        endwhile;

        echo apply_filters('mtssb_calendar_after', '', $params['calendar_id']);

		return ob_get_clean();
	}

	/**
	 * 指定日の予約情報を出力
	 *
	 * @thetime		ymd unixtime
	 */
	private function _reservation_of_the_day($thetime, $params)
    {
		$link = $params['link'];
		$skip = $params['skiptime'];
		$cid = $params['calendar_id'];
		$suppression = $params['suppression'];
		$anchor = $params['anchor'];
		$low = $params['low'];

		$idxday = date_i18n('d', $thetime);
		$week = strtolower(date('D', $thetime));

		// 予約率を求めるパラメータの初期値設定
		$sum = $rsvdnum = $remain = $rate = 0;

		//extract($this->article);

		// スケジュールデータのセット
		if (isset($this->schedule[$idxday])) {
			$schedule = $this->schedule[$idxday];
		} else {
			$schedule = array(
				'open' => 0,
				'delta' => 0,
				'class' => '',
				'note' => '',
			);
		}

		// 予約可能総数を求める
		if ($schedule['open']) {

			$sum = $this->article['restriction'] == 'capacity' ? $this->article['capacity'] : $this->article['quantity'];
			$sum += intval($schedule['delta']);
			$sum *= count($this->article['timetable']);

			// low判定数
			$lows = $low * count($this->article['timetable']);
		}

		// 予約数を求める
		if (isset($this->reserved[$thetime][$this->article['article_id']])) {
			$reserved = $this->reserved[$thetime][$this->article['article_id']];
			$rsvdnum = intval($this->article['restriction'] == 'capacity' ? $reserved['number'] : $reserved['count']);
		}

		// 予約残数・予約残率
		if (0 < $sum ) {
			$remain = $sum - $rsvdnum;
			$rate = $remain * 100 / $sum;
		}

        // 表示マーク
        if ($this->controls['vacant_rate'] < $rate && $lows < $remain) {
            $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
        } else if ($rate <= 0) {
            $mark = 'full';
        } else {
            $mark = 'low';
        }

        // 表示不可マーク
        $timetableDay = $this->calendar->isTimetableDay($thetime);
        if (empty($schedule['open']) || $thetime < $this->calendar->todayTime) {
            $mark = 'disable';
        } elseif (!$timetableDay && !$this->controls['output_margin']) {
            $mark = 'disable';
        }

		// 予約カレンダーからそのまま予約フォームへリンクする指定がある場合
		$linkurl = '';
        if ($mark != 'disable') {
            if ($skip && $timetableDay && $link) {
                $linkurl = esc_url(add_query_arg(array('aid' => $this->article['article_id'], 'utm' => $thetime + $this->article['timetable'][0]), $this->form_link));
            } else {
                $arg = array('aid' => $this->article['article_id'], 'ymd' => $thetime) + (empty($cid) ? array() : array('cid' => $cid));
                $linkurl = htmlspecialchars(add_query_arg($arg, $this->this_page)) . (empty($anchor) ? '' : "#{$anchor}");
            }
        }
/*
		if ($link && $timetableDay) {
			if ($skip) {
				$linkurl = esc_url(add_query_arg(array('aid' => $this->article['article_id'], 'utm' => $thetime + $this->article['timetable'][0]), $this->form_link));
			} else {
				$arg = array('aid' => $this->article['article_id'], 'ymd' => $thetime) + (empty($cid) ? array() : array('cid' => $cid));
				$linkurl = htmlspecialchars(add_query_arg($arg, $this->this_page)) . (empty($anchor) ? '' : "#{$anchor}");
			}
		}
*/
		// マーク・リンク表示(記号または残数)
        $marking = $this->calendar->getMarking($mark, $remain);
		$linktext = apply_filters('mtssb_monthly_calendar_marking', $marking, $cid, $mark, $remain);

		// TD Box
		echo "<td class=\"day-box $week $mark" . ($thetime == $this->calendar->todayTime ? ' today' : '')
		 . (empty($schedule['class']) ? '' : " {$schedule['class']}") . '">';
		// 日付
		echo '<div class="day-number">' . apply_filters('mtssb_day', (int) $idxday, array('day' => $thetime, 'cid' => $cid)) . '</div>';

		// disableの(非)表示
		echo '<div class="calendar-mark">';
		if ($mark == 'disable') {
			if ($suppression != 1) {
				echo $linktext;
			}
		}
		// full,low,booking,vacantの表示
		else {
            if (($mark == 'full' && $params['linkfull'] == 0) || empty($linkurl)) {
                echo $linktext;
            } else {
                echo '<a class="calendar-daylink" href="' . $linkurl . '">' . $linktext . '</a>';
            }
        }
		echo "</div>";

		// スケジュール注記の表示
		$note = empty($schedule['note']) ? '' : $schedule['note'];
		if (!empty($note)) {
			echo apply_filters('mtssb_monthly_schedule_note', "<div class=\"schedule-note\">$note</div>", $cid);
		}

		echo "</td>\n";
	}

    /**
     * 予約日の時間割カレンダー出力
     *
     */
    private function _dayTimetable($dayTime)
    {
        // 予約品目
        $aids = explode(',', $this->params['id']);
        if (isset($_GET['aid']) && in_array($_GET['aid'], $aids)) {
            $articleId = intval($_GET['aid']);
        } else {
            $articleId = intval($aids[0]);
        }

        // 予約品目取得
        $this->articles = MTSSB_Article::get_all_articles($articleId);

        // 予約品目チェック
        if (!in_array($articleId, $aids) || empty($this->articles)) {
            return __('Not found any articles of reservation.', $this->domain);
        }

        // スケジュールを取得する
        $metaName = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $dayTime);
        foreach ($this->articles as $articleId => &$article) {
            $schedule = get_post_meta($articleId, $metaName, true);
            $article['oSchedule'] = $this->_daySchedule(date('d', $dayTime), $schedule);
        }

        // 対象日付の予約カウントデータを取得する
        $this->reserved = $this->get_reserved_day_count($dayTime);

        // アンカーID
        $anchor = $this->params['anchor'] ? sprintf(' id="%s"', $this->params['anchor']) : '';

        ob_start();
?>
        <div class="<?php echo $this->params['class'] ?> day-calendar"<?php echo $anchor ?>>
            <?php
            echo apply_filters('mtssb_timetable_before', '', array('cid' => $this->params['calendar_id'], 'aid' => $articleId));

            foreach ($this->articles as $articleId => $article) {
                // スケジュールがクローズの場合は表示しない
                if (!$article['oSchedule']->open) {
                    continue;
                }

                // 時間割の予約状況を取得する
                $tableInfo = $this->_timetableMark($dayTime, $article);

                if ($this->params['dayform']) {
                    // セレクトボックスフォーム表示
                    echo $this->view->timetableSelect($dayTime, $this->params, $article, $tableInfo);
                } else {
                    // 時間割テーブルリンク表示
                    echo $this->view->timetableLink($dayTime, $this->params, $article, $tableInfo);
                }
            }

            if ($this->params['widget'] != 1) : ?>
            <div class="mtssb-daily-action">
                <button type="button" onclick="history.back()"><?php echo apply_filters('mtssb_timetable_return', '戻る', $this->params['calendar_id']) ?></button>
            </div><?php endif; ?>

            <?php echo apply_filters('mtssb_timetable_after', '', array('cid' => $this->params['calendar_id'], 'aid' => $articleId)); ?>

        </div>

<?php
        return ob_get_clean();
    }

    // 時間割予約状況を取得する
    private function _timetableMark($dayTime, $article)
    {
        $marks = array();
        $articleId = $article['article_id'];

        // 時間割に予約者名を表示する(clientの項目名の指定)
        $whom = apply_filters('mtssb_time_booking_client', '', $article['article_id'], $this->params['calendar_id']);

        // 予約可能数総数を求める
        $sum = $article['oSchedule']->delta + $article[$article['restriction']];

        foreach ($article['timetable'] as $time) {
            $bookingTime = $dayTime + $time;

            // 予約者情報の取得
            $names = '';
            if (!empty($whom)) {
                $bookings = $this->get_booking_of_thetime($bookingTime, $articleId);
                if (!empty($bookings)) {
                    foreach ($bookings as $booking) {
                        //var_dump($booking['client']);
                        $names .= (empty($names) ? '' : ',')
                            . (empty($booking['client']) ? $booking['parent'][$whom] : $booking['client'][$whom]);
                    }
                }
            }
            $names = esc_html($names);

            // 予約受付開始時刻の確認
            $mark = 'disable';
            $rsvdnum = $remain = $rate = 0;

            if (current_time('timestamp') < $bookingTime) {
                // 予約数を求める
                if (isset($this->reserved[$bookingTime][$articleId])) {
                    if ($article['restriction'] == 'capacity') {
                        $rsvdnum = (int)$this->reserved[$bookingTime][$articleId]['number'];
                    } else {
                        $rsvdnum = (int)$this->reserved[$bookingTime][$articleId]['count'];
                    }
                }

                // 予約残数・予約残率
                if (0 < $sum) {
                    $remain = $sum - $rsvdnum;
                    $rate = $remain * 100 / $sum;
                }

                // 表示マーク
                if ($remain <= 0) {
                    $mark = 'full';
                } elseif ($rate <= $this->controls['vacant_rate'] || $remain <= $this->params['low']) {
                    $mark = 'low';
                } else {
                    $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
                }
            }

            $marks[$time] = (object)array(
                'names' => $names,
                'mark' => $mark,
                'remain' => $remain,
                'marking' => $this->calendar->getMarking($mark),
                'link' => $this->calendar->isBookingTime($bookingTime),
            );
        }

        return $marks;
    }

    // スケジュールデータの取得
    private function _daySchedule($day, $schedule)
    {
        $dayIdx = sprintf("%02d", $day);

        $oSchedule = isset($schedule[$dayIdx]) ? (object) $schedule[$dayIdx] : (object) array(
            'open' => 0,
            'delta' => 0,
            'class' => '',
            'note' => '',
        );

        return $oSchedule;
    }

	/**
	 * 月間予約マルチカレンダー出力
	 *
	 */
	public function multiple_calendar($atts)
    {
		// 予約受付終了状態
		if (empty($this->controls['available'])) {
			return $this->controls['closed_page'];
		}

		// ショートコードパラメータの初期化
        $params = $this->params = shortcode_atts(array_merge($this->calendar->commonParams(), array(
			'class' => 'multiple-calendar',
			'href' => '',
			'title' => '予約カレンダー',   // 複合予約カレンダータイトル
        )), $atts);

        // カレンダーID指定の確認
        if (isset($_GET['cid']) && $_GET['cid'] != $params['calendar_id']) {
            return '';
        }

        // 日付が指定されたら当該日の予約表示をする
        if (isset($_GET['ymd'])) {
            $daytime = intval($_GET['ymd']);
            if ($this->calendar->lastDayTime <= $daytime && $daytime < $this->calendar->openTime) {
                return $this->_dayTimetable($daytime);
            }
        }

        // 表示対象の予約品目を取得する
        $this->articles = MTSSB_Article::get_all_articles($params['id']);
        if (empty($this->articles)) {
            return __('Not found any articles of reservation.', $this->domain);
        }

        // 出力月数
        $term = $params['term'];

        ob_start();

        echo apply_filters('mtssb_calendar_before', '', $params['calendar_id']);

        while (0 < $term) :

        // カレンダー表示月を決定する(yyyy年mm月1日)
        $this->calendar->defCalendarTime($params);

        // 翌月のUnix Time
        $nextTime = mktime(0, 0, 0, $this->calendar->calendarMonth + 1, 1, $this->calendar->calendarYear);

		// 対象年月のスケジュールを読込む
		$key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->calendar->calendarTime);
		foreach ($this->articles as $article_id => $article) {
			$this->schedule[$article_id] = get_post_meta($article_id, $key_name, true);
		}

		// 対象年月の予約カウントデータを読込む
		$this->reserved = $this->get_reserved_count($this->calendar->calendarYear, $this->calendar->calendarMonth);

        // 先頭曜日と1日の曜日差を求める
        $startWeek = date_i18n('w', $this->calendar->calendarTime);
        $offsetDay = ($startWeek - $this->calendar->startOfWeek + 7) % 7;

        // カレンダー先頭Unix Time
        $startTime = $this->calendar->calendarTime - $offsetDay * 86400;

        // アンカー指定
        $anchor = '';
        if ($params['anchor'] && !$params['widget'] && $term == $params['term']) {
            $anchor = sprintf(' id="%s"', $params['anchor']);
        }

?>
    <div<?php echo sprintf('%s class="%s"', $anchor, $params['class']) ?>>
        <?php // タイトル表示
            echo $this->view->calendarTitle($params); ?>
	<table>
<?php
        // キャプション・月リンク表示
        echo $this->view->captionPagination($params, $this->calendar);

        // 曜日ヘッダー表示
        echo $this->view->weekHeader($startTime, $params['weeks']);

        // カレンダー表示
        for ($i = 0, $dayTime = $startTime; $i <= 42; $i++, $dayTime += 86400) {
            if ($i % 7 == 0) {
                // 行末表示
                echo 0 < $i ? "</tr>\n" : '';
                // 月末チェック
                if ($nextTime <= $dayTime) {
                    break;
                }
                echo '<tr class="week-row">' . "\n";
            }

            if ($this->calendar->calendarTime <= $dayTime && $dayTime < $nextTime) {
                $this->_multiple_calendar_of_theday($dayTime, $params);
            } else {
                echo '<td class="day-box no-day"></td>' . "\n";
            }
        }
?>

	</table>
	<?php if ($params['pagination'] == 1 || $params['pagination'] == 3) {
        echo $this->view->pagination($params, $this->calendar);
	} ?>

    </div>

<?php
        $params['year'] = date_i18n('Y', $this->calendar->nextTime);
        $params['month'] = date_i18n('n', $this->calendar->nextTime);
        $term--;
        endwhile;

        echo apply_filters('mtssb_calendar_after', '', $params['calendar_id']);

        return ob_get_clean();
	}

	/**
	 * 指定日の予約情報を出力
	 *
	 * @thetime		ymd unixtime
	 */
	private function _multiple_calendar_of_theday($daytime, $params)
    {
		$link = $params['link'];
		$skip = $params['skiptime'];
		$cid = $params['calendar_id'];
		$suppression = $params['suppression'];
		$anchor = $params['anchor'];
		$low = $params['low'];

		$idxday = date_i18n('d', $daytime);
		$week = strtolower(date('D', $daytime));

		// 指定された予約品目の先頭品目の予約スケジュールからclass指定を取得する
		$article = reset($this->articles);
		$article_id = $article['article_id'];
		$class = empty($this->schedule[$article_id][$idxday]['class']) ? '' : $this->schedule[$article_id][$idxday]['class'];
        $note = empty($this->schedule[$article_id][$idxday]['note']) ? '' : $this->schedule[$article_id][$idxday]['note'];

		// TD Box
		echo "<td class=\"day-box $week"
		 . ($daytime == $this->calendar->todayTime ? ' today' : '')
		 . (empty($class) ? '' : " $class") . '">';
		// 日付
		echo "<div class=\"day-number\">" . esc_html(apply_filters('mtssb_day', (int) $idxday, array('day' => $daytime, 'cid' => $cid))) . '</div>';

		// 複数指定予約を出力する
		foreach ($this->articles as &$article) {
			extract($article);

			$sum = $rsvdnum = $remain = $rate = 0;

			// スケジュール
			$schedule = empty($this->schedule[$article_id]) ? null : $this->schedule[$article_id][$idxday];

			// 予約可能総数
			if (!empty($schedule['open'])) {
				$sum = $restriction == 'capacity' ? $capacity : $quantity;
				$sum += intval($schedule['delta']);
				$sum *= count($timetable);

				// low判定数
				$lows = $low * count($timetable);
			}

			// 予約数
			if (isset($this->reserved[$daytime][$article_id])) {
				$reserved = $this->reserved[$daytime][$article_id];
				$rsvdnum = intval($restriction == 'capacity' ? $reserved['number'] : $reserved['count']);
			}

			// 予約残数・予約残率
			if (0 < $sum ) {
				$remain = $sum - $rsvdnum;
				$rate = $remain * 100 / $sum;
			}

            // 表示マーク
            if ($this->controls['vacant_rate'] < $rate && $lows < $remain) {
                $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
            } else if ($rate <= 0) {
                $mark = 'full';
            } else {
                $mark = 'low';
            }

            // 表示不可マーク
            $timetableDay = $this->calendar->isTimetableDay($daytime);
            if (empty($schedule['open'])) {
                $mark = 'disable';
            } else if (!$timetableDay) {
                if (!$this->controls['output_margin'] || $daytime < $this->calendar->lastDayTime) {
                    $mark = 'disable';
                }
            }

			// 予約カレンダーから予約処理をリンクする指定がある場合
			$linkurl = '';
			if ($link && $timetableDay) {
				// 予約カレンダーから予約フォームへのリンク
				if ($skip) {
					$linkurl = htmlspecialchars(add_query_arg(array('aid' => $article_id, 'utm' => $daytime + $timetable[0]), $this->form_link));
				}
				// 予約カレンダーから時間割へのリンク
				else {
					$arg = array('aid' => $article_id, 'ymd' => $daytime) + (empty($cid) ? array() : array('cid' => $cid));
					$linkurl = htmlspecialchars(add_query_arg($arg, $this->this_page)) . (empty($anchor) ? '' : "#{$anchor}");
				}
			}

			// マーク・リンク表示(記号または残数)
            $marking = $this->calendar->getMarking($mark, $remain);
    		$linkname = '';
			if ($mark != 'disable') {
				$linkname = apply_filters('mtssb_multiple_calendar_name', $name, $cid, $article_id);
			}
			$marking = '<span class="calendar-marking">' . apply_filters('mtssb_multiple_calendar_marking', $marking, $cid, $article_id, $mark, $remain) . '</span>';
			$linkname = '<span class="article-name">' . $linkname . '</span>';

			$linktext = apply_filters('mtssb_multiple_calendar_link_text', ($linkname . $marking), $cid, $article_id, $mark, $remain, $name);

			// disableの(非)表示
			if ($mark == 'disable') {
				if ($suppression != 1) {
					echo '<div class="calendar-mark">' . $linktext . "</div>\n";
				}
			}
			else if ($mark == 'full' || empty($linkurl)) {
				echo '<div class="calendar-mark">' . $linktext . "</div>\n";
			}
			// low,booking,vacantの表示
			else {
				echo '<div class="calendar-mark">';
				echo '<a class="calendar-daylink" href="' . $linkurl . '">' . $linktext . '</a>';
				echo "</div>\n";
			}
		}

		// スケジュール注記の表示
		if (!empty($note)) {
			echo apply_filters('mtssb_monthly_schedule_note', "<div class=\"schedule-note\">$note</div>", $cid);
		}

		echo "</td>\n";

	}

	/**
	 * 月間時間割カレンダー出力
	 *
	 */
	public function timetable_calendar($atts)
    {
		// 予約受付終了状態
		if (empty($this->controls['available'])) {
			return $this->controls['closed_page'];
		}

		// ショートコードパラメータの初期化
        $params = $this->params = shortcode_atts(array_merge($this->calendar->commonParams(), array(
			'class' => 'timetable-calendar',
        )), $atts);

        // カレンダーID指定の確認
        if (isset($_GET['cid']) && $_GET['cid'] != $params['calendar_id']) {
            return '';
        }

		// 予約品目を取得する
		$this->articles = MTSSB_Article::get_all_articles($params['id']);
		if (empty($this->articles)) {
			return __('Not found any articles of reservation.', $this->domain);
		}

        // カレンダー表示月を決定する(yyyy年mm月1日)
        $this->calendar->defCalendarTime($params);

        // 翌月のUnix Time
        $nextTime = mktime(0, 0, 0, $this->calendar->calendarMonth + 1, 1, $this->calendar->calendarYear);

		// 対象年月のスケジュールを読込む
		$this->article = array_shift($this->articles);
		$key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->calendar->calendarTime);
		$this->schedule[$this->article['article_id']] = get_post_meta($this->article['article_id'], $key_name, true);

        // 先頭曜日と1日の曜日差を求める
        $startWeek = date_i18n('w', $this->calendar->calendarTime);
        $offsetDay = ($startWeek - $this->calendar->startOfWeek + 7) % 7;

        // カレンダー先頭Unix Time
        $startTime = $this->calendar->calendarTime - $offsetDay * 86400;

        // カレンダータイトル
        if (!isset($this->params['title'])) {
            $this->params['title'] = $this->article['name'];
        }

        // アンカー指定
        $anchor = ($params['anchor'] && !$params['widget']) ? sprintf(' id="%s"', $params['anchor']) : '';

        ob_start();
?>
    <div<?php echo sprintf('%s class="%s"', $anchor, $this->params['class']) ?>>
        <?php echo apply_filters('mtssb_calendar_before', '', $this->params['calendar_id']);
        // タイトル表示
        echo $this->view->calendarTitle($this->params); ?>
	<table>
<?php
        // キャプション・月リンク表示
        echo $this->view->captionPagination($params, $this->calendar);

        // 曜日ヘッダー表示
        echo $this->view->weekHeader($startTime, $params['weeks']);

        // カレンダー表示
        for ($i = 0, $dayTime = $startTime; $i <= 42; $i++, $dayTime += 86400) {
            if ($i % 7 == 0) {
                // 行末表示
                echo 0 < $i ? "</tr>\n" : '';
                // 月末チェック
                if ($nextTime <= $dayTime) {
                    break;
                }
                echo '<tr class="week-row">' . "\n";
            }

            if ($this->calendar->calendarTime <= $dayTime && $dayTime < $nextTime) {
                $this->_timetable_calendar_of_theday($dayTime, $params);
            } else {
                echo '<td class="day-box no-day"></td>' . "\n";
            }
        }
?>

	</table>
	<?php if ($params['pagination'] == 1 || $params['pagination'] == 3) {
        echo $this->view->pagination($params, $this->calendar);
	}
    echo apply_filters('mtssb_calendar_after', '', $this->params['calendar_id']); ?>

	</div>

<?php
		return ob_get_clean();
	}

	/**
	 * 指定日の時間割予約情報を出力
	 *
	 * @thetime		ymd unixtime
	 */
	private function _timetable_calendar_of_theday($daytime, $params)
    {
		$link = $params['link'];
		$cid = $params['calendar_id'];
		$suppression = $params['suppression'];
		$low = $params['low'];

		$idxday = date_i18n('d', $daytime);
		$week = strtolower(date('D', $daytime));

		// 予約品目と予約スケジュールを取り出す
		$article = $this->article;
		extract($article);
		$schedule = empty($this->schedule[$article_id][$idxday]) ? array() : $this->schedule[$article_id][$idxday];
		$delta = 0;
		if (!empty($schedule['delta'])) {
			$delta = intval($schedule['delta']);
		}

		// 当該日予約データの取得
		$reserved = $this->get_reserved_day_count($daytime);

		// TD Box
		echo "<td class=\"day-box $week" . ($daytime == $this->calendar->todayTime ? ' today' : '')
		 . (empty($schedule['class']) ? '' : " {$schedule['class']}") . '">';
		// 日付
		echo "<div class=\"day-number\">" . esc_html(apply_filters('mtssb_day', (int) $idxday, array('day' => $daytime, 'cid' => $cid))) . '</div>';

        $timetableDay = $this->calendar->isTimetableDay($daytime);

        if (empty($schedule['open'])
         || (!$timetableDay && ($daytime < $this->calendar->lastDayTime || !$this->controls['output_margin']))) {
            if (empty($suppression)) {
				$markstr = apply_filters('mtssb_time_calendar_disable_marking', $this->controls['disable'], $cid);
				echo '<div class="calendar-time-disable">' . $markstr . "</div>\n";
			}
		} else {
			foreach ($timetable as $time) {
				$thetime = $daytime + $time;
				$sum = $rsvdnum = $remain = $rate = 0;

				// 予約可能総数
				$sum = ($restriction == 'capacity' ? $capacity : $quantity) + $delta;

				// 予約数
				if (isset($reserved[$thetime][$article_id])) {
					$rsvd = $reserved[$thetime][$article_id];
					$rsvdnum = intval($restriction == 'capacity' ? $rsvd['number'] : $rsvd['count']);
				}

				// 予約残数・予約残率
				if (0 < $sum ) {
					$remain = $sum - $rsvdnum;
					$rate = $remain * 100 / $sum;
				}

				// 表示マーク
                if ($this->controls['vacant_rate'] < $rate && $low < $remain) {
                    $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
                } else if ($rate <= 0) {
                    $mark = 'full';
                } else {
                    $mark = 'low';
                }

				// 予約カレンダーから予約処理をリンクする指定がある場合
				$linkurl = '';
				if ($link && $this->calendar->isBookingTime($thetime)) {
					// 予約カレンダーから予約フォームへのリンク
					$linkurl = htmlspecialchars(add_query_arg(array('aid' => $article_id, 'utm' => $thetime), $this->form_link));
				}

				// マーク・リンク表示(記号または残数)
                $marking = $this->calendar->getMarking($mark, $remain);
				$timestr = '<span class="time-string">' . date_i18n('H:i', $time) . '</span>';
				$timestr = apply_filters('mtssb_time_calendar_timestr', $timestr, $cid, $time);
				$markstr = apply_filters('mtssb_time_calendar_marking', $marking, $cid, $mark, $remain);

				echo '<div class="calendar-time-mark">';
				// disableの(非)表示
				if ($mark == 'disable') {
					if ($suppression != 1) {
						echo $timestr . $markstr;
					}
				}
				else if ($mark == 'full' || empty($linkurl)) {
					echo $timestr . $markstr;
				}
				// low,booking,vacantの表示
				else {
					echo $timestr . '<a class="calendar-timelink" href="' . $linkurl . '">' . $markstr . '</a>';
				}
				echo "</div>\n";
			}
		}

		// スケジュール注記の表示
		$note = empty($schedule['note']) ? '' : $schedule['note'];
		if (!empty($note)) {
			echo apply_filters('mtssb_monthly_schedule_note', "<div class=\"schedule-note\">$note</div>", $cid);
		}

		echo "</td>\n";
	}


    /**
     * 月リスト予約カレンダー出力
     *
     */
    public function listMonthlyCalendar($atts)
    {
        // 予約受付終了状態
        if (empty($this->controls['available'])) {
            return $this->controls['closed_page'];
        }

        // ショートコードパラメータの初期化
        $this->params = shortcode_atts(array_merge($this->calendar->commonParams(), array(
            'class' => 'list-monthly-calendar',
            'title' => '予約カレンダー',  // 月リストカレンダータイトル
            'xaxis' => '',              // 横軸は予約品目(d:日付)
        )), $atts);

        // カレンダーID指定の確認
        if (isset($_GET['cid']) && $_GET['cid'] != $this->params['calendar_id']) {
            return '';
        }

        // 予約日が指定された場合は時間割カレンダーを表示する
        $dayTime = $this->calendar->defDayTime($this->params);
        if ($dayTime) {
            return $this->_dayTimetable($dayTime);
        }

        // カレンダー表示月を決定する(yyyy年mm月1日)
        $this->calendar->defCalendarTime($this->params);

        // 予約品目の取得
        $this->articles = MTSSB_Article::get_all_articles($this->params['id']);
        if (empty($this->articles)) {
            return __('Not found any articles of reservation.', $this->domain);
        }

        // 対象年月のスケジュールを読込む
        foreach ($this->articles as $articleId => &$article) {
            $key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->calendar->calendarTime);
            $article['schedule'] = get_post_meta($articleId, $key_name, true);
        }

        // 対象年月の予約カウントデータを読込む
        $this->reserved = $this->get_reserved_count($this->calendar->calendarYear, $this->calendar->calendarMonth);

        // アンカー指定
        $anchor = ($this->params['anchor'] && !$this->params['widget']) ? sprintf(' id="%s"', $this->params['anchor']) : '';

        ob_start();
?>
        <div<?php echo sprintf('%s class="%s"', $anchor, $this->params['class']) ?>>
            <?php echo apply_filters('mtssb_calendar_before', '', $this->params['calendar_id']);
            // タイトル表示
            echo $this->view->calendarTitle($this->params); ?>
            <table>
                <?php           // キャプション・月リンク表示
                echo $this->view->captionPagination($this->params, $this->calendar);

                if (empty($this->params['xaxis'])) {
                    $this->_articleInColumns();
                }
                ?>

            </table>
            <?php
            if ($this->params['pagination'] == 1 || $this->params['pagination'] == 3) {
                echo $this->view->pagination($this->params, $this->calendar);
            }
            echo apply_filters('mtssb_calendar_after', '', $this->params['calendar_id']); ?>

        </div>

<?php
        return ob_get_clean();
    }

    /**
     * 予約品目並列リストカレンダー
     */
    private function _articleInColumns()
    {

        $days = (int) (mktime(0, 0, 0, $this->calendar->calendarMonth + 1, 1, $this->calendar->calendarYear) - $this->calendar->calendarTime) / 86400;
        $dayTime = $this->calendar->calendarTime;

        $this->_aicArticleName();
?>
        <tbody>
        <?php for ($i = 0; $i < $days; $i++, $dayTime += 86400) {
            $articleNum = 0;
            foreach ($this->articles as $articleId => $article) {
                echo $this->_aicDayLink($dayTime, $article, $articleNum++);
            }
            echo "</tr>\n";
        } ?>
        </tbody>

<?php
    }

    // articleInColumns 予約品目名
    private function _aicArticleName()
    {
?>
        <thead>
        <tr>
            <th class="list-header article-name"></th><?php foreach ($this->articles as $articleId => $article) {
                echo sprintf('<th class="a%d">%s</th>',
                    $articleId, apply_filters('mtssb_article_name', $article['name'], array(
                        'aid' => $articleId,
                        'cid' => $this->params['calendar_id']
                    )));
            } ?>
        </tr>
        </thead>
<?php
    }

    // articleInColumns 日付
    private function _aicDate($day, $dayTime, $class)
    {
        $class = empty($class) ? '' : " {$class}";
        $dayStr = apply_filters('mtssb_day', date_i18n('j (D)', $dayTime), array('day' => $dayTime, 'cid' => $this->params['calendar_id']));
        return sprintf('<tr class="row%d"><th class="list-header day-number %s%s">%s</th>',
            $day % 2, strtolower(date('D', $dayTime)), $class, $dayStr);
    }

    // 予約日時間割リンク
    private function _aicDayLink($dayTime, $article, $articleNum)
    {
        // スケジュールデータのセット
        $idxday = date_i18n('d', $dayTime);
        $oSchedule = $this->_daySchedule($idxday, $article['schedule']);

        // 日付表示
        $dayHeader = '';
        if ($articleNum == 0) {
            $dayHeader = $this->_aicDate((int) $idxday, $dayTime, $oSchedule->class);
        }

        // 予約情報の取得
        $bInfo = $this->_aicDailyMark($dayTime, $article, $oSchedule);

        // 時間割ページリンク
        $param = array_merge(array('aid' => $article['article_id'], 'ymd' => $dayTime),
            $this->params['calendar_id'] ? array('cid' => $this->params['calendar_id']) : array());
        $url = esc_url(add_query_arg($param, $this->this_page) . (empty($this->params['anchor']) ? '' : "#{$this->params['anchor']}"));

        // カレンダー表示出力文字
        $marking = $this->calendar->getMarking($bInfo->mark, $bInfo->remain);
        if ($bInfo->mark == 'disable' && $this->params['suppression']) {
            $marking = '';
        }
        $linkStr = apply_filters('mtssb_monthly_calendar_marking', $marking, $this->params['calendar_id'], $bInfo->mark, $bInfo->remain);

        // 時間割リンク
        if ($this->calendar->isTimetableDay($dayTime)  && $bInfo->mark != 'disable' && !($bInfo->mark == 'full' && $this->params['linkfull'] == 0)) {
            $linkStr = sprintf('<div class="calendar-mark"><a href="%s" title="timetable">%s</a></div>', $url, $linkStr);
        }

        // スケジュールノート
        if (!empty($bInfo->note)) {
            $linkStr .= sprintf('<div class="schedule-note">%s</div>', $bInfo->note);
        }

        // 時間割リンク表示
        return $dayHeader . sprintf('<td class="list-box %s %s%s">%s</td>',
            strtolower(date('D', $dayTime)), $bInfo->mark, ($oSchedule->class ? " {$oSchedule->class}" : ''), $linkStr);
    }

    /**
     * 指定された予約品目の当該日の予約状態を求める
     *
     * @dayTime     指定日
     * @article     予約品目データ
     *
     * @return      array(mark, remain);
     */
    private function _aicDailyMark($dayTime, $article, $oSchedule)
    {
        // 予約率を求めるパラメータの初期値設定
        $rsvdnum = $remain = $rate = 0;

        // 予約制約タイプ
        $restriction = $article['restriction'];

        // 時間割コマ数
        $zone = count($article['timetable']);

        // 予約可能総数
        $sum = $article["total_{$restriction}"] +  $oSchedule->delta * $zone;

        // 予約残数少設定
        $low = $this->params['low'] * $zone;

        // 予約数を求める
        if (isset($this->reserved[$dayTime][$article['article_id']])) {
            if ($restriction == 'capacity') {
                $rsvdnum += $this->reserved[$dayTime][$article['article_id']]['number'];
            } else {
                $rsvdnum += $this->reserved[$dayTime][$article['article_id']]['count'];
            }
        }

        // 予約残数・予約残率
        if (0 < $sum ) {
            $remain = $sum - $rsvdnum;
            $rate = (int) ($remain * 100 / $sum);
        }

        // 表示不可マーク
        $timetableDay = $this->calendar->isTimetableDay($dayTime);
        if ($oSchedule->open <= 0
            || (!$timetableDay && ($dayTime < $this->calendar->lastDayTime || $this->controls['output_margin'] <= 0))) {
            $mark = 'disable';
        // 表示マーク
        } else {
            if ($remain <= 0) {
                $mark = 'full';
            } elseif ($rate <= $this->controls['vacant_rate'] || $remain <= $low) {
                $mark = 'low';
            } else {
                $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
            }
        }

        // 予約状態を戻す
        return (object) array (
            'mark' => $mark,
            'remain' => $remain,
            'note' => isset($oSchedule->note) ? $oSchedule->note : '',
        );
    }

}