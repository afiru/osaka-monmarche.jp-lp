<?php
/**
 * MTS Simple Booking 予約ページ処理モジュール
 *
 * @Filename	mtssb-booking-form.php
 * @Date		2012-05-15
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-04-28
 * Updated to 1.31.0 on 2019-05-17
 * Updated to 1.29.0 on 2018-03-28
 * Updated to 1.28.3 on 2018_03_08
 * Updated to 1.28.0 on 2017-12-08
 * Updated to 1.27.0 on 2017-08-03
 * Updated to 1.24.0 on 2016-07-26
 * Updated to 1.23.1 on 2016-05-16
 * Updated to 1.18.0 on 2014-10-27
 * Updated to 1.17.0 on 2014-07-08
 * Updated to 1.15.0 on 2014-01-30
 * Updated to 1.13.0 on 2014-01-03
 * Updated to 1.12.0 on 2013-12-18
 * Updated to 1.11.0 on 2013-11-21
 * Updated to 1.9.0 on 2013-07-18
 * Updated to 1.8.5 on 2013-07-02
 * Updated to 1.8.0 on 2013-06-20
 * Updated to 1.7.0 on 2013-05-08
 * Updated to 1.6.0 on 2013-03-19
 * Updated to 1.5.0 on 2013-03-07
 * Updated to 1.4.5 on 2013-02-20
 * Updated to 1.4.0 on 2013-02-04
 * Updated to 1.3.0 on 2013-01-08
 * Updated to 1.2.0 on 2012-12-26
 * Updated to 1.1.5 on 2012-12-02
 * Updated to 1.1.1 on 2012-11-01
 * Updated to 1.1.0 on 2012-10-13
 */
if (!class_exists('MTSSB_Booking')) {
    require_once(dirname(__FILE__) . '/mtssb-booking.php');
}
if (!class_exists('MtssbCalendar')) {
    require_once(dirname(__FILE__) . '/lib/MtssbCalendar.php');
}

class MTSSB_Booking_Form extends MTSSB_Booking {

    const PAGE_NAME = 'booking-form';
    const JS_PATH = 'js/mtssb-booking.js'; // JavaScript file path
    const JS_ASSISTANCE = 'js/mts-assistance.js';
    const JS_POSTCODEJP = 'js/mts-postcodejp.js';
    const POSTCODEJP = 'https://postcode-jp.com/js/postcodejp.js';
    const LOADER_FILE = 'image/ajax-loader.gif';

    // 予約条件パラメータ
    public $controls;
    public $charge;
    // 顧客データのカラム情報
    private $reserve;  // 各種設定　予約メール
    // 予約日時に関する情報
    private $thetime;  // 予約対象日時 Unix Time
    private $calendar = NULL;   // 予約受付カレンダー処理モジュール
    // 予約品目
    private $article_id;
    public $article;
    // 当該日スケジュール(array('open','delta','class'));
    private $schedule;
    // 入力フォームメッセージ
    private $message;
    private $iconUrl;
    private $inout;

    /**
     * Error
     */
    private $err_message = '';
    private $errmsg = array();

    /**
     * Constructor
     *
     */
    public function __construct() {

        parent::__construct();

        // 予約条件パラメータのロード
        $this->controls = get_option($this->domain . '_controls');
        $this->charge = get_option($this->domain . '_charge');

        // 表示ページのURL
        $this->this_page = get_permalink();

        // 時間情報の取得
        $this->calendar = new MtssbCalendar($this->domain);

        // 顧客データのカラム利用設定情報を読込む
        $this->reserve = get_option($this->domain . '_reserve');

        // AJAXローディングアイコン画像のURL
        $this->iconUrl = plugins_url(self::LOADER_FILE, __FILE__);
    }

    /**
     * 予約登録前の入力チェック
     *
     */
    public function check_post_booking() {
        // NONCEチェック
        if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
            $this->err_message = $this->_err_message('NONCE_ERROR');
            return false;
        }

        // 予約品目、予約時間の事前チェック
        if (!$this->pre_check()) {
            return false;
        }

        // 予約データを正規化し、登録データを取得する
        $this->booking = $this->normalize_booking($_POST['booking'], $this->article['count']);

        // 入力チェック
        $check_mail2 = false;
        if (!$this->_input_validation($check_mail2)) {
            $this->err_message = $this->_err_message('ERROR_BEFORE_ADDING');
            $this->err_message .= '<br>' . implode($this->errmsg, '<br>');
            return false;
        }

        // 重複予約チェック
        elseif (!$this->_check_multiple_booking()) {
            $this->err_message = $this->_err_message('ERROR_MULTIPLE_BOOKING');
            return false;
        }

