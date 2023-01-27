<?php
/**
 * MTS Simple Booking カレンダー日付処理クラスモジュール
 *
 * @Filename    MtssbCalendar.php
 * @Date        2014-07-09
 * @Implemented Ver.1.17.0
 * @Author      S.Hayashi
 *
 * Updated to 1.32.0 on 2019-11-25
 * Updated to 1.30.0 on 2019-02-07
 * Updated to 1.29.0 on 2019-01-08
 * Updated to 1.28.2 on 2018-02-27
 * Updated to 1.28.0 on 2017-10-26
 * Updated to 1.19.1 on 2014-12-16
 * Updated to 1.19.0 on 2014-10-31
 */
class MtssbCalendar
{
    private $data = array(
	// 予約カレンダー表示　日付データ
        'thisYear' => 0,			// 本日年
        'thisMonth' => 0,   		// 本日月
        'thisDay' => 0, 			// 本日日
        'todayTime' => 0,			// 本日0時 unix time
        'monthTime' => 0,			// 今月1日 unix time
        'lastDayTime' => 0,         // 予約受付最終日0時 unix time
        'closeTime' => 0,           // 予約受付終了時刻 単位:秒
        'openTime' => 0,			// 予約受付開始年月日 unix time(未満)
        'openNextMonth' => 0,       // 予約受付開始翌月 unix time(未満)
        'startOfWeek' => 0,         // WordPress開始曜日

        'calendarTime' => 0,        // 表示カレンダー月1日 unix time
        'calendarYear' => 0,        // 表示カレンダー年
        'calendarMonth' => 0,       // 表示カレンダー月
        'prevTime' => 0,            // 表示カレンダー前月
        'nextTime' => 0,            // 表示カレンダー翌月
        'topCalendarTime' => 0,     // 表示カレンダー月先頭オフセット unix time
    );

    public $controls = NULL;

    public function __construct($domain)
    {
        // カレンダー設定データ取得
        $this->controls = get_option($domain . '_controls');

        // 本日の情報
        $current_time = current_time('timestamp');
        $today_a = explode('-', date_i18n('Y-n-j', $current_time));
        $this->thisYear = (int) $today_a[0];
        $this->thisMonth = (int) $today_a[1];
        $this->thisDay = (int) $today_a[2];

        // 本日0時のunix time
        $this->todayTime = mktime(0, 0, 0, $this->thisMonth, $this->thisDay, $this->thisYear);

        // 今月1日のunix time
        $this->monthTime = mktime(0, 0, 0, $this->thisMonth, 1, $this->thisYear);

        // 直近予約受付終了日time(受付開始日のunix time)
        $marginDays = intval($this->controls['start_accepting'] / 1440);
        $this->lastDayTime = $this->todayTime + $marginDays * 86400;

        // 受付終了時刻(未満)
        $this->closeTime = 86400;

        // 受付終了時刻　受付終了日が当日なら現在時刻＋マージン時間
        if ($marginDays <= 0) {
            $this->closeTime = $current_time - $this->todayTime + $this->controls['start_accepting'] * 60;
        }

        // 受付終了時刻　受付終了時刻が設定されているならその時間
        elseif (0 < intval($this->controls['until_accepting'])) {
            $this->closeTime = $this->controls['until_accepting'] * 60;
        }

        // 予約受付開始翌月(翌月1日のUnix Time)
        $this->openNextMonth = mktime(0, 0, 0, $this->thisMonth + $this->controls['period'] + 1, 1, $this->thisYear);

        // 予約受付開始(Unix Time) 月区切りと日付区切り
        if (empty($this->controls['hedge'])) {
            $this->openTime = mktime(0, 0, 0, $this->thisMonth + $this->controls['period'], 1, $this->thisYear);
        } else {
            $this->openTime = mktime(0, 0, 0, $this->thisMonth + $this->controls['period'], $this->thisDay, $this->thisYear);
        }

        // WordPress開始曜日
        $this->startOfWeek = get_option('start_of_week');
    }

    /**
     * 現在時刻タイム
     *
     */
    public function currentTime()
    {
        return current_time('timestamp') - $this->todayTime;
    }

    /**
     * 月カレンダー表示対象範囲かチェックする
     *
     */
    public function isMonthly($monthtime)
    {
        if ($monthtime < $this->monthTime || $this->openTime <= $monthtime) {
            return false;
        }

        return true;
    }

    /**
     * 該当日が時間割カレンダー表示対象かチェックする
     *
     */
    public function isTimetableDay($daytime)
    {
        // 該当日がカレンダー表示内か確認する
        if ($daytime < $this->lastDayTime || $this->openTime <= $daytime) {
            return false;
        }

        // 該当日が受付開始日の場合、受付時刻が終了していないか確認する
        if ($daytime == $this->lastDayTime && $this->closeTime <= $this->currentTime()) {
            return false;
        }

        return true;
    }

