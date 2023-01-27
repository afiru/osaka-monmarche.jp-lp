<?php
/**
 * MTS Simple Booking Articles スケジュール管理モジュール
 *
 * @Filename	mtssb-schedule-admin.php
 * @Date		2019-07-29
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-04-17
 * Updated to 1.32.1 on 2019-12-13
 * Updated to 1.32.0 on 2019-07-29
 */

class MTSSB_Schedule_Admin
{
	const PAGE_NAME = 'simple-booking-schedule';

    const JS = 'js/mtssb-schedule-admin.js';
    const UI_CSS = 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css';

	private static $iSchedule = null;

    // Googleカレンダーのデータ取得情報
    private static $iCalFile = "https://www.google.com/calendar/ical/japanese__ja@holiday.calendar.google.com/public/basic.ics";

    private $domain;

    private static $weeks = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');

    // 予約品目データ
    private $articles = array();

    // スケジュール
    private $schedules = array();

    // 祝祭日データ
    private $holidays = array();

    // 操作対象
    private $year = 0;              // 年
    private $article_id;            // 予約品目

    // 本日Unix TIme
    private $todayTime;

	// 操作対象データ
	private $message = '';
	private $errflg = false;

    /**
	 * インスタンス化
	 */
	static function get_instance() {
		if (!isset(self::$iSchedule)) {
			self::$iSchedule = new MTSSB_Schedule_Admin();
		}

		return self::$iSchedule;
	}

	public function __construct()
	{
		global $mts_simple_booking;

		$this->domain = MTS_Simple_Booking::DOMAIN;

        wp_enqueue_script(self::PAGE_NAME, plugins_url(self::JS, __FILE__), array('jquery', 'jquery-ui-dialog'));
        wp_enqueue_style(self::PAGE_NAME . '-ui', self::UI_CSS);

		// CSSロード
		$mts_simple_booking->enqueue_style();
	}

