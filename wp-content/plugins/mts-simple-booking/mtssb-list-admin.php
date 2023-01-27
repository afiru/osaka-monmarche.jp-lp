<?php
if (!class_exists('MTSSB_Booking')) {
    require_once(dirname(__FILE__) . '/mtssb-booking.php');
    require_once(dirname(__FILE__) . '/mtssb-mail-template.php');
}

/**
 * MTS Simple Booking Booking 予約登録・編集モジュール
 *
 * @Filename	mtssb-booking-admin.php
 * @Date		2012-04-30
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-04-28
 * Updated to 1.27.0 on 2017-08-04
 * Updated to 1.22.0 on 2015-06-30
 * Updated to 1.21.0 on 2015-01-05
 * Updated to 1.17.1 on 2014-09-05,2014-10-06
 * Updated to 1.17.0 on 2014-07-12
 * Updated to 1.15.0 on 2014-01-29
 * Updated to 1.14.0 on 2014-01-15
 * Updated to 1.12.0 on 2013-11-20
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.3.0.1 on 2013-02-21
 * Updated to 1.3.0 on 2013-01-23
 * Updated to 1.1.0 on 2012-10-11
 */
class MTSSB_List_Admin extends MTSSB_Booking {

    const PAGE_NAME = 'simple-booking-list';
    const START_YEAR = '2012';
    const DOWNLOAD_NAME = 'booking_list.csv';

    private static $iList = null;
    // リストテーブルオブジェクト
    private $blist = null;
    // 読み込んだ予約品目データ
    private $article_id;
    private $articles = null;  // 予約品目
    private $conditions = '';  // 検索条件
    // 操作対象データ
    private $wpdate = null;     // 指定予約年月日
    private $action = '';  // none or montly
    private $message = '';
    private $errflg = false;

    /**
     * インスタンス化
     *
     */
    static function get_instance() {
        if (!isset(self::$iList)) {
            self::$iList = new MTSSB_List_Admin();
        }

        return self::$iList;
    }

    public function __construct() {
        global $mts_simple_booking;

        parent::__construct();

        // action指定
        if (isset($_GET['action'])) {
            $this->action = $_GET['action'];
        }

        // 予約リストのCSVダウンロード
        if ($this->action == 'download') {
            $this->_download_list();
        }

        // CSSロード
        $mts_simple_booking->enqueue_style();
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Javascriptロード
        wp_enqueue_script("mtssb_list_admin_js", $this->plugin_url . "js/mtssb-list-admin.js", array('jquery', 'jquery-ui-dialog'));
    }

    /**
     * 管理画面メニュー処理
     *
     */
    public function list_page() {
        $this->errflg = false;
        $this->message = '';
        $this->wpdate = new MTS_WPDate;

        switch ($this->action) {
            case 'select' :
                $this->_input_check($_REQUEST);
                break;
            case 'download' :
                echo 'kita download link';
                return;
            case 'delete' :
                // NONCEチェックOKなら削除する
                if (wp_verify_nonce($_GET['nonce'], self::PAGE_NAME . "_{$this->action}")) {
                    if ($this->del_booking($_GET['booking_id'])) {
                        $this->message = sprintf(__('Booking ID:%d was deleted.', $this->domain), $_GET['booking_id']);
                    } else {
                        $this->message = __('Deleting the booking data was failed.', $this->domain);
                        $this->errflg = true;
                    }
                } else {
                    $this->message = 'Nonce check error.';
                    $this->errflg = true;
                }
                // ページネーションのリンクにdeleteが残るのでURLをクリアする
                $_SERVER['REQUEST_URI'] = remove_query_arg(array('booking_id', 'action', 'nonce'));
                break;
            default:
                break;
        }

        // リスト表示
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }

        $this->blist = new MTSSB_Booking_List();
        $this->blist->prepare_items($this);
        ?>
        <div class="wrap">

            <div id="icon-edit" class="icon32"><br /></div>
            <h2><?php _e('Booking List', $this->domain) ?></h2>