    /**
     * 予約日時が予約受付対象時間内か家訓する
     *
     */
    public function isBookingTime($bookingTime)
    {
        $dayTime = intval($bookingTime / 86400) * 86400;

        // 予約受付対象日の範囲内か確認する
        if ($dayTime < $this->lastDayTime || $this->openTime <= $dayTime) {
            return false;
        }

        // 終了時間指定の場合
        if ($this->controls['start_accepting'] < 1440) {
            // 予約時間が終了時間前か確認する
            if ($bookingTime < $this->lastDayTime + $this->closeTime) {
                return false;
            }

        // 終了時刻指定の場合
        } else {
            // 現在時が終了時間前か確認する
            if ($dayTime == $this->lastDayTime && $this->closeTime <= $this->currentTime()) {
                return false;
            }
        }

        return true;
    }

    /**
     * カレンダー表示月を決定する(yyyy年mm月1日)
     *
     */
    public function defCalendarTime($params)
    {
        // 対象年月
        $theyear = intval($params['year']);
        $themonth = intval($params['month']);

        // ページ切り替え
        if (isset($_REQUEST['ym']) && preg_match('/\A([\d]{4})(-|\/)([\d]{1,2})\z/', $_REQUEST['ym'], $ym)
            && (!isset($_REQUEST['cid']) || $_REQUEST['cid'] == $params['calendar_id'])) {
            $theyear = (int) $ym[1];
            $themonth = (int) $ym[3];
        }

        // 対象年月チェック
        $this->calendarTime = mktime(0, 0, 0, $themonth, 1, $theyear);
        if (!$this->isMonthly($this->calendarTime)) {
            $this->calendarTime = $this->monthTime;
            $theyear = $this->thisYear;
            $themonth = $this->thisMonth;
        }

        // カレンダー表示年・月
        $this->calendarYear = $theyear;
        $this->calendarMonth = $themonth;

        // カレンダー表示翌月
        $this->prevTime = mktime(0, 0, 0, $this->calendarMonth - 1, 1, $this->calendarYear);
        $this->nextTime = mktime(0, 0, 0, $this->calendarMonth + 1, 1, $this->calendarYear);

        // カレンダー先頭のUnixTime
        $this->topCalendarTime = $this->calendarTime - (7 + date_i18n('w', $this->calendarTime) - $this->startOfWeek) % 7 * 86400;
    }

    /**
     * 時間割カレンダー表示日を決定する
     *
     */
    public function defDayTime($params)
    {
        // パラメータで年月日が指定された場合
        if (0 < intval($params['day'])) {
            return mktime(0, 0, 0, $params['month'], $params['day'], $params['year']);
        }

        // 日付がGETパラメータで指定された場合
        if (isset($_GET['ymd']) && (!isset($_GET['cid']) || $_GET['cid'] == $params['calendar_id'])) {
            $dayTime = intval($_GET['ymd']);
            if ($this->todayTime <= $dayTime && $dayTime < $this->openNextMonth) {
                return $dayTime;
            }
        }

        return false;
    }

    /**
     * プロパティから読み出す
     *
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        if (isset($this->$key)) {
            return $this->$key;
        }

        $trace = debug_backtrace();
        trigger_error(sprintf(
            "Undefined property: '%s&' in %s on line %d, E_USER_NOTICE",
            $key, $trace[0]['file'], $trace[0]['line']
        ));

        return null;
    }

    /**
     * プロパティをセットする
     *
     */
    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        } else {
            $this->$key = $value;
        }

        return $value;
    }

    /**
     * 環境設定された表示マークを取得する
     *
     * @param   $mark (vacant,booked,low,full,disable)
     * @return  mixed
     */
    public function getMarking($mark, $remain=0)
    {
        $marking = $this->controls['disable'];
        $markIdx = $mark . '_mark';

        if (isset($this->controls[$markIdx])) {
            $marking = empty($this->controls[$markIdx]) ? $remain : $this->controls[$markIdx];
        }

        return $marking;
    }


    /**
     * ショートコード共通パラメータ初期値
     *
     */
    public function commonParams()
    {
        return array(
            'id' => '-1',
            'class' => '',
            'anchor' => '',			// 時間割テーブル表示anchor出力指定
            'year' => $this->thisYear,
            'month' => $this->thisMonth,
            'day' => '0',           // 日付指定で当該日の時間割カレンダーへ
            'dayform' => '0',       // 0:リンク 1:セレクトボックス
            'pagination' => '1',    // 前翌月リンク表示指定(0:非表示,1:下,2:上,3:上下)
            'caption' => '1',       // キャプション表示
            'link' => '1',          // 予約フォームページへリンクする
            'weeks' => '',          // 曜日表示文字列(日曜日先頭のカンマ区切り)
            'skiptime' => '0',      // 当該日の時間割表示をスキップする
            'linkfull' => '0',      // 月カレンダー表示で満席日の時間割表示リンク(0:なし,1:リンク)
            'term' => '1',          // 月カレンダー出力月数
            'low' => '0',           // 指定数量以下をlow判定する
            'title' => '',          // タイトル
            'widget' => '0',        // ウィジュエット表示(1:月カレンダー)
            'suppression' => '0',	// disable 出力抑止(未実装)
            'calendar_id' => '',    // 予約カレンダー識別子(ウィジェットカレンダーとの切り分けID)
        );
    }

}