        return true;
    }

    /**
     * フォーム入力予約登録処理
     *
     */
    public function front_booking() {
        // 予約を新規登録する
        $booking_id = $this->add_series_booking();
        if (!$booking_id) {
            $this->err_message = $this->_err_message('ERROR_ADD_BOOKING');
            return false;
        }

        $this->booking['booking_id'] = $booking_id;
        return $booking_id;
    }

    /**
     * メールの送信エラーメッセージをセット
     *
     */
    public function error_send_mail() {
        $this->err_message = $this->_err_message('ERROR_SEND_MAIL');
    }

    /**
     * PayPalの接続エラー、予約エラーのメッセージをセット
     *
     */
    public function error_paypal($errmsg) {
        switch ($errmsg) {
            case 'CLOSED_BOOKING' :
                $message = '予約の受け付けが終了してます。';
                $errmsg = apply_filters('mts_booking_form_paypal_error', $message, $errmsg);
                break;
            case 'BOOKING_ERROR' :
                $message = '予約の登録でエラーが発生しました。電話で予約の確認をお願いします。';
                $errmsg = apply_filters('mts_booking_form_paypal_error', $message, $errmsg);
                break;
            default :
                break;
        }

        $this->err_message = $errmsg;
    }

    /**
     * ステータス別予約フォーム処理
     * the_content フィルター処理
     */
    public function booking_form($content) {
        global $mts_simple_booking;

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        // 予約登録処理実行の後処理
        if ($action == 'thanks' || is_page(MTS_Simple_Booking::PAGE_BOOKING_THANKS)) {
            return $this->_out_completed($content);
        }

        // 上位でエラーの場合はエラー表示する
        if (!empty($this->err_message)) {
            return $this->_out_errorbox();
        }

        // 予約品目、予約時間の事前チェック
        if (!$this->pre_check()) {
            return $this->_out_errorbox();
        }

        // SUBMIT処理
        if (isset($_POST['action']) && $action == 'validate') {

            // NONCEチェック
            if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
                $this->err_message = $this->_err_message('NONCE_ERROR');
                return $this->_out_errorbox();
            }

            // 予約データを正規化し、登録データを取得する
            $this->booking = $this->normalize_booking($_POST['booking'], $this->article['count']);

            // 入力チェック
            if ($this->_input_validation()) {
                // 重複予約チェック
                if (!$this->_check_multiple_booking()) {
                    $this->err_message = $this->_err_message('ERROR_MULTIPLE_BOOKING');
                    return $this->_out_errorbox();
                }

                return $content . $this->_confirming_form();
            }

            // PayPal処理されていなければ新規(PayPalからのリターンは上位でデータセット済み)
        } 
        else if (empty($mts_simple_booking->oPPManager)) {
            $this->booking = $this->new_booking($this->thetime, $this->article_id);
        }

        return $content . $this->_input_form();
    }

    /**
     * 予約処理の共通となる事前チェック (対象予約品目IDを取得)
     *
     */
    public function pre_check() {
        global $mts_simple_booking;

        // PayPalからリターンしたときの処理
        if (isset($_GET['pp']) && !empty($mts_simple_booking->oPPManager)) {
            $this->thetime = $this->booking['booking_time'];
            $this->article_id = $this->booking['article_id'];
        } else {
            // 予約日時
            if (isset($_POST['booking']['booking_time'])) {
                $this->thetime = intval($_POST['booking']['booking_time']);
            } else {
                $this->thetime = isset($_REQUEST['utm']) ? intval($_REQUEST['utm']) : 0;
            }

            // 予約品目の取得
            if (isset($_POST['booking']['article_id'])) {
                $this->article_id = intval($_POST['booking']['article_id']);
            } else {
                $this->article_id = isset($_REQUEST['aid']) ? intval($_REQUEST['aid']) : 0;
            }

            // 予約品目IDの通知(Ver.1.9 オプショングループ機能追加から)
            apply_filters('booking_form_set_article_id', $this->article_id);
        }

        // 予約受付の日時、対象品目の確認
        if (!$this->_booking_acceptance()) {
            return false;
        }

        return true;
    }

    /**
     * 予約受付の日時、対象品目の確認
     *
     */
    protected function _booking_acceptance() {

        // 予約受付中か確認
        if ($this->controls['available'] != 1) {
            $this->err_message = $this->_err_message('UNAVAILABLE');
            return false;
        }

        // 予約受付期間内か確認
        if (!$this->calendar->isBookingTime($this->thetime)) {
            $this->err_message = $this->_err_message('OUT_OF_PERIOD');
            return false;
        }

        // 予約スケジュールデータを取得する
        $key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $this->thetime);
        $schedule = get_post_meta($this->article_id, $key_name, true);

        // スケジュールが登録されており予約を受け付けているか確認する
        $day = date_i18n('d', $this->thetime);
        if (!empty($schedule[$day])) {
            $this->schedule = $schedule[$day];
            if ($this->schedule['open'] != 1) {
                $this->err_message = $this->_err_message('UNACCEPTABLE_DAY');
                return false;
            }
        } else {
            // スケジュールが登録されていない場合
            $this->err_message = $this->_err_message('UNAVAILABLE');
            return false;
        }

        // 予約品目データを取得する
        $this->article = MTSSB_Article::get_the_article($this->article_id);

        // 予約時間の確認
        if (!in_array($this->thetime % 86400, $this->article['timetable'])) {
            $this->err_message = $this->_err_message('UNACCEPTABLE_TIME');
            return false;
        }

        return true;
    }

    /**
     * 予約の受付数・残数確認
     * _booking_acceptance(), normalize_booking()以降に実行すること
     *
     */
    public function check_booking_vacancy() {
        // 予約日unix time
        $booking_time = $this->booking['booking_time'];
        $day_time = $booking_time - $booking_time % 86400;

        // 当該日予約データを取得する
        $booking_count = $this->get_reserved_day_count($day_time);

        // 予約可能受付数
        $restriction = $this->article['restriction'];
        $total = $this->article[$restriction] + $this->schedule['delta'];

        // オプションの予約時間割追加コマ数を取得する
        $series_number = $this->_get_series_number();

        // 予約に必要な数量
        $number = $restriction == 'capacity' ? $this->booking['number'] : 1;

        $article_id = $this->booking['article_id'];

        // 予約に空きがあるか確認する
        foreach ($this->article['timetable'] as $time) {
            $the_time = $day_time + $time;

            // 予約時間以上から確認開始
            if ($booking_time <= $the_time) {
                $remained = $total - $number;
                if (isset($booking_count[$the_time][$article_id])) {
                    if ($restriction == 'capacity') {
                        $remained -= $booking_count[$the_time][$article_id]['number'];
                    } else {
                        $remained -= $booking_count[$the_time][$article_id]['count'];
                    }
                }

                // 予約に空きがなければ終了する
                if ($remained < 0) {
                    if ($booking_time == $the_time) {
                        return 'DEFICIENT_PLACE';
                    }
                    return 'ERROR_OPTION_SERIES';
                }

                // 連続予約連続コマ数が必要な場合次の処理をする
                if (--$series_number < 0) {
                    break;
                }
            }
        }

        // 予約の連続コマ数が時間割を超えている場合は不可
        if (0 <= $series_number) {
            return 'ERROR_SERIES_OVER';
        }

        return true;
    }

    /**
     * 入力の正規化と確認
     *
     */
    protected function _input_validation($check_mail2 = true) {

        $this->errmsg = array();
        $clcols = $this->reserve['column'];

        // 入退場入力があれば時刻データを一時保管する
        if ($this->controls['message']['temps_utile'] && isset($_POST['temps_utile'])) {
            $intime = MTS_WPTime::get_utime($_POST['temps_utile']['in']['hour'], $_POST['temps_utile']['in']['minute']);
            $outtime = MTS_WPTime::get_utime($_POST['temps_utile']['out']['hour'], $_POST['temps_utile']['out']['minute']);
            $this->inout['in'] = $intime ? (new MTS_WPTime($intime)) : '';
            $this->inout['out'] = $outtime ? (new MTS_WPTime($outtime)) : '';
        }

        // 予約残数の確認
        $remain_check = $this->check_booking_vacancy();
        if ($remain_check !== true) {
            if ($remain_check == 'DEFICIENT_PLACE') {
                $this->errmsg['count'] = $this->_err_message($remain_check);
            } else {
                $this->err_message = $this->_err_message($remain_check);
            }
        }

        // 入場人数の確認
        if ($this->booking['number'] < $this->article['minimum'] || $this->article['maximum'] < $this->booking['number']) {
            $this->errmsg['count'] = $this->_err_message('INVALID_NUMBER');
        }

        // 必須入力連絡先項目の確認
        foreach ($clcols as $key => $val) {
            $chkkey = $key == 'address' ? 'address1' : $key;
            if ($val == 1 && empty($this->booking['client'][$chkkey])) {
                $this->errmsg[$key] = $this->_err_message('REQUIRED');
            }
        }

        // 年齢制限の確認
        if (1 == $clcols['birthday']) {
            $limit = $this->_age_limit();
            $age = $this->calendar->thisYear - $this->booking['client']['birthday']->year;
            if ($age < $limit['lower'] || $limit['upper'] < $age) {
                $this->errmsg['birthday'] = $this->_err_message('INVALID_AGE');
            }
        }

        // E-Mailの確認
        if (0 < $clcols['email'] && !empty($this->booking['client']['email'])) {
            if (!preg_match("/^[0-9a-z_\.\-]+@[0-9a-z_\-\.]+$/i", $this->booking['client']['email'])) {
                $this->errmsg['email'] = $this->_err_message('INVALID_EMAIL');
            } else if ($clcols['email'] == 1 && $check_mail2 && $this->booking['client']['email'] != $_POST['booking']['client']['email2']) {
                $this->errmsg['email'] = $this->_err_message('UNMATCH_EMAIL');
            }
        }

        // 郵便番号の確認
        if (0 < $clcols['postcode']) {
            if (!preg_match("/^[0-9\-]*$/", $this->booking['client']['postcode'])) {
                $this->errmsg['postcode'] = $this->_err_message('NOT_NUMERIC');
            }
        }

        // 電話番号の確認
        if (0 < $clcols['tel']) {
            if (!preg_match("/^[0-9_\-\(\)]*$/", $this->booking['client']['tel'])) {
                $this->errmsg['tel'] = $this->_err_message('NOT_NUMERIC');
            }
        }

        // オプション項目の必須入力チェック
        if ($this->article['addition']->isOption()) {
            foreach ($this->booking['options'] as $option) {
                $type = $option->getType();
                $val = $option->getValue();
                $key = $option->getKeyname();
                if ($option->getRequired() == 1) {
                    switch ($type) {
                        case 'text' :
                        case 'radio' :
                        case 'check' :
                        case 'select' :
                        case 'date' :
                        case 'time' :
                        case 'textarea' :
                            if (empty($val)) {
                                $this->errmsg['option'][$key] = $this->_err_message('REQUIRED');
                            }
                            break;
                        case 'number' :
                            if (is_numeric($val)) {
                                $message = apply_filters('booking_form_option_validate', '', $key, $val, $type);
                            } else {
                                $message = $this->_err_message('REQUIRED');
                            }
                            if (!empty($message)) {
                                $this->errmsg['option'][$key] = $message;
                            }
                            break;
                        default:
                            break;
                    }
                }
                // メッセージフィルターを利用して外部入力チェック
                $option_check = apply_filters("mtssb_option_validate_{$key}",
                        array('result' => true, 'message' => ''),
                        array('value' => $val, 'aid' => $this->booking['article_id']));
                // 外部入力チェックの結果がエラー
                if (isset($option_check['result']) && !$option_check['result']) {
                    $this->errmsg['option'][$key] = $option_check['message'];
                }
            }
        }

        if (!empty($this->errmsg) || !empty($this->err_message)) {
            return false;
        }

        // メッセージ入力に追加を付加する
        if ($this->controls['message']['temps_utile']) {
            $temps_utile = '';
            if ($this->inout['in']) {
                $temps_utile = apply_filters('booking_form_message_intime', '入場 ')
                        . (empty($this->inout['in']) ? '' : date_i18n('H時i分', $this->inout['in']->utime));
            }
            if ($this->inout['out']) {
                $temps_utile .= apply_filters('booking_form_message_outtime', '　退場 ')
                        . (empty($this->inout['out']) ? '' : date_i18n('H時i分', $this->inout['out']->utime));
            }
            $this->booking['note'] = (empty($temps_utile) ? '' : "$temps_utile\n\n") . "{$this->booking['note']}";
        }

        return true;
    }

    private function _check_multiple_booking() {
        // 同一日時の多重予約チェック
        if ($this->article['addition']->check_name + $this->article['addition']->check_email + $this->article['addition']->check_tel <= 0) {
            return true;
        }

        $name = $email = $tel = '';

        if ($this->article['addition']->check_name) {
            $name = $this->booking['client']['name'];
        }

        if ($this->article['addition']->check_email) {
            $email = $this->booking['client']['email'];
        }

        if ($this->article['addition']->check_tel) {
            $tel = $this->booking['client']['tel'];
        }

        $number = $this->findMultipleBooking($this->booking['article_id'], $this->booking['booking_time'],
                $name, $email, $tel);

        return 0 < $number ? false : true;
    }

    /**
     * エラーメッセージ
     *
     */
    protected function _err_message($err_name) {
        $errmsg = apply_filters('mts_booking_form_set_message', '', $err_name);
        if (!empty($errmsg)) {
            return $errmsg;
        }

        switch ($err_name) {
            case 'UNAVAILABLE':
                return 'ただ今予約は受け付けておりません。';
            case 'OUT_OF_PERIOD':
                return '予約受付期間外です。';
            case 'UNACCEPTABLE_DAY':
                return '指定日は予約を受け付けておりません。';
            case 'UNACCEPTABLE_TIME':
                return '指定時間は予約を受け付けておりません。';

            case 'CLOSED_BOOKING':
                return '指定日時の予約受け付けは終了しました。';

            case 'NONCE_ERROR':
                return 'Nonce Check Fault.';
            case 'INVALID_NUMBER':
                return '予約の人数が受付範囲外です。';
            case 'DEFICIENT_PLACE':
                return '予約残数が不足しています。';
            case 'REQUIRED':
                return 'この項目は必ず入力して下さい。';
            case 'INVALID_AGE':
                return '生年月日の入力が正しくありません。';
            case 'INVALID_EMAIL':
                return 'メールアドレスの指定が正しくありません。';
            case 'UNMATCH_EMAIL':
                return 'メールアドレスが確認用と一致しませんでした。';
            case 'NOT_NUMERIC':
                return '数字以外の文字が見つかりました。';

            case 'ERROR_BEFORE_ADDING':
                return '入力チェックエラーが登録前に見つかりました。';
            case 'ERROR_ADD_BOOKING':
                return '予約のデータ登録を失敗しました。';
            case 'ERROR_SEND_MAIL':
                return 'メールの送信を失敗しました。電話で予約の確認をお願いします。';

            case 'ERROR_OPTION_SERIES':
                return '追加時間の予約残数が不足しています。';
            case 'ERROR_SERIES_OVER':
                return '追加時間の予約範囲が当日中に収まりませんでした。';

            case 'ERROR_MULTIPLE_BOOKING':
                return '予約済みです。';

            default :
                return '入力エラーです。';
        }
    }

    /**
     * メッセージ
     *
     */
    public function set_message($msg_name) {
        $message = '';

        switch ($msg_name) {
            case 'PAYPAL_CANCEL':
                $message = 'PayPalの処理がキャンセルされました。';
                break;
            default:
                break;
        }

        $this->message = apply_filters('mts_booking_form_set_message', $message, $msg_name);
    }

    /**
     * エラーエレメントの出力
     *
     */
    protected function _out_errorbox() {
        ob_start();
        ?>
        <div class="error-message error-box">
            <?php echo $this->err_message ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 予約完了エレメントの出力
     *
     */
    protected function _out_completed($content) {
        $tracking = '';

        // nonceコードと予約IDが渡されたか確認する
        if (isset($_GET['nonce']) && isset($_GET['bid'])) {

            // 予約データと予約品目を取得する
            $booking = $this->get_booking(intval($_GET['bid']));
            if (isset($booking['article_id'])) {
                $article = MTSSB_Article::get_the_article($booking['article_id']);
            }

            // 有効でトラッキングが指定されていれば出力コードを生成する
            if (isset($article['addition']->tracking)) {
                if (wp_verify_nonce($_GET['nonce'], 'affiliate' . $booking['booking_id'])) {
                    // 予約IDを生成する
                    $reserve_id = apply_filters('mtssb_thanks_reserve_id', date('ymd', $booking['booking_time']) . substr("00{$booking['booking_id']}", -3));
                    // トラッキングコードに予約IDを埋め込む
                    $tracking = str_replace('%RESERVE_ID%', $reserve_id, $article['addition']->tracking);
                }
            }
        }

        // booking-thanksページ
        if (is_page(MTS_Simple_Booking::PAGE_BOOKING_THANKS)) {
            return $content . $tracking;
        }

        ob_start();
        ?>
        <div class="info-message booking-completed">
            ご予約ありがとうございました。
        </div>
        <?php
        return ob_get_clean() . $tracking;
    }

    /**
     * お客様入力フォームの表示
     *
     */
    protected function _input_form() {
        global $mts_simple_booking;

        $url = get_permalink();
        $client = $this->booking['client'];

        // 予約数の取得
        $daytime = $this->thetime / 86400 * 86400;
        $reserved = $this->get_reserved_day_count($daytime);

        // 予約総数を求める
        $remain = ($this->article['restriction'] == 'capacity' ? $this->article['capacity'] : $this->article['quantity']) + intval($this->schedule['delta']);

        // 予約残数を求める
        if (isset($reserved[$this->thetime][$this->article_id]) && 0 < $remain) {
            $reserved = $reserved[$this->thetime][$this->article_id];
            $remain -= intval($this->article['restriction'] == 'capacity' ? $reserved['number'] : $reserved['count']);
        }

        // 入退場時刻入力前処理
        if ($this->controls['message']['temps_utile']) {
            if (empty($this->inout['in'])) {
                $this->inout['in'] = new MTS_WPTime($this->thetime);
            }
            if (empty($this->inout['out'])) {
                $this->inout['out'] = new MTS_WPTime($this->thetime);
            }
        }

        // 予約情報をjQuery UIで処理できるように準備する
        wp_enqueue_script('mtssb_booking_js', $mts_simple_booking->plugin_url . self::JS_PATH, array('jquery'));

        // 郵便番号検索の指定
        $premise = get_option($this->domain . '_premise');
        $zipSearch = isset($premise['zip_search']) ? $premise['zip_search'] : 0;
        $apiKey = isset($premise['api_key']) ? $premise['api_key'] : '';
        if ($zipSearch == 1) {
            wp_enqueue_script('mts_assistance', $mts_simple_booking->plugin_url . self::JS_ASSISTANCE);
        } elseif ($zipSearch == 2) {
            wp_enqueue_script('postcodejp', self::POSTCODEJP);
            wp_enqueue_script('mts_postcodejp', $mts_simple_booking->plugin_url . self::JS_POSTCODEJP);
        }

        ob_start();
        ?>

        <div id="booking-form" class="content-form">
            <?php if (!empty($this->message) || !empty($this->err_message)) : ?>
                <div class="form-message<?php echo empty($this->err_message) ? '' : " error" ?>">
                    <?php echo $this->message . $this->err_message ?>
                </div>
            <?php endif; ?>

            <?php echo apply_filters('booking_form_before', '', array('aid' => $this->article['article_id'])); ?>
            <form method="post" action="<?php echo $url ?>">
                <fieldset id="booking-reservation-fieldset">
                    <legend><?php echo apply_filters('booking_form_number_title', '', 'input') ?></legend>
                    <?php echo apply_filters('booking_form_number_message', '') ?>
                    <table>
                        <tr>
                            <th><?php echo apply_filters('booking_form_number_reserve', '予約', 'input') ?></th>
                            <td><?php
                                echo $this->article['name'] . '<br />';
                                echo apply_filters('booking_form_date', date('Y年n月j日 H:i', $this->thetime), $this->thetime, 'input');
                                // 予約料金の表示
                                if ($this->charge['charge_list'] && $this->article['price']->booking != 0) {
                                    echo '<br />' . apply_filters('booking_form_charge_booking', '料金 ', 'input') . $this->money_format($this->article['price']->booking);
                                }
                                echo apply_filters('booking_form_catch', '', $remain, $this->article_id, 'input');
                                ?>
                            </td>
                        </tr>
                        <tr class="booking-form-people-number-row">
                            <th><label for="client-adult"><?php echo apply_filters('booking_form_people_number', '人数', 'input') ?></label></th>
                            <td>
                                <div class="display_flex_center booking-form-people-number-row_wap">
                                    <?php
                                    $prices = '';
                                    foreach ($this->controls['count'] as $key => $val) :
                                        if ($val == 1) :
                                            ?><div class="display_flex_center input-number input_number_2020"><?php
                                            $title = apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'input');
                                            // 種別表示(大人、子供)
                                            echo empty($title) ? '' : "<label class=\"client-{$key}\" for=\"client-{$key}\">$title</label>";
                                            // 料金表設定
                                            if ($this->charge['charge_list'] == 1 && 0 < $this->article['price']->$key) {
                                                $prices .= (empty($prices) ? '' : ', ') . "<span class=\"type-price\">{$title} "
                                                        . $this->money_format($this->article['price']->$key) . '</span>';
                                            }
                                            // セレクトボックスと入替フィルター
                                            $html = $this->_input_number_select($key, $client[$key]);
                                            echo apply_filters('booking_form_count_input', $html, $key, $client[$key]);
                                            ?></div><?php
                                        endif;
                                    endforeach;
                                    ?>

                                    <?php
                                    if ($this->charge['charge_list'] == 1 && !empty($prices)) {
                                        echo sprintf('<div class="unit-price">%s</div>', $prices);
                                    }
                                    ?>
        <?php if (isset($this->errmsg['count'])) : ?><div class="error-message"><?php echo $this->errmsg['count'] ?></div><?php endif; ?>
                                </div></td>
                        </tr>
                    </table>
                </fieldset>

                <?php
                // オプションの入力フォーム出力
                if ($this->article['addition']->isOption() && $this->article['addition']->position == 0) {
                    $this->_outform_option();
                }
                ?>

                <?php
                // 連絡先の入力フォーム出力
                $this->_outform_client($zipSearch, $apiKey);
                ?>

                <?php
                // オプションの入力フォーム出力
                if ($this->article['addition']->isOption() && $this->article['addition']->position == 1) {
                    $this->_outform_option();
                }
                ?>

                <fieldset id="booking-message-fieldset">
                        <?php echo apply_filters('booking_form_message_message', '') ?>
                    <table>
                                <?php if ($this->controls['message']['temps_utile']) : ?><tr>
                                <th><label for="message-temps_utile"><?php echo apply_filters('booking_form_message_inout_title', '入退場予定', 'input') ?></label></th>
                                <td>
            <?php echo apply_filters('booking_form_message_intime', '入場 ', 'input') ?><?php echo $this->inout['in']->time_form('in', 'temps_utile') ?><br />
            <?php echo apply_filters('booking_form_message_outtime', '退場 ', 'input') ?><?php echo $this->inout['out']->time_form('out', 'temps_utile') ?>
                                </td>
                            </tr><?php endif; ?>
                        <tr>
                            <th><label for="booking-note"><?php echo apply_filters('booking_form_message_header', 'ご要望・ご質問など') ?></label></th>
                            <td>
                                <textarea id="booking-note" class="content-text fat" name="booking[note]" rows="5" cols="100"><?php if(!empty($_POST['booking']['note'])): ?><?php echo $_POST['booking']['note']; ?><?php else: ?><?php echo esc_textarea($this->booking['note']) ?><?php endif; ?></textarea>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <div id="action-button" style="text-align: center">
                    <?php if(empty($_POST['action'])): ?>
                        <?php echo apply_filters('booking_form_send_button', '<button type="submit" name="reserve_action" value="validate">確認画面へ</button>'); ?>
                    <?php elseif($_POST['action']==='validate'): ?>
                        <?php echo apply_filters('booking_form_send_button', '<button type="submit" name="reserve_action" value="validate">確認画面へ</button>'); ?>
                    <?php elseif( $_POST['action']==='confirm'): ?>
                        <?php echo apply_filters('booking_form_send_button', '<button type="submit" name="reserve_action" value="validate">予約確認</button>'); ?>
                    <?php else: ?>
                        <?php echo apply_filters('booking_form_send_button', '<button type="submit" name="reserve_action" value="validate">確認画面へ</button>'); ?>
                    <?php endif; ?>
        
                </div>
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
                <input type="hidden" name="action" value="validate" />
                <input type="hidden" name="booking[article_id]" value="<?php echo $this->article_id ?>" />
                <input type="hidden" name="booking[booking_time]" value="<?php echo $this->thetime ?>" />
                <input type="hidden" name="booking[user_id]" value="<?php echo $this->booking['user_id'] ?>" />
            </form>
            <?php echo apply_filters('booking_form_after', '', array('aid' => $this->article['article_id'])); ?>
  
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 人数入力のセレクトボックス表示
     *
     * @key		adult,child,baby
     * @number	入力人数
     */
    private function _input_number_select($key, $number) {
        $minimum = apply_filters('booking_form_input_number_minimum', 0, $key);
        $maximum = apply_filters('booking_form_input_number_miximum', $this->article['maximum'], $key);

        ob_start();
        ?>
        <select id="client-<?php echo $key ?>" name="booking[client][<?php echo $key ?>]">            
            <?php
            for ($i = $minimum; $i <= $maximum; $i++) {
                if(!empty($_POST['booking']['client'][$key])){
                    $number = (int)$_POST['booking']['client'][$key];
                    echo "<option value=\"$i\"" . ($i == $number ? ' selected="selected"' : '') . ">$i</option>";
                }else {
                    echo "<option value=\"$i\"" . ($i == $number ? ' selected="selected"' : '') . ">$i</option>";
                }
            }
            ?>
        </select>

        <?php
        return ob_get_clean();
    }

    /**
     * 連絡先の入力フォーム出力
     *
     */
    private function _outform_client($zipSearch, $apiKey) {
        global $usces;

        $client = $this->booking['client'];

        // 年齢制限データの取得
        $agelimit = $this->_age_limit();

        // Welcartが有効ならWelcartのユーザー情報をセットする
        if (!empty($usces) && usces_is_membersystem_state() && usces_is_login() && apply_filters('mtssb_use_usces', true)) {
            $usces->get_current_member();
            $wc = $usces->get_member_info($usces->current_member['id']);
            $client['sei'] = $wc['mem_name1'];
            $client['mei'] = $wc['mem_name2'];
            $client['sei_kana'] = $wc['mem_name3'];
            $client['mei_kana'] = $wc['mem_name4'];
            $client['email'] = $wc['mem_email'];
            $client['postcode'] = $wc['mem_zip'];
            $client['address1'] = $wc['mem_pref'] . $wc['mem_address1'] . ' ' . $wc['mem_address2'];
            $client['address2'] = $wc['mem_address3'];
            $client['tel'] = $wc['mem_tel'];
            $this->booking['user_id'] = $wc['ID'];

            // ログイン中であればログインユーザー情報をセットする
        } elseif (is_user_logged_in() && empty($client['name']) && empty($client['email'])) {
            $current_user = wp_get_current_user();
            $client['company'] = get_the_author_meta('mtscu_company', $current_user->ID);
            $client['sei'] = $current_user->last_name;
            $client['mei'] = $current_user->first_name;
            $furigana = explode(' ', mb_convert_kana(get_the_author_meta('mtscu_furigana', $current_user->ID), 's'));
            $client['sei_kana'] = isset($furigana[0]) ? $furigana[0] : '';
            $client['mei_kana'] = isset($furigana[1]) ? $furigana[1] : '';
            $client['email'] = $current_user->user_email;
            $client['postcode'] = get_the_author_meta('mtscu_postcode', $current_user->ID);
            $client['address1'] = get_the_author_meta('mtscu_address1', $current_user->ID);
            $client['address2'] = get_the_author_meta('mtscu_address2', $current_user->ID);
            $client['tel'] = get_the_author_meta('mtscu_tel', $current_user->ID);
            $this->booking['user_id'] = $current_user->ID;
        }

        // フォーム並び順配列
        $column_order = explode(',', $this->reserve['column_order']);
        ?>
        <fieldset id="booking_client-fieldset">
            <legend><?php echo apply_filters('booking_form_client_title', 'ご連絡先', 'input') ?></legend>
                <?php echo apply_filters('booking_form_client_message', '') ?>

            <table>
                <?php
                foreach ($column_order as $column) : $column_use = $this->reserve['column'][$column];
                    if (0 < $column_use) :
                        switch ($column) :
                            case 'company' :
                                ?><tr>
                                    <th><label for="client-company"><?php echo apply_filters('booking_form_company', '会社名', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                        ?></label></th>
                                    <td>
                                        <input id="client-company" class="content-text medium" type="text" name="booking[client][company]" value="<?php echo esc_html($client['company']) ?>" maxlength="100" />
                                            <?php
                                            break;
                                        case 'name' :
                                            ?><tr>
                                    <th><label for="client-name"><?php echo apply_filters('booking_form_name', 'お名前', 'input');
                                            echo $column_use == 1 ? $this->_require_message() : ''
                                            ?></label></th>
                                    <td>
                                        <div class="display_flex_center name_contents_in_form">
                                            
                                            <label class="booking-seimei" for="booking-sei">姓</label>
                                            <input id="booking-sei" class="content-text small-medium" type="text" name="booking[client][sei]" value="<?php if(!empty($_POST['booking']['client']['sei'])){ echo $_POST['booking']['client']['sei']; } else {echo esc_attr($client['sei']);} ?>" />
                                            <label class="booking-seimei" for="booking-mei">名</label>
                                            <input id="booking-mei" class="content-text small-medium" type="text" name="booking[client][mei]" value="<?php if(!empty($_POST['booking']['client']['mei'])){ echo $_POST['booking']['client']['mei']; } else {echo esc_attr($client['mei']);} ?>" />
                                        </div>
                        <?php
                        break;
                    case 'furigana' :
                        ?><tr>
                                    <th><label for="client-furigana"><?php echo apply_filters('booking_form_furigana', 'フリガナ', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                        ?></label></th>
                                    <td>
                                        <div class="display_flex_center name_contents_in_form">
                                            <label class="booking-seimei" for="booking-sei_kana">セイ</label>
                                            <input id="booking-sei_kana" class="content-text small-medium" type="text" name="booking[client][sei_kana]" value="<?php if(!empty($_POST['booking']['client']['sei_kana'])){ echo $_POST['booking']['client']['sei_kana']; } else {echo esc_attr($client['sei']);} ?>" />
                                            <label class="booking-seimei" for="booking-mei_kana">メイ</label>
                                            <input id="booking-mei_kana" class="content-text small-medium" type="text" name="booking[client][mei_kana]" value="<?php if(!empty($_POST['booking']['client']['mei'])){ echo $_POST['booking']['client']['mei_kana']; } else {echo esc_attr($client['mei_kana']);} ?>" />
                                        </div>

                                        <?php
                                        break;
                                    case 'birthday' :
                                        ?><tr>
                                    <th><label id="client-birthday"><?php echo apply_filters('booking_form_birthday', '生年月日', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                                        ?></label></th>
                                    <td>
                                        <?php
                                        echo $client['birthday']->date_form('form_birthday', "booking[client][birthday]", 0, $agelimit['upper'], true);
                                        break;
                                    case 'gender' :
                                        ?><tr>
                                    <th><label id="client-gender"><?php echo apply_filters('booking_form_gender', '性別', 'input');
                                        echo $column_use == 1 ? $this->_require_message() : ''
                                        ?></label></th>
                                    <td><input type="hidden" name="booking[client][gender]" value="" />
                                        <label class="booking-form-radio"><input id="client-gender-female" type="radio" class="content-text radio" name="booking[client][gender]" value="female"<?php echo $client['gender'] == 'female' ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_gender_female', '女性') ?></label>
                                        <label class="booking-form-radio"><input id="client-gender-male" type="radio" class="content-text radio" name="booking[client][gender]" value="male"<?php echo $client['gender'] == 'male' ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_gender_male', '男性') ?></label>
                                            <?php
                                            break;
                                        case 'email' :
                                            ?><tr>
                                    <th><label for="client-email"><?php echo apply_filters('booking_form_email', 'E-Mail', 'input');
                                            echo $column_use == 1 ? $this->_require_message() : ''
                                            ?></label></th>
                                    <td>
                                        <input id="client-email" class="content-text fat" type="text" name="booking[client][email]" value="<?php if(!empty($_POST['booking']['client']['email'])){ echo $_POST['booking']['client']['email']; } else {echo esc_attr($client['email']);} ?>" maxlength="100" />
                                        <?php
                                        break;
                                    case 'postcode' :
                                        ?><tr>
                                    <th><label for="client-postcode"><?php echo apply_filters('booking_form_postcode', '郵便番号', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                        ?></label></th>
                                    <td>
                                        <input id="client-postcode" class="content-text medium" type="text" name="booking[client][postcode]" value="<?php echo esc_html($client['postcode']) ?>" maxlength="10" />
                                        <?php if (0 < $zipSearch) : ?>
                                            <button id="mts-postcode-button" type="button" class="button-secondary" onclick="mts_assistance.findByPostcode('<?php echo $apiKey ?>', 'client-postcode', 'client-address1')" data-api_key="<?php echo $apiKey ?>" data-postcode="client-postcode" data-address="client-address1">検索</button>
                                            <img id="mts-postcode-loading" src="<?php echo $this->iconUrl ?>" style="display:none" alt="Loading...">
                        <?php endif; ?>
                                        <?php
                                        break;
                                    case 'address' :
                                        ?><tr>
                                    <th><label for="client-address1"><?php echo apply_filters('booking_form_address', '住所', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                                        ?></label></th>
                                    <td>
                                        <input id="client-address1" class="content-text fat" type="text" name="booking[client][address1]" value="<?php echo esc_html($client['address1']) ?>" maxlength="100" /><br />
                                        <input id="client-address2" class="content-text fat" type="text" name="booking[client][address2]" value="<?php echo esc_html($client['address2']) ?>" maxlength="100" />
                                        <?php
                                        break;
                                    case 'tel' :
                                        ?><tr>
                                    <th><label for="client-tel"><?php echo apply_filters('booking_form_tel', '電話番号', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                                        ?></label></th>
                                    <td>
                                        <input id="client-tel" class="content-text medium" type="text" name="booking[client][tel]" value="<?php if(!empty($_POST['booking']['client']['tel'])){ echo $_POST['booking']['client']['tel']; } else {echo esc_attr($client['tel']);} ?>" maxlength="20" />
                                        <?php
                                        break;
                                    case 'newuse' :
                                        ?><tr>
                                    <th><label for="client-newuse-yes"><?php echo apply_filters('booking_form_newuse', '新規利用', 'input');
                        echo $column_use == 1 ? $this->_require_message() : ''
                        ?></label></th>
                                    <td>
                                        <label class="content-radio"><input id="client-newuse-yes" type="radio" name="booking[client][newuse]" value="1"<?php echo $client['newuse'] == 1 ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_newuse_yes', 'はい') ?></label>
                                        <label class="content-radio"><input id="client-newuse-no" type="radio" name="booking[client][newuse]" value="2"<?php echo $client['newuse'] == 2 ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_newuse_no', 'いいえ') ?></label>
                                <?php
                                break;
                            default :
                                break;
                        endswitch;

                        // エラーの表示
                        if (isset($this->errmsg[$column])) {
                            echo '<div class="error-message">' . $this->errmsg[$column] . '</div>';
                        }
                        ?>
                            </td>
                        </tr>
                <?php if ($column == 'email' && $column_use == 1) : $email2 = isset($this->errmsg[$column]) ? '' : $client[$column] ?><tr>
                                <th><label for="client-email2"><?php echo apply_filters('booking_form_email2', sprintf('E-Mail確認%s', $this->_require_message()), 'input') ?></label></th>
                                <td>
                                    <input id="client-email2" class="content-text fat" type="text" name="booking[client][email2]" value="<?php echo $email2 ?>" maxlength="100" />
                                </td>
                            </tr><?php
                    endif;
                endif;
            endforeach;
            ?>
            </table>
        </fieldset>

                <?php
                return;
            }

            /**
             * オプションの入力フォーム出力
             *
             */
            private function _outform_option() {
                $legend = apply_filters('booking_form_option_title', 'オプションご注文', 'input');
                $message = apply_filters('booking_form_option_message',
                        '以下は追加でご注文いただけます。ご注文の場合は数量を入力して下さい。', array('aid' => $this->article['article_id']));
                ?>
        <fieldset id="booking-option-fieldset">
            <legend><?php echo $legend ?></legend>
                <?php echo $message ?>
            <table id="booking-option-table">
                <?php
                foreach ($this->booking['options'] as $option) :
                    switch ($option->getType()) {
                        case 'number':
                        case 'text':
                            echo $this->_outform_option_text($option);
                            break;
                        case 'radio':
                            echo $this->_outform_option_radio($option);
                            break;
                        case 'select':
                            echo $this->_outform_option_select($option);
                            break;
                        case 'check':
                            echo $this->_outform_option_check($option);
                            break;
                        case 'date':
                            echo $this->_outform_option_date($option);
                            break;
                        case 'time':
                            echo $this->_outform_option_time($option);
                            break;
                        case 'textarea':
                            echo $this->_outform_option_textarea($option);
                            break;
                        default:
                            break;
                    }
                endforeach;
                ?>
            </table>
        </fieldset>

                <?php
                return;
            }

            /**
             * number, text オプション入力フォーム表示
             *
             */
            private function _outform_option_text($option) {
                // input class属性
                $class = $option->type == 'number' ? 'booking-option-number' : 'content-text';
                ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, true);
        ?>
            </th>
            <td>
                <input id="booking-option-<?php echo $option->keyname ?>" class="<?php echo $class ?>" type="text" name="booking[options][<?php echo $option->keyname ?>]" value="<?php echo esc_html($option->getValue()) ?>" />
        <?php
        $this->_out_option_note($option); // 注記
        $this->_out_option_price($option); // 単価
        $this->_out_option_err_message($option->keyname); // エラー
        ?>
            </td>
        </tr>
        <?php
        return;
    }

    private function _out_option_label_header($option, $setfor) {
        $label_for = '';

        // オプションタイトル名
        $title = $option->name . ($option->required ? $this->_require_message() : '');
        $title = apply_filters("option_label_{$option->keyname}", $title);

        // laber for 属性
        if ($setfor) {
            $label_for = ' for="booking-option-' . $option->keyname . '"';
        }

        echo "<label class=\"option-label {$option->keyname}\"{$label_for}>{$title}</label>";
    }

    /**
     * オプション項目の注記表示
     *
     * @option	オプションのオブジェクト
     */
    private function _out_option_note($option) {
        if (empty($option->note)) {
            return;
        }

        echo "<span class=\"option-note {$option->keyname}\">{$option->note}</span>";
    }

    /**
     * オプション項目の料金表示
     *
     * @option	オプションデータオブジェクト
     */
    private function _out_option_price($option) {
        // 料金表示設定されていないか金額が０円なら表示なし
        if ($this->charge['charge_list'] != 1 || !$option->isPriced()) {
            return;
        }

        // オプションに価格が設定されている場合
        if ($option->price != 0) {
            $price = apply_filters("booking_form_option_price", $this->money_format($option->price), $option->keyname);

            // オプション選択肢に価格が設定されている場合
        } else {
            $price = '';
            foreach ($option->field as $fieldname => $fielda) {
                // 金額が設定された選択肢のみ表示
                if (is_numeric($fielda['price'])) {
                    $price .= (empty($price) ? '' : ', ') . $fielda['label'] . ' ' . $this->money_format($fielda['price']);
                }
            }
            $price = apply_filters("booking_form_option_price", $price, $option->keyname);
        }

        echo "<div class=\"unit-price\">{$price}</div>";
    }

    /**
     * オプション項目のエラー表示
     *
     * @keyname	オプション項目名
     */
    private function _out_option_err_message($keyname) {
        if (isset($this->errmsg['option'][$keyname])) {
            echo '<div class="error-message">' . $this->errmsg['option'][$keyname] . '</div>';
        }
    }

    /**
     * radio オプション入力フォーム表示
     *
     */
    private function _outform_option_radio($option) {
        $val = $option->getValue();
        ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, false);
        ?>
            </th>
            <td><?php
        // ラジオボタンの表示
        foreach ($option->field as $fieldname => $fielda) :
            ?>
                    <label class="field-item check-<?php echo $option->keyname ?>">
                        <input id="option-radio-<?php echo "{$option->keyname}-{$fieldname}" ?>" type="radio" name="booking[options][<?php echo $option->keyname ?>]" value="<?php echo $fieldname ?>"<?php echo $fieldname == $val ? ' checked="checked"' : '' ?> /> <?php echo $fielda['label'] ?>
                    </label>
                    <?php endforeach; ?>
                    <?php
                    $this->_out_option_note($option); // 注記
                    $this->_out_option_price($option); // 単価
                    $this->_out_option_err_message($option->keyname); // エラー
                    ?>
            </td>
        </tr>
                <?php
                return;
            }

            /**
             * select オプション入力フォーム表示
             *
             */
            private function _outform_option_select($option) {
                $val = $option->getValue();
                ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, true);
                ?>
            </th>
            <td>
                <select id="booking-option-<?php echo $option->keyname ?>" class="booking-option-select" name="booking[options][<?php echo $option->keyname ?>]">
                <?php foreach ($option->field as $fieldname => $fielda) : ?>
                        <option value="<?php echo $fieldname ?>"<?php echo $fieldname == $val ? ' selected="selected"' : '' ?>><?php echo $fielda['label'] ?></option>
        <?php endforeach; ?></select>
        <?php
        $this->_out_option_note($option); // 注記
        $this->_out_option_price($option); // 単価
        $this->_out_option_err_message($option->keyname); // エラー
        ?>
            </td>
        </tr>
                <?php
                return;
            }

            /**
             * check オプション入力フォーム表示
             *
             */
            private function _outform_option_check($option) {
                ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, false);
        ?>
            </th>
            <td>
                <?php foreach ($option->field as $fieldname => $fielda) : ?>
                    <input type="hidden" name="booking[options]<?php echo "[$option->keyname][$fieldname]" ?>" value="0" />
                    <label class="field-item check-<?php echo $option->keyname ?>">
                        <input id="option-check-<?php echo "{$option->keyname}-{$fieldname}" ?>" type="checkbox" name="booking[options]<?php echo "[$option->keyname][$fieldname]" ?>" value="1"<?php echo $option->isChecked($fieldname) ? ' checked="checked"' : '' ?> /> <?php echo $fielda['label'] ?>
                    </label>
                    <?php
                endforeach;
                $this->_out_option_note($option); // 注記
                $this->_out_option_price($option); // 単価
                $this->_out_option_err_message($option->keyname); // エラー
                ?>
            </td>
        </tr>
        <?php
        return;
    }

    /**
     * date オプション入力フォーム表示
     *
     */
    private function _outform_option_date($option) {
        // 日付入力ボックスの表示
        $odate = new MTS_WPDate;
        ?>
        <tr>
            <th><?php
                // ラベル表示
                $this->_out_option_label_header($option, false);
                ?>
            </th>
            <td>
                <?php
                echo $odate->set_time($option->getValue())->date_form("option_{$option->keyname}", "booking[options][{$option->keyname}]");
                $this->_out_option_note($option); // 注記
                $this->_out_option_price($option); // 単価
                $this->_out_option_err_message($option->keyname); // エラー
                ?>
            </td>
        </tr>
        <?php
        return;
    }

    /**
     * time オプション入力フォーム表示
     *
     */
    private function _outform_option_time($option) {
        // 時間入力ボックス
        $otime = new MTS_WPTime($option->getValue());
        ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, false)
        ?>
            </th>
            <td>
                <?php
                echo $otime->time_form($option->keyname, "booking[options]");
                $this->_out_option_note($option);     // 注記
                $this->_out_option_price($option);     // 単価
                $this->_out_option_err_message($option->keyname); // エラー表示
                ?>
            </td>
        </tr>
        <?php
        return;
    }

    /**
     * textarea オプション入力フォーム表示
     *
     */
    private function _outform_option_textarea($option) {
        // input class属性
        $class = $option->type == 'number' ? 'booking-option-number' : 'content-text';
        ?>
        <tr>
            <th><?php
        // ラベル表示
        $this->_out_option_label_header($option, true);
        ?>
            </th>
            <td>
                <textarea id="booking-option-<?php echo $option->keyname ?>" class="content-text fat" rows="5" cols="100" name="booking[options][<?php echo $option->keyname ?>]"><?php echo esc_textarea($option->getValue()) ?></textarea>
        <?php
        $this->_out_option_note($option); // 注記
        $this->_out_option_price($option); // 単価
        $this->_out_option_err_message($option->keyname); // エラー
        ?>
            </td>
        </tr>
        <?php
        return;
    }

    /**
     * 必須入力項目マーク表示
     *
     */
    private function _require_message() {
        return '(<span class="required">※</span>)';
    }

    /**
     * 料金の表示
     *
     */
    public function money_format($amount) {
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
     * 予約入力確認フォームの表示
     *
     */
    protected function _confirming_form() {
        global $mts_simple_booking;

        $url = get_permalink();
        $client = $this->booking['client'];

        // 入力オプションデータを配列に変換し、メッセージフィルターのパラメータにセットする
        $options = MTSSB_Option::recordSet($this->booking['options']);
        $param = array(
            'aid' => $this->booking['article_id'],
            'options' => $options,
        );

        // オプション値の書き換えフィルター
        $new_options = apply_filters('mtssb_confirm_option_manage', null, $param);
        if (is_array($new_options)) {
            foreach ($new_options as $option_key => $option_val) {
                foreach ($this->booking['options'] as $option) {
                    if ($option->getKeyname() == $option_key) {
                        $option->setValue($option_val);
                    }
                }
            }
        }

        // 同意書チェックが必要なとき
        wp_enqueue_script('mtssb_booking_js', $mts_simple_booking->plugin_url . self::JS_PATH, array('jquery'));

        ob_start();
        ?>

        <div id="booking-form" class="content-form">

                        <?php echo apply_filters('booking_form_confirm_before', '', array('aid' => $this->article_id)); ?>
            <form method="post" action="<?php echo $url ?>">
                <fieldset id="booking-confirm-fieldset">
                    <legend><?php echo apply_filters('booking_form_confirm_title', '') ?></legend>
                    <table>
                        <tr>
                            <th><?php echo apply_filters('booking_form_number_reserve', '予約', 'confirm') ?></th>
                            <td><?php echo $this->article['name'] ?><br />
                        <?php echo apply_filters('booking_form_date', date_i18n('Y年n月j日 H時i分', $this->booking['booking_time']), $this->booking['booking_time'], 'confirm') ?>
                            </td>
                        </tr>
                        <tr class="booking-form-people-number-row">
                            <th><?php echo apply_filters('booking_form_people_number', '人数', 'confirm') ?></th>
                            <td>
        <?php foreach ($this->controls['count'] as $key => $val) : ?>
            <div class="input-number"<?php echo $val != 1 ? ' style="display:none"' : '' ?>>
                <?php
                $title = apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'confirm');
                if ($title != '') {
                    echo "$title<br />";
                }
                ?>
                <?php echo esc_html($client[$key]) ?>
                    <input type="hidden" name="booking[client][<?php echo $key ?>]" value="<?php echo esc_html($client[$key]) ?>" />
                        <?php echo apply_filters('booking_form_count_note', '') ?>
            </div>
        <?php endforeach; ?>
        <?php if (isset($this->errmsg['count'])) : ?><div class="error-message"><?php echo $this->errmsg['count'] ?></div><?php endif; ?>
                            </td>

                        </tr>

                <?php
                // オプション先方指定の表示
                if ($this->article['addition']->isOption() && $this->article['addition']->position == 0) {
                    $this->_outconfirm_option();
                }
                ?>

                <?php
                // 連絡先の表示
                $this->_outconfirm_client();
                ?>

        <?php
        // オプション後方指定の表示
        if ($this->article['addition']->isOption() && $this->article['addition']->position == 1) {
            $this->_outconfirm_option();
        }
        ?>


                        <tr>
                            <th><?php echo apply_filters('booking_form_message_header', 'ご要望・ご質問など') ?></th>
                            <td>
        <?php echo nl2br(esc_html($this->booking['note'])) ?>
                                <input type="hidden" name="booking[note]" value="<?php echo esc_textarea($this->booking['note']) ?>" />
                            </td>
                        </tr>
                    </table>
                </fieldset>

        <?php
        // 請求明細の表示
        if ($this->charge['charge_list'] == 1) {
            echo $this->_confirming_form_bill($this->article['addition']);
        }
        ?>

        <?php if (!empty($this->charge['terms_url'])) : ?><div id="terms-conditions">
            <?php
            echo apply_filters('booking_form_terms_conditions',
                    "ご予約に関する規約が次のページにございます。確認の上予約処理を実行して下さい。<br />")
            ?>
                        <a href="<?php echo $this->charge['terms_url'] ?>" target="_blank"><?php echo apply_filters('booking_form_terms_link_title', '予約に関する規約') ?></a>
                    </div><?php endif; ?>
        <?php if ($this->charge['accedence'] == 1) : ?><div id="accedence-box">
                        <input id="terms-accedence" type="checkbox" /> <?php echo apply_filters('booking_form_terms_accedence', '規約に同意します。') ?>
                    </div><?php endif; ?>

        <?php
        // PayPal・予約ボタンの出力
        $this->_outconfirm_button();
        ?>

                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
                <input type="hidden" name="booking[article_id]" value="<?php echo $this->article_id ?>" />
                <input type="hidden" name="booking[booking_time]" value="<?php echo $this->thetime ?>" />
                <input type="hidden" name="action" value="confirm" />
                <input type="hidden" name="booking[user_id]" value="<?php echo esc_html($this->booking['user_id']) ?>" />
            </form>
        <?php echo apply_filters('booking_form_confirm_after', '', array('aid' => $this->article_id)); ?>
            
            <?php $now = apply_filters('mtssb_booking_button_out', ($this->charge['pay_first'] <= 0), $param); ?>
            <?php if((int)$now===1): ?>

            <div class="BackContactForm">
                <form method="post" action="<?php echo $url ?>">
                    <input type="hidden" name="booking[client][sei]" value="<?php echo esc_textarea($this->booking['client']['sei']) ?>">
                    <input type="hidden" name="booking[client][mei]" value="<?php echo esc_textarea($this->booking['client']['mei']) ?>">                    
                    <input type="hidden" name="booking[client][sei_kana]" value="<?php echo esc_textarea($this->booking['client']['sei_kana']) ?>">
                    <input type="hidden" name="booking[client][mei_kana]" value="<?php echo esc_textarea($this->booking['client']['mei_kana']) ?>">                    
                    <input type="hidden" name="booking[client][tel]" value="<?php echo esc_textarea($this->booking['client']['tel']) ?>">
                    <input type="hidden" name="booking[client][email]" value="<?php echo esc_textarea($this->booking['client']['email']) ?>">
                    <input type="hidden" name="booking[client][email2]" value="<?php echo esc_textarea($this->booking['client']['email']) ?>">                    
                    <input type="hidden" name="booking[note]" value="<?php echo esc_textarea($this->booking['note']) ?>" />
                    <?php foreach ($this->controls['count'] as $key => $val) : ?>
                        <input type="hidden" name="booking[client][<?php echo $key ?>]" value="<?php echo esc_html($client[$key]) ?>" />
                    <?php endforeach; ?>
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
                    <input type="hidden" name="booking[article_id]" value="<?php echo $this->article_id ?>" />
                    <input type="hidden" name="booking[booking_time]" value="<?php echo $this->thetime ?>" />
                    <input type="hidden" name="action" value="contact" />
                    <input type="hidden" name="booking[user_id]" value="<?php echo esc_html($this->booking['user_id']) ?>" />
                    <input type="submit" value="戻る"> 
                </form>
            </div>
                
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * PayPal・予約ボタン出力
     *
     */
    private function _outconfirm_button() {
        // 入力オプションデータを配列に変換し、メッセージフィルターのパラメータにセットする
        $options = MTSSB_Option::recordSet($this->booking['options']);
        $param = array(
            'aid' => $this->booking['article_id'],
            'options' => $options,
        );

        // PayPalボタン出力の確認
        $paypal_out = false;
        if (0 < $this->charge['checkout'] && $this->bill->get_total() != 0) {
            $paypal_out = true;
            $paypal_out = apply_filters('mtssb_paypal_button_out', $paypal_out, $param);
        }

        // 予約ボタン出力の確認
        $booking_out = apply_filters('mtssb_booking_button_out', ($this->charge['pay_first'] <= 0), $param);

        // ボタン出力前のメッセージ
        $button_message = '';
        if ($paypal_out && $booking_out) {
            $button_message = '銀行振込その他でお支払いの場合は「予約する」を押してご予約下さい。';
        } elseif ($paypal_out) {
            $button_message = 'お支払いを実行していただくと予約されます。';
        } elseif (!$booking_out) {
            $button_message = '入力項目を確認して下さい。';
        }

        // ボタンメッセージのメッセージフィルター
        $button_message = apply_filters('mtssb_confirm_button_message', $button_message, $param);
        // メッセージの出力
        if (!empty($button_message)) {
            $open_div = sprintf('<div id="button-message"%s>', ($paypal_out || $booking_out) ? '' : ' class="error-message"');
            echo apply_filters('booking_form_button_message', sprintf('%s%s</div>', $open_div, $button_message));
        }
        ?>

        <div id="action-button">
        <?php
        // PayPalボタン出力
        if ($paypal_out) {
            $burl = $this->charge['checkout_button'] != '' ? $this->charge['checkout_button'] : 'https://www.paypal.com/ja_JP/i/btn/btn_xpressCheckout.gif';
            echo apply_filters('booking_form_checkout_button', sprintf(
                            '<button type="submit" name="reserve_action" value="checkout" style="border:0px; width:145px;height:42px;vertical-align:middle"><img src="%s" style="width:100%%;hight:100%%"></button>', $burl));
        }

        // 予約ボタン出力
        if ($booking_out) {
            if ($paypal_out) {
                echo apply_filters('booking_form_button_or', ' または ', $param);
            }
            echo apply_filters('booking_form_submit_button', '<button type="submit" value="validate">送信</button>');
        }

        // ボタン出力がない場合
        if (!$paypal_out && !$booking_out) {
            echo '<div class="paypal-out return-button">'
            . '<button type="button" onclick="history.back(); return false;">戻る</button>'
            . '</div>';

            // ボタンカバーの出力
        } elseif ($this->charge['accedence'] == 1) {
            echo '<div id="action-button-cover"></div>';
        }
        ?>

        </div>
        <?php
    }

    /**
     * 連絡先の確認フォーム出力
     *
     */
    private function _outconfirm_client() {
        $client = $this->booking['client'];

        // フォーム並び順配列
        $column_order = explode(',', $this->reserve['column_order']);
        ?>

                <?php
                foreach ($column_order as $column) : if (0 < $this->reserve['column'][$column]) :
                        switch ($column) :
                            case 'company' :
                                ?><tr>
                            <th><?php echo apply_filters('booking_form_company', '会社名', 'confirm') ?></th>
                            <td>
                                <?php echo esc_html($client['company']) ?>
                                <input type="hidden" name="booking[client][company]" value="<?php echo esc_html($client['company']) ?>" />
                            </td>
                        <?php
                        break;
                    case 'name' :
                        ?><tr>
                            <th><?php echo apply_filters('booking_form_name', 'お名前', 'confirm') ?></th>
                            <td>
                                <?php echo esc_html($client['name']) ?>
                                <input type="hidden" name="booking[client][sei]" value="<?php echo esc_html($client['sei']) ?>" />
                                <input type="hidden" name="booking[client][mei]" value="<?php echo esc_html($client['mei']) ?>" />
                            </td>
                            <?php
                            break;
                        case 'furigana' :
                            ?><tr>
                            <th><?php echo apply_filters('booking_form_furigana', 'フリガナ', 'confirm') ?></th>
                            <td>
                        <?php echo esc_html($client['furigana']) ?>
                                <input type="hidden" name="booking[client][sei_kana]" value="<?php echo esc_html($client['sei_kana']) ?>" />
                                <input type="hidden" name="booking[client][mei_kana]" value="<?php echo esc_html($client['mei_kana']) ?>" />
                            </td>
                            <?php
                            break;
                        case 'birthday' :
                            ?><tr>
                            <th><?php echo apply_filters('booking_form_birthday', '生年月日', 'confirm') ?></th>
                            <td>
                            <?php echo apply_filters('booking_form_birthday_date', $client['birthday']->get_date('j'), $client['birthday']->get_date()) ?>
                            <?php echo $client['birthday']->date_form_hidden('booking[client][birthday]') ?>
                            </td>
                            <?php
                            break;
                        case 'gender' :
                            ?><tr>
                            <th><?php echo apply_filters('booking_form_gender', '性別', 'confirm') ?></th>
                            <td><?php
                            $gender = empty($client['gender']) ? '' : ($client['gender'] == 'male' ? '男性' : '女性');
                            echo apply_filters('booking_form_gender_type', $gender, $client['gender'])
                            ?>
                                <input type="hidden" name="booking[client][gender]" value="<?php echo esc_html($client['gender']) ?>" />
                            </td>
                                <?php
                                break;
                            case 'email' :
                                ?><tr>
                            <th><?php echo apply_filters('booking_form_email', 'E-Mail', 'confirm') ?></th>
                            <td>
                                <?php echo esc_html($client['email']) ?>
                                <input type="hidden" name="booking[client][email]" value="<?php echo esc_html($client['email']) ?>" />
                            </td>
                                <?php
                                break;
                            case 'postcode' :
                                ?><tr>
                            <th><?php echo apply_filters('booking_form_postcode', '郵便番号', 'confirm') ?></th>
                            <td>
                            <?php echo esc_html($client['postcode']) ?>
                                <input type="hidden" name="booking[client][postcode]" value="<?php echo esc_html($client['postcode']) ?>" />
                            </td>
                            <?php
                            break;
                        case 'address' :
                            ?><tr>
                            <th><?php echo apply_filters('booking_form_address', '住所', 'confirm') ?></th>
                            <td>
                        <?php echo esc_html($client['address1']) . '<br />' . esc_html($client['address2']) ?>
                                <input type="hidden" name="booking[client][address1]" value="<?php echo esc_html($client['address1']) ?>" />
                                <input type="hidden" name="booking[client][address2]" value="<?php echo esc_html($client['address2']) ?>" />
                            </td>
                        <?php
                        break;
                    case 'tel' :
                        ?><tr>
                            <th><?php echo apply_filters('booking_form_tel', '電話番号', 'confirm') ?></th>
                            <td>
                        <?php echo esc_html($client['tel']) ?>
                                <input type="hidden" name="booking[client][tel]" value="<?php echo esc_html($client['tel']) ?>" />
                            </td>
                        <?php
                        break;
                    case 'newuse' :
                        ?><tr>
                            <th><?php echo apply_filters('booking_form_newuse', '新規利用', 'confirm') ?></th>
                            <td><?php
                        switch ($client['newuse']) {
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
                        echo $newuse_val;
                        ?>
                                <input type="hidden" name="booking[client][newuse]" value="<?php echo $client['newuse'] ?>" />
                            </td>
                                <?php
                                break;
                            default :
                                break;
                        endswitch;
                        ?></tr>

                        <?php
                    endif;
                endforeach;

                return;
            }

            /**
             * オプションの確認フォーム出力
             *
             */
            private function _outconfirm_option() {
                ?>
        <tr>
            <td class="option-confirm-header" colspan="2"><?php echo apply_filters('booking_form_option_title', 'オプション注文', 'confirm') ?></td>
        </tr>
        <?php foreach ($this->booking['options'] as $option) : ?><tr id="confirmation-<?php echo $option->keyname ?>">
                <th class="option-confirm-label"><?php echo apply_filters("option_confirm_label", $option->getLabel(), array('name' => $option->keyname)) ?></th>
                <td class="option-confirm-value">
            <?php echo apply_filters("option_confirm_text", nl2br(esc_html($option->getText())), array('name' => $option->keyname)); ?>
                    <span class="option-confirm-note"> <?php echo apply_filters("option_confirm_note", $option->getNote(), array('name' => $option->keyname)) ?></span>

            <?php
            switch ($option->getType()) :
                case 'number':
                case 'text' :
                case 'radio':
                case 'select':
                case 'textarea':
                    ?>
                            <input type="hidden" name="booking[options][<?php echo $option->keyname ?>]" value="<?php echo esc_html($option->getValue()) ?>" />
                                        <?php
                                        break;
                                    case 'check':
                                        foreach ($option->field as $fieldname => $fieldlabel) :
                                            ?>
                                <input type="hidden" name="booking[options][<?php echo $option->keyname ?>][<?php echo $fieldname ?>]" value="<?php echo $option->isChecked($fieldname) ? '1' : '0' ?>" />
                                            <?php
                                        endforeach;
                                        break;
                                    case 'date':
                                        $odate = new MTS_WPDate;
                                        echo $odate->set_time($option->getValue())->date_form_hidden("booking[options][{$option->keyname}]");
                                        break;
                                    case 'time':
                                        $otime = new MTS_WPTime($option->getValue());
                                        echo $otime->time_form_hidden("booking[options][{$option->keyname}]");
                                        break;
                                    default:
                                        break;
                                endswitch;
                                ?></td>
            </tr><?php endforeach; ?>
        <?php
        return;
    }

    /**
     * 請求明細の表示
     *
     * @opflag		オプションの有無
     */
    private function _confirming_form_bill($opflag) {
        $bill = $this->make_bill();

        ob_start();
        ?>
        <fieldset id="booking-confirm-bill">
            <legend><?php echo apply_filters('booking_form_bill_title', 'ご請求', 'confirm_bill') ?></legend>
            <table>
                <tr>
                    <th class="bill-th">明細</th>
                    <td class="bill-td">
                        <table class="bill-details">
        <?php
        // 予約料金の表示
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
            </table>
        </fieldset>

        <?php
        return ob_get_clean();
    }

    /**
     * 請求の明細表示
     *
     */
    private function _outconfirm_bill_row($title, $number, $unit) { //, $cost)
        ?>
        <tr>
            <td class="bill-title"><?php echo $title ?></td>
            <td class="bill-number"><?php echo $number ?></td>
            <td class="bill-unit"><?php echo $this->money_format($unit) ?></td>
            <td class="bill-cost"><?php echo $this->money_format($number * $unit) ?></td>
        </tr><?php
    }

    /**
     * 対象予約品目の参照を戻す
     *
     */
    public function getArticle() {
        return $this->article;
    }

    /**
     * 各種条件設定パラメータの参照を戻す
     *
     */
    public function getControls() {
        return $this->controls;
    }

    /**
     * 各種設定料金情報を戻す
     *
     */
    public function getCharge() {
        return $this->charge;
    }

}