        <?php if (!empty($this->message)) : ?><div class="<?php echo $this->errflg ? 'error' : 'updated' ?>">
                    <p><?php echo $this->message ?></p>
                </div><?php endif; ?>

            <div id="list-condition">
        <?php $this->_select_form() ?>
            </div>
            <div id="booking-list">
                <form id="movies-filter" method="post">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>">
                    <input type="hidden" id="nonce_ajax" value="<?php echo wp_create_nonce(strtolower(get_class())) ?>">
                <?php $this->blist->display() ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 入力予約品目、日付の設定
     *
     */
    private function _input_check($params) {
        $this->wpdate = new MTS_WPDate;

        // 予約品目の指定
        if (!empty($params['aid'])) {
            $this->article_id = intval($params['aid']);
        }

        // 年月日の指定
        if (!empty($params['select']['year'])) {
            $this->wpdate->year = intval($params['select']['year']);
            // 月の指定
            if (!empty($params['select']['month'])) {
                $this->wpdate->month = intval($params['select']['month']);
                // 日の指定
                if (!empty($params['select']['day'])) {
                    $this->wpdate->day = intval($params['select']['day']);
                }
            }
        }
    }

    /**
     * スケジュール指定フォームの出力
     */
    private function _select_form() {
        // 全予約品目を取得する
        if (empty($this->articles)) {
            $this->articles = MTSSB_Article::get_all_articles();
        }

        // 選択年月の上限・下限を求める
        $controls = get_option($this->domain . '_controls');
        $this_year = date_i18n('Y');
        $upyear = date_i18n('Y', mktime(0, 0, 0, date_i18n('n') + $controls['period'], 1, $this_year)) - $this_year;
        $downyear = $this_year - self::START_YEAR + 1;

        // ソート指定
        $order = $this->set_order_key($_REQUEST);
        ?>
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo self::PAGE_NAME ?>" />

            <div id="list-condition-select">
        <?php _e('Article:', $this->domain) ?>
                <select id="select_aid" class="select-article" name="aid">
                    <option value=""> </option>
        <?php
        foreach ($this->articles as $article_id => $article) {
            echo "<option value=\"$article_id\"";
            if ($article_id == $this->article_id) {
                echo ' selected="selected"';
            }
            echo ">{$article['name']}</option>\n";
        }
        ?>
                </select>
                <button class="button-secondary" name="action" value="select"><?php _e('Select Article', $this->domain) ?></button>

                    <?php echo $this->wpdate->date_form('select', 'select', $upyear, $downyear) ?>
                <button class="button-secondary" name="action" value="select"><?php _e('Change Date', $this->domain) ?></button>
            </div>

            <div id="list-condition-out">
                <button class="button-secondary" name="action" value="download"><?php _e('List Download', $this->domain) ?></button>
            </div>
            <div class="clear"></div>

            <input id="list_key" type="hidden" name="orderby" value="<?php echo $order['key'] ?>">
            <input id="list_direction" type="hidden" name="order" value="<?php echo $order['direction'] ?>">
        </form>

        <?php
        // 前月・翌月リンクの表示
        if (0 < $this->wpdate->month && $this->wpdate->day <= 0) {
            $this->_month_link();
        }
    }

    /**
     * 前月・翌月リンク出力
     *
     */
    private function _month_link() {
        // 前月・翌月 Unix Time
        $prevtime = mktime(0, 0, 0, $this->wpdate->month - 1, 1, $this->wpdate->year);
        $nexttime = mktime(0, 0, 0, $this->wpdate->month + 1, 1, $this->wpdate->year);

        // 予約品目パラメータ
        $aid = empty($this->article_id) ? '' : "&amp;aid={$this->article_id}";
        ?>
        <div id="list-month-link">
            <ul class="subsubsub">
                <li><?php echo sprintf('<a href="?page=%s%s&amp;action=%s&amp;select[year]=%s&amp;select[month]=%s">%s</a>',
                self::PAGE_NAME, $aid, 'select', date_i18n('Y', $prevtime), date_i18n('n', $prevtime), date_i18n('Y-n', $prevtime))
        ?> | </li>
                <li><?php echo $this->wpdate->year . '-' . $this->wpdate->month ?> | </li>
                <li><?php echo sprintf('<a href="?page=%s%s&amp;action=%s&amp;select[year]=%s&amp;select[month]=%s">%s</a>',
                self::PAGE_NAME, $aid, 'select', date_i18n('Y', $nexttime), date_i18n('n', $nexttime), date_i18n('Y-n', $nexttime))
        ?></li>
            </ul>
            <div class="clear"> </div>
        </div>
                    <?php
                }

