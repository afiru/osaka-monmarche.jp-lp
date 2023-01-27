<?php
/**
 * MTS Simple Booking フロント処理各種予約カレンダーモジュール
 *
 * @Filename	mtssb-front-freak.php
 * @Date		2013-09-07
 * @Author		S.Hayashi
 *
 * Updated to 1.32.2 on 2020-01-07
 * Updated to 1.32.0 on 2019-11-26
 * Updated to 1.31.1 on 2019-10-01
 * Updated to 1.28.2 on 2018-02-27
 * Updated to 1.28.0 on 2017-11-01
 * Updated to 1.21.0 on 2015-04-03
 * Updated to 1.19.0 on 2014-10-31
 * Updated to 1.17.0 on 2014-07-08
 * Updated to 1.15.0 on 2014-02-20
 * Updated to 1.12.0 on 2013-11-19
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

class MTSSB_Front_Freak extends MTSSB_Booking
{
	// 予約条件設定
	private $controls = array();

    // カレンダー処理オブジェクト
    private $calendar = NULL;

	// 予約カレンダー表示　日付データ
    private $day_time;              // カレンダー表示対象日 unix time

	// 予約カレンダー表示　データベース
	private $articles = array();
    private $first_article_id = 0;
	private $schedules = array();
	private $reserved = array();

	// ショートコードパラメータ
	private $params = array();

    // カレンダー表示ビュー
    private $view = null;

    // 表示ページ
    private $this_page = '';

    // ミックスデイリー時間割カレンダー表示
    private $partition = 0;         // 時間割最大コマ数

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
	 * スケジュール確認
	 *
	 * @daytime		スケジュール確認日のunix time
	 */
	private function _is_open($daytime)
	{
		$idxday = date_i18n('d', $daytime);
		$open_flg = false;

		foreach ($this->articles as $article_id => $article) {
			// 対象年月のスケジュール確認と読み込み
			if ($idxday == 1 || !isset($this->schedules[$article_id])) {
				$key_name = MTS_Simple_booking::SCHEDULE_NAME . date_i18n('Ym', $daytime);
				$this->schedules[$article_id] = get_post_meta($article_id, $key_name, true);
			}
			// スケジュール予約受付の確認
			if (isset($this->schedules[$article_id][$idxday]) && $this->schedules[$article_id][$idxday]['open']) {
				$open_flg =true;
			}
		}

		return $open_flg;
	}

	/**
	 * リスト予約カレンダー出力
	 *
	 */
	public function list_calendar($atts)
	{
		// 予約受付終了状態
		if (empty($this->controls['available'])) {
			return $this->controls['closed_page'];
		}

		// ショートコードパラメータの初期化
        $this->params = shortcode_atts(array_merge($this->calendar->commonParams(), array(
            'class' => 'list-calendar',
            'title' => '予約カレンダー',
            'period' => '180',		// リスト表示期間(日数) 0:設定値
            'pagedays' => '10',		// 表示する受付日数
            'header' => '1',		// ヘッダー表示
            'alldays' => '0'		// スケジュールがopen以外も全て表示
        )), $atts);

		// 予約品目の取得
		$this->articles = MTSSB_Article::get_all_articles($this->params['id']);
		if (empty($this->articles)) {
			return __('Not found any articles of reservation.', $this->domain);
		}

        // アンカー指定
        $anchor = ($this->params['anchor'] && !$this->params['widget']) ? sprintf(' id="%s"', $this->params['anchor']) : '';

        ob_start();
?>
    <div<?php echo sprintf('%s class="%s"', $anchor, $this->params['class']) ?>>
        <?php echo apply_filters('mtssb_calendar_before', '', $this->params['calendar_id']);
        // タイトル表示
        echo $this->view->calendarTitle($this->params); ?>

	<table>
		<?php $this->_listout_caption() ?>
		<?php $this->_listout_header() ?>

<?php /* 日付繰り返し処理 */

		$days = 0;			// 表示対象期間(日)
		$open_days = 0;		// 予約受付表示日数

		// カレンダー表示開始日
		$daytime = $this->calendar->lastDayTime;

		// カレンダーの表示は予約受付期間内、かつ、予約受付表示日数内、かつ、表示対象期間内
		while ($daytime < $this->calendar->openTime && $open_days < $this->params['pagedays'] && $days <= $this->params['period']) {
			// スケジュールが予約受付状態のとき表示
			if ($this->_is_open($daytime) || $this->params['alldays']) {
				echo $this->_listout_dayrow($daytime);
				$open_days++;
			}

			$days++;
			$daytime += 86400;
		}
?>
	</table>
	<?php echo apply_filters('list_message_after', '', $this->params['calendar_id']) ?>

	</div>

<?php
		return ob_get_clean();
	}

	/**
	 * リスト予約カレンダーのキャプション表示
	 *
	 */
	private function _listout_caption()
	{
		// caption指定がなければ未表示
		if ($this->params['caption'] == 0) {
			return;
		}

		$capstr = apply_filters('list_caption', '予約リスト', array(
			'calendar_id' => $this->params['calendar_id'])
		);

		echo sprintf("<caption class=\"list-calendar%s\">%s</caption>\n",
			(empty($this->params['calendar_id']) ? '' : " {$this->params['calendar_id']}"), $capstr
		);
	}

	/**
	 * リストカレンダーのヘッダー出力
	 *
	 */
	private function _listout_header()
	{
		// ヘッダー出力指定がなければ未表示
		if ($this->params['header'] != 1) {
			return;
		}

		ob_start();
?>
		<tr>
			<th class="list-header" rowspan="2"><?php echo apply_filters('list_header_top_left', ' ', array(
				'calendar_id' => $this->params['calendar_id'])); ?></th>
			<?php echo $this->_listout_header_article_name() ?>
		</tr>
		<tr>
			<?php echo $this->_listout_header_timetable() ?>
		</tr>

<?php
		echo ob_get_clean();
	}

	/**
	 * リストカレンダーのヘッダー内予約品目名出力
	 *
	 */
	private function _listout_header_article_name()
	{
		ob_start();

		foreach ($this->articles as $article_id => $article) :
			$cols = count($article['timetable']) + 1;
?>
			<th class="list-header article-name" colspan="<?php echo $cols ?>"><?php
				echo apply_filters('list_article_name', $article['name'], array(
					'artcle_id' => $article_id,
					'calendar_id' => $this->params['calendar_id']));
			?></th>

<?php
		endforeach;

		return ob_get_clean();
	}

	/**
	 * リストカレンダーのヘッダー内予約品目別時間割出力
	 *
	 */
	private function _listout_header_timetable()
	{
		ob_start();

		foreach ($this->articles as $article_id => $article) :
			foreach ($article['timetable'] as $booking_time) :
?>
			<th class="list-header timetable-time"><?php
				echo apply_filters('list_timetable_time', date('H:i', $booking_time), array(
					'booking_time' => $booking_time,
					'article_id' => $article_id,
					'calendar_id' => $this->params['calendar_id']));
			?></th>
			<?php endforeach; ?>
			<th class="list-header note"><?php echo apply_filters('list_note', '注記', array(
				'article_id' => $article_id,
				'calendar_id' => $this->params['calendar_id']));
			?></th>

<?php
		endforeach;

		return ob_get_clean();
	}

	/**
	 * 指定日の予約情報を出力
	 *
	 */
	private function _listout_dayrow($thetime)
	{
		// 予約データ取得
		$this->reserved = $this->get_reserved_day_count($thetime);

		$idxday = date_i18n('d', $thetime);

		foreach ($this->articles as $article_id => $article) {
			// スケジュールデータセット
			$schedule = $this->_get_schedule_data($article_id, $idxday);
			$cols[$article_id]['schedule'] = $schedule;

			// 予約可能総数を求める
			$sum = $article[$article['restriction']];
			$sum += intval($schedule['delta']);

			// 時間割別予約状況
			foreach ($article['timetable'] as $tabletime) {
				$booking_time = $thetime + $tabletime;

				if ($schedule['open']) {
					// 予約率を求めるパラメータの初期値設定
					$rsvdnum = $remain = $rate = 0;
					$linkurl = '';

					// 予約数を求める
					if (isset($this->reserved[$booking_time][$article_id])) {
						$reserved = $this->reserved[$booking_time][$article_id];
						$rsvdnum = intval($article['restriction'] == 'capacity' ? $reserved['number'] : $reserved['count']);
					}

					// 予約残数・予約残率
					if (0 < $sum ) {
						$remain = $sum - $rsvdnum;
						$rate = $remain * 100 / $sum;
					}

					// 表示マーク 受付開始時刻以前の予約はdisable
					//if ($this->start_time < $booking_time) {
                    if ($this->calendar->isBookingTime($booking_time)) {

						if ($this->controls['vacant_rate'] < $rate) {
							// 予約がなければ'vacant'、予約があれば'booked'
							$mark = 0 < $rsvdnum ? 'booked' : 'vacant';
							// 残数がパラメータ指定されたlow以下なら'low'をセットする
							if ($remain <= $this->params['low']) {
								$mark = 'low';
							}
						} else if ($rate <= 0) {
							$mark = 'full';
						} else {
							$mark = 'low';
						}

						// 予約カレンダーから予約フォームへのリンク
						$linkurl = htmlspecialchars(add_query_arg(array('aid' => $article_id, 'utm' => $booking_time), $this->form_link));
					} else {
						$mark = 'disable';
					}

					$cols[$article_id]['timetable'][$tabletime] = array(
						'mark' => $mark,
						'remain' => $remain,
						'rate' => $rate,
						'linkurl' => $linkurl);
				} else {
					$cols[$article_id]['timetable'][$tabletime] = array(
						'mark' => 'disable');
				}
			}
		}

		$week = strtolower(date('D', $thetime));
?>
		<tr>
			<?php $this->_listout_dayrow_date($thetime) ?>
			<?php $this->_listout_dayrow_calendar($cols, $week) ?>
		</tr>
<?php
	}

	/**
	 * リスト日付カラム表示
	 *
	 * @thetime		unix time
	 */
	private function _listout_dayrow_date($thetime)
	{
		$date_clm = date_i18n('Y/m/d (D)', $thetime);
		$date_str = apply_filters('list_calendar_date', $date_clm, array(
					'date_time' => $thetime,
					'calendar_id' => $this->params['calendar_id']));

		// スケジュールに登録されたクラス名を取得する
		$class = $this->_get_schedule_class(date_i18n('d', $thetime));

		echo sprintf("<th class=\"list-header %s%s\">%s</th>\n", strtolower(date('D', $thetime)), $class, $date_str);
	}

	/** 
	 * スケジュールに登録されたクラス名を戻す
	 *
	 * @didx	日付2桁
	 */
	private function _get_schedule_class($didx)
	{
		foreach ($this->schedules as $article_id => $schedule) {
			if (!empty($schedule[$didx]['class'])) {
				return ' ' . $schedule[$didx]['class']; 
			}
		}
		return '';
	}

	/**
	 * スケジュールデータを戻す
	 *
	 * @article_id	article_id
	 * @didx		日付2桁
	 */
	private function _get_schedule_data($article_id, $didx)
	{
		if (isset ($this->schedules[$article_id][$didx])) {
			return $this->schedules[$article_id][$didx];
		}

		return array(
			'open' => 0,
			'delta' => 0,
			'class' => '',
			'note' => '');
	}

	/**
	 * リスト予約カレンダー表示
	 *
	 * @cols[$artcile_id][$tabletime] = array(
	 *		'mark' => $mark,
	 *		'remain' => $remain,
	 *		'rate' => $rate,
	 *		'linkurl' => $linkurl);
	 * @week	week string(lower case)
	 */
	private function _listout_dayrow_calendar($cols, $week)
	{
		$note_class = '';

		foreach ($this->articles as $article_id => $article) {
			$schedule = $cols[$article_id]['schedule'];

			foreach ($article['timetable'] as $tabletime) {
				$col = $cols[$article_id]['timetable'][$tabletime];

                $marking = $this->calendar->getMarking($col['mark']);

				$linktext = apply_filters('list_calendar_marking', $marking, array(
					'article_id' => $article_id,
					'mark' => $col['mark'],
					'remain' => isset($col['remain']) ? $col['remain'] : 0,
					'calendar_id' => $this->params['calendar_id']));

				// TDタグ出力
				$class = sprintf('class="list-box %s %s%s"', $week, $col['mark'], (empty($schedule['class']) ? '' : " {$schedule['class']}"));
				echo "<td $class><div class=\"calendar-mark\">";

				// disable 表示
				if ($col['mark'] == 'disable') {
					echo $this->params['suppression'] == 1 ? ' ' : $linktext;
				} else {
					if (empty($linktext)) {
						$linktext = $col['remain'];
					}
				// full 表示
					if ($col['mark'] == 'full') {
						echo "$linktext";
				// vacant,booked,low 表示
					} else {
						echo $this->params['link'] ? sprintf('<a href="%s">%s</a>', $col['linkurl'], $linktext) : $linktext;
					}
				}
				
				// /TDタグ出力
				echo "</div></td>\n";
			}

			echo sprintf("<td class=\"note%s\">%s</td>\n",
				($schedule['class'] ? " {$schedule['class']}" : ''),
				($schedule['note'] ? $schedule['note'] : ' '));
		}
	}

    /**
     * ミックス予約カレンダー出力
     *
     */
    public function mix_calendar($atts=array())
    {
        // 予約受付終了状態
        if (empty($this->controls['available'])) {
            return $this->controls['closed_page'];
        }

        // ショートコードパラメータの初期化
        $this->params = shortcode_atts(array_merge($this->calendar->commonParams(), array(
            'class' => 'mix-calendar',
            'title' => '予約カレンダー',
            'anchor' => 'mix-anchor',   // anchor出力指定
            'time' => 'row',        // 時間割を行にするかカラムにするか
            'drop_off' => '0',      // スケジュールで予約不可の対象品目を時間割カレンダーから外す
            'space_line' => '1',    // 時間割横軸スペース行表示
            'time_cell' => '1',     // 時間割時間表示
            'linkurl' => $this->this_page,  // 予約カレンダー内の時間割リンク先
        )), $atts);

        // 予約品目の取得
        $this->articles = MTSSB_Article::get_all_articles($this->params['id']);
        if (empty($this->articles)) {
            return __('Not found any articles of reservation.', $this->domain);
        }

        // 予約品目先頭のID
        $current_article = current($this->articles);
        $this->first_article_id = $current_article['article_id'];

        // パラメータ、または、カレンダーから日付が指定された場合のunixtimeを取得する
        $link_daytime = $this->calendar->defDayTime($this->params);

        // 日付が指定されたらWidgetを除き、時間割カレンダーを表示する
        if ($link_daytime) {
            if ($this->params['widget'] != 1) {
                $calendar = $this->_day_mix_calendar($link_daytime);
                if ($calendar) {
                    return $calendar;
                }
            }

            // 日付が指定されたら表示年月を合わせるために同年月をセットする
            $this->params['year'] = date_i18n('Y', $link_daytime);
            $this->params['month'] = date_i18n('m', $link_daytime);
        }

        // カレンダー表示月を決める
        $this->calendar->defCalendarTime($this->params);

        // アンカー指定
        $anchor = ($this->params['anchor'] && !$this->params['widget']) ? sprintf(' id="%s"', $this->params['anchor']) : '';

        ob_start();
?>
        <div<?php echo sprintf('%s class="%s"', $anchor, $this->params['class']) ?>>
            <?php echo apply_filters('mtssb_calendar_before', '', array('cid' => $this->params['calendar_id']));
            // タイトル表示
            echo $this->view->calendarTitle($this->params); ?>
            <table>
<?php
                // キャプション・月リンク表示
                echo $this->view->captionPagination($this->params, $this->calendar);

                // 曜日ヘッダー表示
                echo $this->view->weekHeader($this->calendar->topCalendarTime, $this->params['weeks']);

                // カレンダー表示
                for ($i = 0, $caltime = $this->calendar->topCalendarTime; $caltime < $this->calendar->nextTime || 0 < $i; $i = ($i + 1) % 7, $caltime += 86400) {
                    if ($i == 0) {
                        echo "<tr>\n";
                    }

                    if ($caltime < $this->calendar->calendarTime || $this->calendar->nextTime <= $caltime) {
                        $this->_mixout_noday();
                    }

                    //
                    else {
                        $this->_mixout_daybox($caltime);
                    }

                    if ($i == 6) {
                        echo "</tr>\n";
                    }
                }
?>

            </table>
            <?php if ($this->params['pagination'] == 1 || $this->params['pagination'] == 3) {
                echo $this->view->pagination($this->params, $this->calendar);
            }
            echo apply_filters('mtssb_calendar_after', '', array('cid' => $this->params['calendar_id'])); ?>

        </div>

<?php
        return ob_get_clean();
    }

    /**
     * 月間カレンダー内当月外の表示
     *
     */
    private function _mixout_noday()
    {
        echo '<td class="day-box no-day">&nbsp;</td>' . "\n";
    }

    /**
     * 月間カレンダー各日付状況の表示
     *
     * @daytime     当該日のUnixtime
     */
    private function _mixout_daybox($daytime)
    {
        // 当該日の予約状況のマーク(disable,full,kow,vacant,booked)と表示マークを取得する
        $status = $this->_mix_booking_mark($daytime);
        $marking = $this->calendar->getMarking($status['mark'], $status['remain']);
        $link_marking = $marking;

        // 予約受付期間無いの予約可能な状態か確認する
        if ($this->_is_linkmark($status['mark']) && $this->calendar->todayTime <= $daytime && $daytime < $this->calendar->openNextMonth) {
            // 予約受付期限内か、期限外のマージン出力設定なら日付リンクを用意する
            if ($this->calendar->isTimetableDay($daytime) || $this->controls['output_margin']) {
                $href = htmlspecialchars(add_query_arg(array('ymd' => $daytime), $this->params['linkurl']));
                if ($this->params['anchor']) {
                    $href .= "#{$this->params['anchor']}";
                }
                $link_marking = sprintf('<a href="%s" title="%s">%s</a>', $href, date_i18n('Y年n月j日', $daytime), $marking);
            }
        }

        echo sprintf('<td class="day-box %s %s %s">', strtolower(date('D', $daytime)), $status['mark'], $status['class']);
        echo sprintf('<div class="day-number">%s</div>',
            apply_filters('mtssb_day', date_i18n('j', $daytime), array('day' => $daytime, 'cid' => $this->params['calendar_id'])));
        echo sprintf('<div class="calendar-mark">%s</div>', $link_marking);
        echo sprintf('<div class="schedule-note">%s</div>', $status['note']);
        echo '</td>' . "\n";
    }

    /**
     * リンク表示マークか確認する
     *
     * @param $mark
     * @return bool
     */
    private function _is_linkmark($mark)
    {
        if (in_array($mark, array('vacant', 'booked', 'low'))) {
            return true;
        }

        return false;
    }

    /**
     * ミックス予約品目の指定日の優先順位の高いマークを取得する
     *
     * @param   $daytime
     * @return  array('remain', 'mark', 'note')
     */
    private function _mix_booking_mark($daytime)
    {
        // 複数予約品目での表示優先順位の低い順番
        $significant = apply_filters('mtssb_mix_mark_priority',
            array('disable' => 0, 'full' => 1, 'low' => 2, 'vacant' => 3, 'booked' => 4),
            array('cid' => $this->params['calendar_id'])
        );

        // 全予約品目のマーク計算
        $data = array('remain' => 0, 'mark' => 'disable', 'class' => '', 'note' => '');

        // 指定された全予約品目のスケジュールの受付確認
        if (!$this->_is_open($daytime)) {
            return $data;
        }

        // スケジュールデータの日付と先頭予約品目のクラス指定と注記の設定
        $idxday = date_i18n('d', $daytime);
        $schedule = $this->_get_schedule_data($this->first_article_id, $idxday);
        $data['class'] = $schedule['class'];
        $data['note'] = $schedule['note'];

        // 予約受付期間外でマージン出力なしの場合は受付不可
        if (!$this->calendar->isTimetableDay($daytime)) {
            if ($daytime < $this->calendar->todayTime || $this->calendar->openNextMonth <= $daytime) {
                return $data;
            } elseif ($this->controls['output_margin'] <= 0) {
                return $data;
            }
        }

        // 予約データ取得
        $reserved = $this->get_reserved_day_count($daytime);

        // 当該日の予約品目の予約状況
        foreach ($this->articles as $article_id => $article) {
            // スケジュールデータを取得し受付不可なら処理をスキップする
            $schedule = $this->_get_schedule_data($article_id, $idxday);
            if (!$schedule['open']) {
                continue;
            }

            // 予約可能総数を求める( (予約上限+スケジュール調整)*時間割コマ数 )
            $restriction = $article['restriction'];
            $empty = ($article[$restriction] + intval($schedule['delta'])) * count($article['timetable']);

            // 予約数を求める
            $total = 0;
            foreach ($article['timetable'] as $bookingtime) {
                // 当該時間割の年月日時Unixtime
                $bookingtime += $daytime;
                // 予約があれば合計する
                if (isset($reserved[$bookingtime][$article_id])) {
                    $idx = $restriction == 'capacity' ? 'number' : 'count';
                    $total += $reserved[$bookingtime][$article_id][$idx];
                }
            }

            // 予約残数をセットする
            $remain = $empty - $total;
            $data['remain'] += $remain;

            // 予約数から予約率を求めマークをセットする
            if (0 < $this->controls['vacant_rate']) {
                if (!$schedule['open']) {
                    $mark = 'disable';
                } elseif ($empty <= 0 || $remain <= 0) {
                    $mark = 'full';
                } elseif ($remain == $empty) {
                    $mark = 'vacant';
                } else {
                    $mark = 'booked';
                    $rate = $remain * 100 / $empty;
                    if ($rate <= $this->controls['vacant_rate']) {
                        $mark = 'low';
                    }
                }
                // 優先順位の高いマークをセットする
                if ($significant[$data['mark']] < $significant[$mark]) {
                    $data['mark'] = $mark;
                }
            }
        }

        return $data;
    }

    /**
     * 対象予約品目の最大時間割コマ数を取得する
     *
     * @return int
     */
    private function _get_max_timetable_partition()
    {
        $partition = 0;

        foreach ($this->articles as $article) {
            $num = count($article['timetable']);
            if ($partition < $num) {
                $partition = $num;
            }
        }

        return $partition;
    }

    /**
     * 指定日の時間割予約カレンダーを表示する
     *
     * @param $daytime
     * @return string
     */
    private function _day_mix_calendar($daytime)
    {
        // 指定日のスケジュール、予約受付期間を確認する
        if (!$this->_is_open($daytime) || $daytime < $this->calendar->todayTime || $this->calendar->openNextMonth <= $daytime) {
            return '';
        }

        // 予約受付期限内か、期限外のマージン出力設定を確認する
        if (!$this->calendar->isTimetableDay($daytime) && !$this->controls['output_margin']) {
            return '';
        }

        // スケジュールで受付しない設定の予約品目を予約対象品目から外す
        if ($this->params['drop_off'] && !$this->_check_drop_article_off($daytime)) {
            return '';
        }

        // 指定日のUnix time保存
        $this->day_time = $daytime;

        // 対象予約品目の時間割の最大コマ割数を取得する
        $this->partition = $this->_get_max_timetable_partition();

        // 予約データ取得
        $this->reserved = $this->get_reserved_day_count($daytime);

        // 表示アンカー
        $anchor = $this->params['anchor'] ? " id=\"{$this->params['anchor']}\"" : '';

        ob_start();
?>
        <div<?php echo sprintf('%s class="day-%s"', $anchor, $this->params['class']) ?>>
            <?php echo apply_filters('mtssb_mixday_message_before', '', array('day' => $daytime, 'cid' => $this->params['calendar_id'])) ?>
            <table>
            <?php
                // Caption出力
                $capstr = apply_filters('mtssb_mixday_caption',
                    date_i18n('Y年n月j日(D)', $daytime), array('day' => $daytime, 'cid' => $this->params['calendar_id']));
                if (!empty($capstr) && $this->params['caption']) {
                    echo sprintf('<caption class="%s%s">%s</caption>',
                        strtolower(date('D', $daytime)), $this->_get_schedule_class(date_i18n('d', $daytime)), $capstr) . "\n";
                }

                // 横軸時間割　表示
                if ($this->params['time'] == 'row') {
                    $this->_row_daily_calendar();
                }

                // 縦軸時間割　表示
                elseif ($this->params['time'] == 'col') {
                    $this->_col_daily_calendar();
                }
            ?></table>
            <?php echo apply_filters('mtssb_mixday_message_after', '', array('day' => $daytime, 'cid' => $this->params['calendar_id'])) ?>
        </div>
<?php

        return ob_get_clean();
    }

    /**
     * スケジュールで予約受付しない予約品目を対象から外す
     *
     * @daytime     スケジュール確認日(unit time)
     */
    private function _check_drop_article_off($daytime)
    {
        $idxday = date_i18n('d', $daytime);

        foreach ($this->articles as $article_id => $article) {
            // 対象年月のスケジュール確認と読み込み
            if ($idxday == 1 || !isset($this->schedules[$article_id])) {
                $key_name = MTS_Simple_booking::SCHEDULE_NAME . date_i18n('Ym', $daytime);
                $this->schedules[$article_id] = get_post_meta($article_id, $key_name, true);
            }
            // スケジュール予約受付の確認と対象予約品目の削除
            if (!isset($this->schedules[$article_id][$idxday]) || !$this->schedules[$article_id][$idxday]['open']) {
                unset($this->articles[$article_id]);
            }
        }

        if (empty($this->articles)) {
            return false;
        }

        $first = current($this->articles);
        $this->first_article_id = $first['article_id'];
        return true;
    }

    /**
     * 横軸時間割の指定日予約カレンダーを表示する
     *
     */
    private function _row_daily_calendar()
    {
        // 時間割時間のカスタマイズ出力
        echo apply_filters('mtssb_mixday_row_timeheader', '', array('cid' => $this->params['calendar_id']));

        // 各予約品目の出力
		foreach ($this->articles as $article_id => $article) {
            // 空白行出力
            if ($article_id != $this->first_article_id && $this->params['space_line']) {
                echo "<tr>\n";
                $this->_mixout_dayrow_spaceline();
                echo "</tr>\n";
            }

            // 時間割ヘッダー行の表示
            if ($this->params['time_cell']) {
                echo "<tr>\n";
                $this->_mixout_dayrow_header($article);
                echo "</tr>\n";
            }

            // 予約リンクの行表示
            echo "<tr>\n";
            $this->_mixout_dayrow_calendar($article);
            echo "</tr>\n";
        }
    }

    /**
     * 予約品目毎の時間割予約カレンダー表示(行)
     *
     */
    private function _mixout_dayrow_calendar($article)
    {
        // 時間割ヘッダー行が出力されていない場合の予約品目名出力
        if (!$this->params['time_cell']) {
            $this->_mixout_day_articlename($article, 0);
        }

       // 時間割の出力
        for ($i = 0; $i < $this->partition; $i++) {
            // 時間割定義があれば指定日時の予約状況を表示する
            if ($i < count($article['timetable'])) {
                $this->_mixout_day_booking_time($article, $this->day_time + $article['timetable'][$i]);
            // 時間割定義がなければ空白セルを出力する
            } else {
                echo '<td class="mix-day no-data"></td>' . "\n";
            }
        }
    }

    /**
     * 指定予約品目の指定日時の予約状況を時間割カレンダーに表示する
     *
     * @param $article
     * @param $booking_time
     */
    private function _mixout_day_booking_time($article, $booking_time)
    {
        // スケジュール取得
        $idxday = date_i18n('d', $this->day_time);
        $schedule = $this->_get_schedule_data($article['article_id'], $idxday);

        // 予約状況を取得する(mark, remain, rate, linkurl)
        $status = $this->_booking_time_status($article, $schedule, $booking_time);

        // マーキングを取得する、空白なら残数をセットする
        $marking = $this->calendar->getMarking($status['mark'], $status['remain']);

        $marking = apply_filters('mtssb_mixday_marking', $marking,
            array('aid' => $article['article_id'], 'remain' => $status['remain'], 'cid' => $this->params['calendar_id']));

        // 表示マーキングリンクを求める
        if ($status['linkurl'] && $this->_is_linkmark($status['mark'])) {
            $a_title = apply_filters('mtssb_mixday_timetitle', date_i18n('Y年n月j日 H:i', $booking_time),
                array('time' => $booking_time, 'cid' => $this->params['calendar_id']));
            $marking_url = sprintf('<a href="%s" title="%s">%s</a>', $status['linkurl'], $a_title, $marking);
        } else {
            $marking_url = $marking;
        }

        // マーキングを表示する
        echo sprintf('<td class="mix-day %s"><div class="calendar-mark">%s</div></td>' . "\n", $status['mark'], $marking_url);
    }

    /**
     * 指定予約時間の予約状況を戻す
     *
     * @param $article
     * @param $schedule
     * @param $booking_time
     * @return array(mark, remain, rate, linkurl)
     */
    private function _booking_time_status($article, $schedule, $booking_time)
    {
        $mark = 'disable';
        $remain = $rate = 0;
        $linkurl = '';

        // 現在時刻以上の予約時間の予約状況を求める
        if (current_time('timestamp') < $booking_time && 0 < $schedule['open']) {

            // 予約可能総数を求める
            $sum = $article[$article['restriction']];
            $sum += intval($schedule['delta']);

            // 予約率を求めるパラメータの初期値設定
            $rsvdnum = $remain = $rate = 0;
            //$linkurl = '';
            $article_id = $article['article_id'];

            // 予約数を求める
            if (isset($this->reserved[$booking_time][$article_id])) {
                $reserved = $this->reserved[$booking_time][$article_id];
                $rsvdnum = intval($article['restriction'] == 'capacity' ? $reserved['number'] : $reserved['count']);
            }

            // 予約残数・予約残率
            if (0 < $sum) {
                $remain = $sum - $rsvdnum;
                $rate = $remain * 100 / $sum;
            }

            // 表示マーク
            if ($this->controls['vacant_rate'] < $rate) {
                // 予約がなければ'vacant'、予約があれば'booked'
                $mark = 0 < $rsvdnum ? 'booked' : 'vacant';
                // 残数がパラメータ指定されたlow以下なら'low'をセットする
                if ($remain <= $this->params['low']) {
                    $mark = 'low';
                }
            } else if ($rate <= 0) {
                $mark = 'full';
            } else {
                $mark = 'low';
            }

            // 予約受付期限内なら予約フォームへのリンクを設定する
            if ($this->calendar->isBookingTime($booking_time)) {
                $linkurl = htmlspecialchars(add_query_arg(array('aid' => $article_id, 'utm' => $booking_time), $this->form_link));
            }
        }

        return array(
            'mark' => $mark,
            'remain' => $remain,
            'rate' => $rate,
            'linkurl' => $linkurl
        );
    }

    /**
     * 時間割ヘッダー行出力
     *
     * @param $article
     */
    private function _mixout_dayrow_header($article)
    {
        // ヘッダー行予約品目名出力
        $this->_mixout_day_articlename($article);

        // ヘッダー行時間割出力
        for ($i = 0; $i < $this->partition; $i++) {
            // 時間割定義が有りの場合
            if ($i < count($article['timetable'])) {
                $time_str = apply_filters('mtssb_mixday_timetable_time', date('H:i', $article['timetable'][$i]), array(
                    'aid' => $article['article_id'], 'time' => $article['timetable'][$i], 'cid' => $this->params['calendar_id']));
                $time_cell = sprintf('<th class="mix-day header-time">%s</th>', $time_str);
            // 時間割定義がない場合
            } else {
                $time_cell = '<th class="mix-day header-time-space"></th>' . "\n";
            }

            // ヘッダー行の出力
            echo $time_cell;
        }
    }

    /**
     * 品目名出力
     *
     */
    private function _mixout_day_articlename($article, $span=2, $dir='row')
    {
        $calendar_id = $this->params['calendar_id'];
        $article_name = apply_filters('mtssb_mix_article_name', $article['name'],
            array('aid' => $article['article_id'], 'cid' => $calendar_id));
        $spanstr = 1 < $span ? " {$dir}span=\"{$span}\"" : '';

        echo sprintf("<th class=\"mix-day article-name\"%s>%s</th>\n", $spanstr, $article_name);
    }

    /**
     * 横軸時間割　予約品目行間出力
     *
     */
    private function _mixout_dayrow_spaceline()
    {
        echo sprintf('<th class="mix-dayrow space-line" colspan="%s"></th>', $this->partition + 1) . "\n";
    }

     /**
      * 縦軸時間割の指定日予約カレンダーを表示する
      *
      */
    private function _col_daily_calendar()
    {
        // 時間割時間のカスタマイズ出力
        $this->_mixout_daycol_header();


        // 各行を時間割コマ数だけ出力する
        for ($i = 0; $i < $this->partition; $i++) {
            echo "<tr>\n";

            // 時間割時間のカスタマイズ出力
            echo apply_filters('mtssb_mixday_col_timeheader', '', array('row' => $i, 'cid' => $this->params['calendar_id']));

            // 各予約品目の次の行を出力する
            foreach ($this->articles as $article_id => $article) {
                // 時間割予約の表示
                $this->_mixout_daycol_calendar($i, $article);
            }

            echo "</tr>\n";
        }

    }

    /**
     * 縦軸時間割の先頭予約品目名表示行を表示する
     *
     */
    private function _mixout_daycol_header()
    {
        echo "<tr>\n";

        // 時間割時間のカスタマイズ出力
        echo apply_filters('mtssb_mixday_col_timeheader', '', array('row' => -1, 'cid' => $this->params['calendar_id']));

        // 各予約品目名の出力
        foreach ($this->articles as $article_id => $article) {
            $this->_mixout_day_articlename($article, ($this->params['time_cell'] ? 2 : 0), 'col');
        }

        echo "</tr>\n";
    }

    private function _mixout_daycol_calendar($timeno, $article)
    {
        // 時間割のコマが定義数を超えている場合は空白を出力する
        if (count($article['timetable']) <= $timeno) {
            if ($this->params['time_cell']) {
                echo '<th class="mix-day header-time-space"></th>';
            }
            echo '<td class="mix-day no-data"></td>' . "\n";
            return;
        }

        // 予約日時
        $thetime = $article['timetable'][$timeno];
        $booking_time = $thetime + $this->day_time;

        // 時間割時間の表示
        if ($this->params['time_cell']) {
            $time_str = apply_filters('mtssb_mixday_timetable_time', date('H:i', $thetime), array(
                'tabletime' => $thetime, 'cid' => $this->params['calendar_id']));
            echo sprintf('<th class="mix-daycol header-time">%s</th>', $time_str);
        }

        // 指定日時の予約状況の表示
        $this->_mixout_day_booking_time($article, $booking_time);
    }

}