    /**
     * スケジュールカレンダー表示
     */
    public function schedule_page()
    {
        // 予約品目の読み込み
        $this->articles = MTSSB_Article::get_all_articles();
        if (empty($this->articles)) {
            $this->errorView(__('The exhibited reservation item data has nothing.', $this->domain), '');
            return;
        }

        // 入力処理
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], self::PAGE_NAME)) {
            if (isset($_POST['save_schedule'])) {
                $year = (int) $_POST['year'];
                $articleId = (int) $_POST['article_id'];
                if ($this->_inputSchedule()) {
                    if ($this->_saveSchedule($articleId, $year)) {
                        $this->message = __('Schedule has been saved.', $this->domain);
                    } else {
                        $this->errorView('SAVE_FAILED', $articleId);
                        return;
                    }
                }
            }

            // 予約品目・捜査対象年切り換え
            elseif (isset($_POST['change_article'])) {
                $year = (int) $_POST['year'];
                $articleId = (int) $_POST['article_id'];
            }

        // 入力なし
        } else {
            $year = (int)date_i18n('Y');
            $articleId = key($this->articles);
        }

        // 予約品目IDの確認
        if (!array_key_exists($articleId, $this->articles)) {
            $this->errorView(__('Not found any articles of reservation.', $this->domain), $articleId);
            return;
        }

        // 予約品目先頭のスケジュールデータを読み込む
        if (empty($this->schedules)) {
            $this->schedules = $this->_readSchedule($articleId, $year);
        }

        // 祝祭日データを取得する
        $yearTime = mktime(0, 0, 0, 1, 1, $year);
        $endTime = mktime(0, 0, 0, 1, 1, $year + 1);
        $this->holidays = $this->getHolidays($yearTime, $endTime);

        $this->year = $year;
        $this->article_id = $articleId;
        //$this->todayTime = mktime(0, 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('y'));
        $ymd = explode('-', date_i18n('Y-n-j'));
        $this->todayTime = mktime(0, 0, 0, $ymd[1], $ymd[2], $ymd[0]);

        $this->editView();
    }

    // 送信されたスケジュールデータを取り込む
    private function _inputSchedule()
    {
        // 送信データを取得する
        $data = json_decode(stripslashes($_POST['schedule_data']), true);

        foreach ($data as $dateTime => $schedule) {
            $month = date('m', $dateTime);
            $day = date('d', $dateTime);
            $this->schedules[$month][$day] = $schedule;
        }

        return true;
    }

    // スケジュールデータを保存する
    private function _saveSchedule($articleId, $year)
    {
        for ($i = 1; $i <= 12; $i++) {
            $month = sprintf('%02d', $i);
            $keyName = MTS_Simple_Booking::SCHEDULE_NAME . $year . $month;
            if (!add_post_meta($articleId, $keyName, $this->schedules[$month], true)) {
                update_post_meta($articleId, $keyName, $this->schedules[$month]);
            }
        }

        return true;
    }

    // 対象年のスケジュールデータを読み込む
    private function _readSchedule($articleId, $year)
    {
        $schedules = array();

        // 対象年月のスケジュールデータの読み込み
        for ($i = 1; $i <= 12; $i++) {
            $monthtime = mktime(0, 0, 0, $i, 1, $year);
            $keyName = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $monthtime);
            $schedule = get_post_meta($articleId, $keyName, true);
            if (!$schedule) {
                $schedule = $this->_newMonth($monthtime);
            }
            $schedules[sprintf('%02d', $i)] = $schedule;
        }

        return $schedules;
    }

    // 1ヶ月の空スケジュール取得
    private function _newMonth($monthtime)
    {
        // 当月日データ構築
        $nextMonth = mktime(0, 0, 0, date('n', $monthtime) + 1, 1, date('Y', $monthtime));

        $schedule = array();

        for ($daytime = $monthtime; $daytime < $nextMonth; $daytime += 86400) {
            $schedule[date('d', $daytime)] = $this->_newDay();
        }

        return $schedule;
    }

    // 1日の空スケジュールデータ
    private function _newDay()
    {
        return array(
            'open' => 0,		// 0:閉店 1:開店
            'delta' => 0,		// 予約数量の増減
            'class' => '',		// class 表示データ
            'note' => '',		// Note カレンダー表示データ
        );
    }

    /**
     * Googleカレンダーから祝祭日を取得する
     */
    public function getHolidays($startTime, $endTime)
    {
        $cafile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'ca-bundle.crt';

        $options = array(
            'ssl' => array(
                'cafile' => $cafile,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        );

        $iCal = @file_get_contents(self::$iCalFile, false, stream_context_create($options));

        if (empty($iCal)) {
            return false;
        }

        $iCal = preg_replace("/\r\n|\r|\n/", "\n", $iCal);

        // 祝日一覧配列を取得する
        $holidays = $this->_getHolidays($iCal, $startTime, $endTime);

        return $holidays;
    }

    // 祝日一覧配列を取得する
    private function _getHolidays($iCal, $startTime, $endTime)
    {
        // 祝日一覧配列を生成する
        $pos = 0;
        $dateTime = '';
        $holidays = array();

        while ($str = $this->_getLine($pos, $iCal)) {
            if (empty($dateTime)) {
                if (preg_match("/^DTSTART;VALUE=DATE:(\d*)$/", $str, $matches)) {
                    $unixTime = strtotime(sprintf('%s-%s-%s', substr($matches[1], 0, 4), substr($matches[1], 4, 2), substr($matches[1], 6, 2)));
                    if ($startTime <= $unixTime && $unixTime < $endTime) {
                        $dateTime = $unixTime;
                    }
                }
            } elseif (preg_match("/^SUMMARY:(.*)$/", $str, $matches)) {
                $holidays[$dateTime] = $matches[1];
                $dateTime = '';
            }

            $pos += mb_strlen($str) + 1;
        }

        // 日付順にソートする
        ksort($holidays, SORT_NUMERIC);

        return $holidays;
    }

    // 1行取得する
    private function _getLine($pos, $string)
    {
        // 次の文字位置
        $nextPos = mb_strpos($string, "\n", $pos);

        if ($nextPos !== false) {
            return mb_substr($string, $pos, $nextPos - $pos);
        }

        return false;
    }

    /**
     * スケジュールカレンダー編集画面の表示
     */
    public function editView()
    {
        ob_start();
?>
        <div class="wrap">
            <h1 id="schedule-title"><?php _e('Schadule Management', $this->domain); ?></h1>
            <?php if (!empty($this->message)) {
                $this->_outMessage();
            } ?>

            <table id="schedule-box">
                <tr><td id="schedule-box-header">
                    <div class="action-box">
                        <?php $this->_selectArticle() ?>
                    </div>
        
                    <h2 id="schedule-year"><?php echo sprintf('%s %04d%s', $this->articles[$this->article_id]['name'], $this->year, __('Year', $this->domain)) ?></h2>
        
                    <div class="action-box">
                        <?php $this->_calendarOperation() ?>
                    </div>
                </td></tr>
                <tr><td>
                    <div class="calendar-year action-box">
                        <?php $this->_calendarAll() ?>
                    </div>
                    <div class="clear"></div>
        
                    <form method="post">
                        <input type="submit" class="button-primary" name="save_schedule" onclick="return mtssb_schedule_admin.setScheduleData()" value="<?php _e('Save', $this->domain) ?>">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(self::PAGE_NAME) ?>">
                        <input type="hidden" name="article_id" value="<?php echo $this->article_id ?>">
                        <input type="hidden" name="year" value="<?php echo $this->year ?>">
                        <input id="article_schedule_data" type="hidden" name="schedule_data" value="">
                    </form>
                </td></tr>
            </table>

            <div id="shop-day-dialog" style="display:none">
                <?php $this->_outDialogBox() ?>
            </div>
            <div id="check-holidays" style="display:none">
                <?php $this->_outNationalHolidays() ?>
            </div>

        </div>

<?php
        ob_end_flush();
    }

    // 予約品目と編集対象年
    private function _selectArticle()
    {
        // 予約品目選択肢
        $articles = array();
        foreach ($this->articles as $articleId => $artcile) {
            $articles[$articleId] = $artcile['name'];
        }

        // 年選択肢
        $years = array($this->year - 1, $this->year, $this->year + 1);
?>
        <div id="schedule-article-select">
            <form method="post" action="?page=<?php echo self::PAGE_NAME ?>">
                <span class="operation-action select-article">
                    <label for="select-article-id"><?php _e('Booking Article', $this->domain) ?>：</label>
                    <select id="select-article-id" name="article_id"><?php foreach ($this->articles as $articleId => $article) {
                        $selected = $articleId == $this->article_id ? ' selected="selected"' : '';
                        echo sprintf('<option value="%d"%s>%s</option>', $articleId, $selected, $article['name']);
                    } ?></select>
                </span>
                <span class="operation-action select-year">
                    <label for="select-year"><?php _e('Year', $this->domain) ?>：</label>
                    <select id="select-year" name="year"><?php foreach ($years as $year) {
                        $selected = $year == $this->year ? ' selected="selected"' : '';
                        echo sprintf('<option value="%d"%s>%d</option>', $year, $selected, $year);
                    } ?></select>
                </span>
                <span class="operation-action form-send">
                    <input type="submit" class="button-secondary" name="change_article" value="<?php _e('Change', $this->domain) ?>">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(self::PAGE_NAME) ?>">
                </span>
            </form>
        </div>

<?php
    }

    // カレンダー一括操作ボックス
    private function _calendarOperation()
    {
?>
        <span class="operation-action">
            <label for="alter-day-month"><?php _e('Select Month', $this->domain) ?>：</label>
            <select id="alter-day-month"><option value="0"><?php _e('All', $this->domain) ?></option><?php
                for ($i = 1; $i <= 12; $i++) {
                    echo sprintf('<option value="%d">%d</option>', $i, $i);
                } ?></select>
        </span>
        <span class="operation-action">
            <label for="alter-day-week"><?php _e('Select Week', $this->domain) ?>：</label>
            <select id="alter-day-week"><option value="all"><?php _e('All', $this->domain) ?></option><?php
                foreach (self::$weeks as $week) {
                    echo sprintf('<option value="%s">%s</option>', $week, __(ucfirst($week)));
                } ?></select>
         </span>
         <span class="operation-action">
            <input id="alter-close" type="button" class="button" value="<?php _e('Close', $this->domain) ?>">
            <input id="alter-open" type="button" class="button" value="<?php _e('Open', $this->domain) ?>">
        </span>
        <span class="operation-action">
            <label for="get-holidays"><?php _e('National Holidays', $this->domain) ?>：</label>
            <input id="get-holidays" type="button" class="button" value="<?php _e('Read', $this->domain) ?>">
        </span>

<?php
    }

    // カレンダー編集表示１２カ月
    private function _calendarAll()
    {
        for ($month = 1; $month <= 12; $month++) {
            echo '<div class="admin-eigyo-calendar">' . "\n";
            echo $this->_monthCalendar($month);
            echo "</div>\n";
        }
    }

    // １カ月カレンダーの表示
    private function _monthCalendar($month)
    {

        // 週最初の曜日
        $startOfWeek = get_option('start_of_week');

        // カレンダー先頭のUnix Time
        $monthTime = mktime(0, 0, 0, $month, 1, $this->year);
        $startTime = $monthTime - (date('w', $monthTime) - $startOfWeek + 7) % 7 * 86400;

        // 表示翌月のUnix Time
        $nextMonth = mktime(0, 0, 0, $month + 1, 1, $this->year);

        ob_start();
?>
        <table id="month-table-<?php echo $month ?>" class="month-table">
            <caption><div class="caption-title" data-month="<?php echo $month ?>"><?php echo sprintf('%d%s', $month, __('Month', $this->domain)) ?></div></caption>
            <tr class="week-row-name"><?php for ($i = 0, $weekNo = $startOfWeek; $i < 7; $i++, $weekNo = ++$weekNo % 7) {
                echo sprintf('<th class="%s">%s</th>', self::$weeks[$weekNo], __(ucfirst(self::$weeks[$weekNo])));
            } ?></tr>

<?php
        // カレンダー先頭の時間をセットして１カ月表示
        for ($bx = 0, $datetime = $startTime; $bx < 42; $bx++, $datetime += 86400) {

            if ($bx % 7 == 0) {
                if (0 < $bx) {
                    echo "</tr>\n";
                }

                // 月末日を越えた場合は表示を終了する
                if ($nextMonth <= $datetime) {
                    break;
                }

                echo sprintf('<tr class="week-row-%d">', intval($bx / 7) + 1);
            }

            // 曜日No
            $weekNo = ($startOfWeek + $bx) % 7;

            // カレンダー日付処理
            if ($monthTime <= $datetime && $datetime < $nextMonth) {
                $this->_outDate($datetime, $month);
            }

            // カレンダー日付外処理
            else {
                $this->_outNoDate($weekNo);
            }
        }

        echo "</table>\n";

        return ob_get_clean();
    }

    // カレンダー日付(TDセル)の表示
    private function _outDate($datetime, $month)
    {
        $day = (int) date('j', $datetime);

        $schedule = $this->schedules[sprintf('%02d', $month)][sprintf('%02d', $day)];

        echo sprintf('<td class="%s">', $this->_tdClass($datetime, $month, $day, $schedule));

        // day number
        echo sprintf('<div class="day-number"><span class="day-number-str">%d</span></div>', $day);

        // day content
        echo $this->_dayData($datetime, $schedule);

        echo "</td>\n";
    }

    // tdのclassを構成する
    private function _tdClass($datetime, $month, $day, $schedule)
    {
        $tdClass = array(sprintf('day-%d', $day));

        $tdClass[] = self::$weeks[(int) date('w', $datetime)];

        $tdClass[] = $schedule['open'] ? 'open' : 'close';
        if (!empty($schedule['class'])) {
            $tdClass[] = $schedule['class'];
        }

        if ($datetime == $this->todayTime) {
            $tdClass[] = 'today';
        }

        return implode(' ', $tdClass);
    }

    // dayコンテンツ
    private function _dayData($datetime, $schedule)
    {
        ob_start();
        echo sprintf('<div class="day-delta">%d</div>', $schedule['delta']);
        echo sprintf('<div class="day-memo" data-datetime="%d" data-open="%d" data-class="%s" data-delta="%s" data-note="%s">%s</div>',
            $datetime, $schedule['open'], $schedule['class'], $schedule['delta'], $schedule['note'], $schedule['note']);

        return ob_get_clean();
    }

    // カレンダー日付外(TDセル)の表示
    private function _outNoDate($weekNo)
    {
        echo sprintf('<td class="no-day %s"></td>', self::$weeks[$weekNo]) . "\n";
    }

    // 編集ダイアログボックス出力
    private function _outDialogBox()
    {
?>
        <h3 id="shop-day-date"></h3>
        <table class="shop-day-edit">
            <tr>
                <th>予約受付</th>
                <td>
                    <label><input type="radio" name="dialog_open" value="0"> 中止</label>
                    <label><input type="radio" name="dialog_open" value="1"> 受付</label>
                </td>
            </tr>
            <tr>
                <th>予約数増減</th>
                <td>
                    <input id="dialog-delta" class="mts-small" type="text" name="dialog_delta" value="0">
                </td>
            </tr>
            <tr>
                <th>CLASS</th>
                <td>
                    <input id="dialog-class" type="text" name="dialog_class" value="">
                </td>
            </tr>
            <tr>
                <th>注記</th>
                <td>
                    <input id="dialog-note" type="text" name="dialog_note" value="">
                </td>
            </tr>
        </table>

<?php
    }

    // 対象年の祝祭日を出力する
    private function _outNationalHolidays()
    {
        echo sprintf('<input id="national-holidays" type="hidden" value="%s">', empty($this->holidays) ? '' : esc_html(json_encode($this->holidays)));

        if (empty($this->holidays)) :
            echo "祝祭日データを取得できませんでした。";
        else :
?>
            <table id="calendar-holidays">
                <?php foreach ($this->holidays as $daytime => $title) {
                    echo sprintf("<tr><td>%s</td><td>%s</td></tr>\n", date_i18n('Y-n-j', $daytime), $title);
                } ?>
            </table>

<?php
        endif;
    }

    // メッセージ表示
    private function _outMessage()
    {
        static $messageClass = array(
            1 => 'error',
            2 => 'warning',
            3 => 'success',
            4 => 'info',
        );

        $messageNo = $this->errflg ? 1 : 3;
?>
        <div class="notice notice-<?php echo $messageClass[$messageNo] ?>">
            <p><?php echo $this->message ?></p>
        </div>

<?php
    }

    /**
     * エラーメッセージを表示する
     */
    public function errorView($errCode, $errSub)
    {
        $this->setError($errCode, $errSub);

        ob_start();
?>
        <div class="wrap">
            <?php $this->_outMessage() ?>
        </div>
<?php
        ob_end_flush();
    }

    /**
     * エラーメッセージをセットする
     */
    public function setError($errCode, $errSub='')
    {
        $errMessage = array(
            'SAVE_FAILED' => '保存できませんでした。'
        );

        $this->errflg = true;
        $this->message = sprintf((isset($errMessage[$errCode]) ? $errMessage[$errCode] : $errCode), $errSub);

        return false;
    }

}