                /**
                 * リストオブジェクトからのデータ取得コール関数
                 *
                 */
                public function read_data($offset, $limit, $order) {
                    // 期間指定条件の追加
                    if (0 < $this->wpdate->year) {
                        // 読み込み開始予約タイム
                        $start_time = $this->wpdate->get_time();
                        // 読み込み終了タイム
                        if (0 < $this->wpdate->day) {
                            $end_time = $start_time + 86400;
                        } elseif (0 < $this->wpdate->month) {
                            $end_time = mktime(0, 0, 0, $this->wpdate->month + 1, 1, $this->wpdate->year);
                        } else {
                            $end_time = mktime(0, 0, 0, 1, 1, $this->wpdate->year + 1);
                        }
                        // 条件設定
                        $this->conditions = sprintf('booking_time>=%d AND booking_time<%d', $start_time, $end_time);
                    }

                    // 予約品目の指定
                    if (!empty($this->article_id)) {
                        $this->conditions .= ($this->conditions ? ' AND ' : '') . "article_id=$this->article_id";
                    }

                    $booking_data = $this->get_booking_list($offset, $limit, $order, $this->conditions);
                    $booking_ids = array_keys($booking_data);
                    $series_number = $this->get_booking_series_count($booking_ids);
                    foreach ($booking_data as $booking_id => &$booking) {
                        $booking['series'] = isset($series_number[$booking_id]) ? $series_number[$booking_id] : 0;
                    }

                    return $booking_data;
                }

                /**
                 * カラムソート指定をキー配列にする
                 *
                 */
                public function set_order_key($params) {
                    $columns = array('booking_id', 'booking_time');
                    $order = array('key' => 'booking_id', 'direction' => 'desc');

                    if (isset($params['orderby']) && in_array($params['orderby'], $columns)) {
                        $order['key'] = $params['orderby'];
                    }

                    if (isset($params['order'])) {
                        $order['direction'] = $params['order'] == 'asc' ? 'asc' : 'desc';
                    }

                    return $order;
                }

                /**
                 * リストオブジェクトからのレコード件数取得コール関数
                 * (read_data()に実行により検索条件が決まってからコールする)
                 */
                public function list_count() {
                    return $this->get_booking_count($this->conditions);
                }

                /**
                 * 予約リスからのAJAX通信処理
                 *
                 */
                public function ajax_dispatcher() {
                    $ret = array('result' => 'false', 'message' => 'mtssb-list-admin ajax_dispatcher error.');

                    $method = $_POST['method'] ? $_POST['method'] : '';
                    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

                    switch ($method) {
                        case 'confirm' :    // 予約確認表示
                            $ret = $this->_confirm_booking($booking_id);
                            break;
                        case 'check' :      // 予約確認済みマーキング
                            $ret = $this->_check_confirm($booking_id);
                            break;
                        case 'send' :       // 予約確認完了メール送信
                            $ret = $this->_confirmed_mail($booking_id);
                            break;
                    }

                    return $ret;
                }

                /**
                 * 予約リストのCSVダウンロード
                 *
                 */
                protected function _download_list() {
                    // 検索条件
                    $this->_input_check($_REQUEST);

                    // データ読み込み
                    $data = $this->read_data(0, 1000, $this->set_order_key($_REQUEST));

                    $csv = $this->_exchange_csv($data);

                    $sjis_csv = $this->_str2msstr($csv);

                    $fname = apply_filters('mtssb_download_list_name', self::DOWNLOAD_NAME);

                    header("Content-Type: text/plane; charset=Shift_JIS"); //Shift_JIS application/octet-stream");
                    header("Content-Disposition: attachment; filename={$fname}");
                    header("Content-Length: " . strlen($sjis_csv));
                    echo $sjis_csv;
                    exit();
                }

                /**
                 * 予約データをCSVデータに変換する
                 *
                 */
                protected function _exchange_csv($bookings) {
                    $list_order = apply_filters('mtssb_download_list_order', array(
                        'reserve_id' => '予約ID',
                        'booking_time' => '予約日時',
                        'client.name' => '予約者',
                        'client.tel' => '電話番号',
                        'client.email' => 'E-Mail',
                    ));

                    $csv = $this->_csv_header($list_order);

                    foreach ($bookings as $booking) {
                        $csv .= $this->_csv_row($list_order, $booking);
                    }

                    return $csv;
                }

                private function _csv_header($list_order) {
                    $csv = '';

                    foreach ($list_order as $text) {
                        $csv .= (empty($csv) ? '' : ',') . $this->_csv_value($text);
                    }

                    return $csv . "\n";
                }

                private function _csv_row($list_order, $booking) {
                    $csv = '';

                    foreach ($list_order as $index => $header) {
                        $csv .= empty($csv) ? '' : ',';

                        $item = explode('.', $index);
                        if ($item[0] == 'client') {
                            if (!isset($booking['client'][$item[1]]) && in_array($item[1], array('sei', 'mei', 'sei_kana', 'mei_kana'))) {
                                $this->_set_seimei($booking['client']);
                            }
                            $csv .= $this->_csv_value($booking['client'][$item[1]]);
                        } elseif ($item[0] == 'options') {
                            $csv .= $this->_csv_value($booking['options'][$item[1]]);
                        } else {
                            if ($item[0] == 'reserve_id') {
                                $csv .= $this->_csv_value(date_i18n('ymd', $booking['booking_time']) . substr("00{$booking['booking_id']}", -3));
                            } elseif ($item[0] == 'booking_time') {
                                $csv .= $this->_csv_value(
                                        apply_filters('mtssb_download_list_booking_time', date_i18n('Y-m-d H:i', $booking['booking_time']), $booking['booking_time'])
                                );
                            } else {
                                $csv .= $this->_csv_value($booking[$item[0]]);
                            }
                        }
                    }

                    return $csv . "\n";
                }

                // 名前データから姓名データを設定する
                private function _set_seimei(&$client) {
                    $name = explode(' ', mb_convert_kana($client['name'], 's'));
                    $client['sei'] = empty($name[0]) ? '' : $name[0];
                    $client['mei'] = empty($name[1]) ? '' : $name[1];

                    $kana = explode(' ', mb_convert_kana($client['furigana'], 's'));
                    $client['sei_kana'] = empty($kana[0]) ? '' : $kana[0];
                    $client['mei_kana'] = empty($kana[1]) ? '' : $kana[1];
                }

                /**
                 * 数値、文字列のcsv値を戻す
                 *
                 */
                private function _csv_value($var = "") {
                    if (is_numeric($var)) {
                        return $var;
                    }
                    return sprintf('"%s"', $var);
                }

                /**
                 * SJIS,CRLF変換
                 *
                 */
                private function _str2msstr($str) {
                    $lf2crlf = str_replace("\n", "\r\n", $str);
                    $sjis = mb_convert_encoding($lf2crlf, 'sjis-win', 'utf-8');
                    return $sjis;
                }

                /**
                 * 予約リストからAJAXで予約確認処理実行
                 *
                 */
                protected function _confirm_booking($booking_id = 0) {
                    // 予約データを取得する
                    $booking = $this->get_booking($booking_id);
                    if (!$booking) {
                        return array('result' => false, 'message' => "{$booking_id} doesn't exist.");
                    }
                    $this->setBooking($booking);

                    // 予約品目の取得
                    $article = MTSSB_Article::get_the_article($booking['article_id']);

                    return array(
                        'result' => true,
                        'booking_id' => $booking_id,
                        'content' => $this->_out_booking($booking, $article),
                        'mailform' => $this->_out_mailform($booking, $article),
                        'tickimg' => '<img src="' . plugins_url('image/system-tick.png', __FILE__) . '">',
                    );
                }

                /**
                 * 予約リストからAJAXで予約確認済みセット
                 *
                 */
                protected function _check_confirm($booking_id = 0) {
                    $result = array(
                        'result' => true,
                        'booking_id' => $booking_id,
                        'message' => '',
                    );

                    $ret = $this->setConfirmed($booking_id);

                    if (!$ret) {
                        $result['result'] = false;
                        $result['message'] = "Failed to set confirm in {$booking_id}.";
                    }

                    return $result;
                }

                /**
                 * 予約リストからAJAXで予約確認完了メールを送信する
                 *
                 */
                protected function _confirmed_mail($booking_id = 0) {
                    global $mts_simple_booking;

                    // 予約確認済みをマークする
                    $check = $this->_check_confirm($booking_id);

                    if (!$check['result']) {
                        return $check;
                    }

                    // 予約データを取得してオブジェクトにセットする
                    $booking = $this->get_booking($booking_id);
                    $this->setBooking($booking);

                    // メール送信前フィルター
                    $param = apply_filters('mtssb_mail_exchange', array(
                        'state' => 'confirm',
                        'aid' => $booking['article_id'],
                        'to' => $booking['client']['email'],
                        'subject' => $_POST['mail']['subject'],
                        'body' => $_POST['mail']['body'],
                        'from' => '',
                        'header' => array(),
                    ));

                    // メールの送信
                    $mts_simple_booking->_load_module('MTSSB_Mail');
                    $ret = $mts_simple_booking->oMail->templateMail($param['to'], $param['subject'], $param['body'], $param['from'], $param['header']);
                    //$ret = $mts_simple_booking->oMail->confirmed_mail($_POST['mail']['subject'], $_POST['mail']['body']);

                    $result = array('result' => true, 'message' => '');
                    if (!$ret) {
                        $result['result'] = false;
                        $result['message'] = 'Failed to send the confirmed mail.';
                    }

                    return $result;
                }

                /**
                 * 予約完了メールフォーム出力データ
                 *
                 */
                private function _out_mailform($booking, $article) {
                    global $mts_simple_booking;

                    // テンプレート番号取得、設定されていなければNULLを戻す
                    $tno = $article['addition']->template;
                    if (!$article['addition']->template) {
                        return null;
                    }

                    // メールオブジェクト
                    $oMail = $mts_simple_booking->_load_module('MTSSB_Mail');
                    $vars = $oMail->setTempVar($article, $booking);

                    // テンプレートを取得する
                    $oTemplate = $mts_simple_booking->_load_module('MTSSB_Mail_Template');
                    $oTemplate->get_mail_template($tno);

                    // テンプレートの変数置換
                    $subject = $oMail->replaceVariable($oTemplate->mail_subject, $vars);
                    $body = $oMail->replaceVariable($oTemplate->mail_body, $vars);

                    ob_start();
                    ?>
        <div class="mtssb-dialog-inner">
            <form id="check-mail-form">
                <div class="mail-infield">
                    <label for="check-mail-subject">件名：</label>
                    <input id="check-mail-subject" type="text" name="mail_subject" value="<?php echo $subject ?>">
                </div>
                <div class="mail-infield">
                    <label for="check-mail-body">内容：</label>
                    <textarea id="check-mail-body" name="mail_body" rows="10"><?php echo $body ?></textarea>
                </div>
                <input type="hidden" id="check-mail-booking-id" name="booking_id" value="<?php echo $booking['booking_id'] ?>">
            </form>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * 予約情報の表示出力データ
     *
     */
    private function _out_booking($booking, $article) {
        // 予約条件、支払データのロード
        $controls = get_option($this->domain . '_controls');
        $charge = get_option($this->domain . '_charge');

        ob_start();
        ?>
        <div class="mtssb-dialog-inner">
            <table class="mtssb-booking-data">
                <tr>
                    <th>予約名称</th><td><?php echo $article['name'] ?></td>
                </tr>
                <tr>
                    <th>予約日時</th><td><?php echo date_i18n('Y年m月d日 H:i', $booking['booking_time']) ?></td>
                </tr>
                <tr>
                    <th>予約人数</th>
                    <td><?php
        echo sprintf('%d人 (', $booking['number']);
        foreach ($controls['count'] as $key => $val) {
            if ($val) {
                echo sprintf("%s:%d ", apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'input'), $booking['client'][$key]);
            }
        }
        echo ')';
        ?></td>
                </tr>
        <?php if ($article['addition']->option) : ?><tr>
                        <th>オプション</th>
                        <td><?php
            foreach ($booking['options'] as $option) {
                echo $option->getLabel() . ' : ';
                echo $option->type == 'textarea' ? nl2br(esc_textarea($option->getText())) : esc_html($option->getText());
                echo '<br>';
            }
            ?></td>
                    </tr><?php endif; ?>

        <?php
        // 予約者連絡先の出力
        $this->_out_client($booking['client']);
        ?>

                <tr>
                    <th>メッセージ</th><td><?php echo nl2br(esc_html($booking['note'])) ?></td>
                </tr>

                        <?php
                        // 料金明細の出力
                        if ($charge['charge_list']) {
                            $this->_out_bill($charge);
                        }

                        // PayPal支払いのトランザクションID
                        if (isset($booking['client']['transaction_id']) && $booking['client']['transaction_id']) {
                            echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                    'PayPal', $booking['client']['transaction_id']
                            );
                        }
                        ?>

                <tr>
                    <th>ブラウザ</th><td><?php echo esc_html($booking['client']['user_agent']) ?></td>
                </tr>
                <tr>
                    <th>IPアドレス</th><td><?php echo esc_html($booking['client']['remote_addr']) ?></td>
                </tr>
            </table>
        </div>

                <?php
                return ob_get_clean();
            }

            /**
             * 予約者連絡先の出力
             *
             */
            private function _out_client($client) {
                // 顧客データのカラム利用設定情報を読込む
                $reserve = get_option($this->domain . '_reserve');
                $column_order = explode(',', $reserve['column_order']);

                foreach ($column_order as $column) {
                    if ($reserve['column'][$column]) {
                        switch ($column) {
                            case 'company' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        apply_filters('booking_form_company', '会社名', 'input'), esc_html($client['company']));
                                break;
                            case 'name' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        apply_filters('booking_form_name', '名前', 'input'), esc_html($client['name']));
                                break;
                            case 'furigana' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        apply_filters('booking_form_furigana', 'フリガナ', 'input'), esc_html($client['furigana']));
                                break;
                            case 'birthday' :
                                if ($client['birthday']->isSetDate()) {
                                    echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                            '生年月日', $client['birthday']->get_date('j'));
                                }
                                break;
                            case 'gender' :
                                $gender = '';
                                if ($client['gender'] == 'male') {
                                    $gender = '男性';
                                } elseif ($client['gender'] == 'female') {
                                    $gender = '女性';
                                }
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n", '性別', $gender);
                                break;
                            case 'email' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        'E-Mail', esc_html($client['email']));
                                break;
                            case 'postcode' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        '〒', esc_html($client['postcode']));
                                break;
                            case 'address' :
                                echo sprintf("<tr><th>%s</th><td>%s<br>%s</td></tr>\n",
                                        '住所', esc_html($client['address1']), esc_html($client['address2']));
                                break;
                            case 'tel' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        'TEL', esc_html($client['tel']));
                                break;
                            case 'newuse' :
                                echo sprintf("<tr><th>%s</th><td>%s</td></tr>\n",
                                        apply_filters('booking_form_newuse', '新規利用', 'admin'),
                                        ($client['newuse'] ? apply_filters('booking_form_newuse_yes', 'はい') : apply_filters('booking_form_newuse_no', 'いいえ')));
                                break;
                        }
                    }
                }
            }

            /**
             * 料金明細の表示
             *
             */
            private function _out_bill($charge) {
                $bill = $this->make_bill();
                $currency = ' ' . ($bill->currency_code == 'JPY' ? __('Yen', $this->domain) : __('US dollar', $this->domain));
                ?>
        <tr>
            <th>料金明細</th>
            <td>
                <table>
                    <tr>
                        <th class="bill-th">明細</th>
                        <td class="bill-td">
                            <table class="bill-details">
        <?php
        // 予約料金の表示
        if ($bill->basic_charge) {
            $this->_out_bill_row($bill->article_name . ' 料金', 1, $bill->basic_charge, $currency);
        }
        // 人数料金の表示
        foreach (array('adult', 'child', 'baby') as $type) {
            if ($bill->number->$type != 0) {
                $this->_out_bill_row($bill->article_name . __(ucwords($type), $this->domain),
                        $bill->number->$type, $bill->amount->$type, $currency
                );
            }
        }
        // オプション料金の表示
        foreach ($bill->option_items as $item) {
            $this->_out_bill_row($item['name'], $item['number'], $item['price'], $currency);
        }
        ?>
                            </table>
                        </td>
                    </tr>
                                <?php if ($charge['tax_notation']) : ?><tr>
                            <th class="bill-th">合計</th>
                            <td class="bill-td"><div class="bill-total"><?php echo number_format($bill->get_total()) . $currency ?></div></td>
                        </tr>
                        <tr>
                            <th class="bill-th"><?php echo $charge['tax_notation'] == 1 ? '内' : '' ?>消費税(<?php echo $bill->tax ?>%)</th>
                            <td class="bill-td"><div class="bill-tax"><?php echo number_format($bill->get_amount_tax($charge['tax_notation'] == 1)) . $currency ?></div></td>
                        </tr>
            </tr><?php endif; ?>
        <tr>
            <th class="bill-th">総合計</th>
            <td class="bill-td"><div class="bill-total"><?php echo number_format($bill->get_total() + ($charge['tax_notation'] == 1 ? 0 : $bill->get_amount_tax())) . $currency ?></div></td>
        </tr>
        </table>
        </td>
        </tr>

        <?php
    }

    /*
     * 請求の明細表示
     *
     */

    private function _out_bill_row($title, $number, $unit, $currency) {
        ?>
        <tr>
            <td class="bill-title"><?php echo $title ?></td>
            <td class="bill-number"><?php echo $number ?></td>
            <td class="bill-unit"><?php echo sprintf("%s %s", number_format($unit), $currency) ?></td>
            <td class="bill-cost"><?php echo sprintf("%s %s", number_format($number * $unit), $currency) ?></td>
        </tr>
        <?php
    }

}

/**
 * 予約一覧
 *
 * Updated to 1.14.0 on 2014-01-15
 */
class MTSSB_Booking_List extends WP_List_Table {

    const PAGE_NAME = 'simple-booking-list';

    private $domain = '';
    private $per_page = 20;

    /**
     * Constructor
     *
     */
    public function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'booking',
            'plural' => 'bookings',
            'ajax' => false
        ));

        $this->domain = MTS_Simple_Booking::DOMAIN;
    }

    /**
     * リストカラム情報
     *
     */
    public function get_columns() {
        return array(
            'booking_id' => __('ID', $this->domain),
            'booking_time' => __('Booking Date', $this->domain),
            'name' => __('Name'),
            'tel' => __('TEL', $this->domain),
            //'number' => __('Number', $this->domain),
            'article_id' => __('Article Name', $this->domain),
            //'series' => __('Series Times', $this->domain),
            //'paid' => __('Paid', $this->domain),
            'confirmed' => __('Confirmed', $this->domain),
            'created' => __('Date'),
        );
    }

    /**
     * ソートカラム情報
     *
     */
    public function get_sortable_columns() {
        return array(
            'booking_id' => array('id', false),
            'booking_time' => array('booking_time', true),
            'name' => array('name', true),
            'article_id' => array('article_id', true),
            'created' => array('created', true),
        );
    }

    /**
     * カラムデータのデフォルト表示
     *
     */
    public function column_default($item, $column_name) {

        switch ($column_name) {
            case 'booking_id' :
                return sprintf('%s<br>%s', apply_filters('mtssb_thanks_reserve_id', date('ymd', $item['booking_time']) . substr("00{$item['booking_id']}", -3)), $item[$column_name]);
            case 'confirmed' :
                $out = '';
                if ((int) $item[$column_name] & 1) {
                    $out = sprintf('<img src="%s" alt="Not Confirme">', plugins_url('image/system-tick.png', __FILE__));
                } else {
                    $out = sprintf('<a href="javascript:void(0)" onclick="mtssb_list_op.confirm(this, %d)"><img src="%s"></a>',
                            $item['booking_id'], plugins_url('image/system-stop.png', __FILE__));
                }
                if ((int) $item[$column_name] & 2) {
                    $out .= sprintf('<img src="%s" alt="Notification Mail">', plugins_url('image/mail_trans.png', __FILE__));
                }
                return $out;
            case 'paid' :
                if (empty($item['client']['transaction_id'])) {
                    return '';
                }
                return '<img src="' . plugins_url('image/system-tick.png', __FILE__) . '" />';
            case 'article_id' :
                return $item['article_name'];
            case 'number' :
            case 'series' :
                return $item[$column_name];
            case 'created' :
                return substr($item[$column_name], 0, 10) . '<br />' . substr($item[$column_name], -8);
            case 'name' :
                return (empty($item['client']['company']) ? '' : (esc_html($item['client']['company']) . '<br />')) . esc_html($item['client']['name']);
            case 'booking_time' :
            	return date('Y-m-d',$item['booking_time']).'';
            case 'tel' :
                return $item['client']['tel'].'';
            default :
                return print_r($item, true);
        }
    }

    /**
     * カラムデータ booking_time とアクションリンク表示
     *
     */
    public function column_booking_time($item) {

        // アクション
        $actions = array(
            'view' => sprintf('<a href="?page=simple-booking&amp;bid=%d">%s</a>', $item['booking_id'], __('View')),
            'edit' => sprintf('<a href="?page=simple-booking-booking&amp;booking_id=%d&amp;action=edit">%s</a>', $item['booking_id'], __('Edit')),
            'delete' => sprintf('<a href="?page=simple-booking-list&amp;booking_id=%d&amp;action=delete&amp;nonce=%s" onclick="return confirm(\'%s\')">%s</a>', $item['booking_id'], wp_create_nonce(self::PAGE_NAME . '_delete'), __('Do you really want to delete this booking?', $this->domain), __('Delete')),
        );

        //return esc_html($item['client']['name']) . $this->row_actions($actions);
        return date('Y-m-d', $item['booking_time']) . $this->row_actions($actions);
    }

    /**
     * リスト表示準備
     *
     * @dba		parent object
     */
    public function prepare_items($dba = null) {

        // カラムヘッダープロパティの設定
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        // カレントページの取得
        $current_page = $this->get_pagenum() - 1;

        // 予約データの取得
        $this->items = $dba->read_data($current_page * $this->per_page, $this->per_page, $dba->set_order_key($_REQUEST));

        // 予約データ検索総数の取得
        $total_items = $dba->list_count();

        // ページネーション設定
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $this->per_page,
            'total_pages' => ceil($total_items / $this->per_page),
        ));
    }

}
