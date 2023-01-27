<?php

/**
 * MTS Simple Booking メール送信処理モジュール
 *
 * @Filename	mtssb-mail.php
 * @Date		2012-05-17
 * @Author		S.Hayashi
 *
 * Updated to 1.33.1 on 2020-07-25
 * Updated to 1.33.0 on 2020-04-28
 * Updated to 1.32.0 on 2019-08-22
 * Updated to 1.31.0 on 2019-05-17
 * Updated to 1.27.0 on 2017-08-04
 * Updated to 1.25.0 on 2016-10-27
 * Updated to 1.22.0 on 2016-06-30
 * Updated to 1.21.0 on 2015-01-03
 * Updated to 1.18.1 on 2014-12-29
 * Updated to 1.18.0 on 2014-09-30
 * Updated to 1.14.0 on 2014-01-22
 * Updated to 1.11.0 on 2013-10-31
 * Updated to 1.9.6 on 2013-10-22
 * Updated to 1.9.5 on 2013-09-06
 * Updated to 1.8.7 on 2013-08-05
 * Updated to 1.8.6 on 2013-08-02
 * Updated to 1.8.5 on 2013-07-04
 * Updated to 1.8.0 on 2013-05-30
 * Updated to 1.7.0 on 2013-05-11
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.4.5 on 2013-02-21
 * Updated to 1.3.1 on 2013-02-07
 * Updated to 1.3.0 on 2013-01-16
 * Updated to 1.1.5 on 2012-12-04
 * Updated to 1.1.1 on 2012-11-01
 * Updated to 1.1.0 on 2012-10-16
 */
class MTSSB_Mail {

    /**
     * Common private valiable
     */
    private $domain;
    private $shop;
    private $fromshop;
    private $shopmail;
    // メール設定データ
    private $template = array();
    // テンプレート埋込変数
    private $tempVar = array();

    /**
     * Constructor
     */
    public function __construct() {

        $this->domain = MTS_Simple_Booking::DOMAIN;

        // 施設情報の読み込み
        $this->shop = get_option($this->domain . '_premise');

        // メールのフロム情報
        $shopMail = $this->shop['email'] != '' ? $this->shop['email'] : get_bloginfo('admin_email');
        $shopName = $this->shop['name'] != '' ? $this->shop['name'] : get_bloginfo('name');
        $this->shopmail = sprintf('%s <%s>', $shopName, $shopMail);
        $this->fromshop = "From: {$this->shopmail}\n";
    }

    /**
     * お問い合わせのメール
     *
     */
    public function contact_mail() {
        global $mts_simple_booking;

        $contact = $mts_simple_booking->oContact->contact;
        $template = $mts_simple_booking->oContact->template;

        // 予約者情報
        $vars = array_merge(array(
            '%CLIENT_NAME%' => empty($contact['name']) ? 'お問い合わせ' : $contact['name'],
                ), $this->_shopTempVar());

        // メール文生成
        $body = $this->_contact_content();

        // クライアント
        $subject = $template['title'];
        $client_content = $this->replaceVariable($template['header'], $vars) . $body . $this->replaceVariable($template['footer'], $vars);
        $client_to = $contact['email'];
        $client_ret = true;
        if (!empty($client_to)) {
            $client_ret = wp_mail($client_to, $subject, $client_content, $this->fromshop);
        }

        // 自社
        $myheader = date_i18n('Y年n月j日 H:i') . "\n\n";
        $subject = apply_filters('mtssb_mail_own_subject', $subject, 'contact');
        $my_content = $this->replaceVariable($myheader, $vars) . $body;
        $my_to = $this->shop['email'];
        $my_ret = true;
        if (!empty($my_to)) {
            $my_ret = wp_mail($my_to, $subject, $my_content, $this->fromshop);
        }

        if (!$client_ret || !$my_ret) {
            return false;
        }

        return true;
    }

    /**
     * お問い合わせメールの本文生成
     *
     */
    private function _contact_content() {
        global $mts_simple_booking;

        $contact = &$mts_simple_booking->oContact->contact;

        $body = '';
        if (!empty($contact['company'])) {
            $body .= "[会社名]\n{$contact['company']}\n";
        }
        if (!empty($contact['name'])) {
            $body .= "[名前]\n{$contact['name']}\n";
        }
        if (!empty($contact['furigana'])) {
            $body .= "[フリガナ]\n{$contact['furigana']}\n";
        }
        if (!empty($contact['email'])) {
            $body .= "[E-MAIL]\n{$contact['email']}\n";
        }
        if (!empty($contact['postcode'])) {
            $body .= "[郵便番号]\n{$contact['postcode']}\n";
        }
        if (!empty($contact['address1'])) {
            $body .= "[住所]\n{$contact['address1']}\n";
            if (!empty($contact['address2'])) {
                $body .= "{$contact['address2']}\n";
            }
        }
        if (!empty($contact['tel'])) {
            $body .= "[電話番号]\n{$contact['tel']}\n";
        }
        $body .= "[ご要望・ご質問など
]\n" . $this->_form_message($contact['message']) . "\n\n";

        return $body;
    }

    /**
     * テンプレートメール送信
     */
    public function templateMail($to, $subject, $body, $from = '', $header = array()) {
        // メール送信準備
        $headers = array();

        $sendto = empty($to) ? $this->shopmail : $to;
        

        if (empty($from)) {
            $headers[] = $this->fromshop;
        } else {
            $headers[] = sprintf('From: %s', $from);
        }

        foreach ($header as $key => $val) {
            if ($key == 'cc') {
                $headers[] = sprintf('Cc: %s', $val);
            } elseif ($key == 'bcc') {
                $headers[] = sprintf('Bcc: %s', $val);
            } else {
                $headers[] = $val;
            }
        }

        // メール送信
        return wp_mail($sendto, $subject, $body, $headers);
    }

    /**
     * 予約確認完了のメール
     *
     */
    public function confirmed_mail($subject, $body) {
        global $mts_simple_booking;

        $booking = $mts_simple_booking->blist->getBooking();

        // メール送信
        $client_to = $booking['client']['email'];
        $mail_ret = true;
        if (!empty($client_to)) {
            $mail_ret = wp_mail($client_to, $subject, $body, $this->fromshop);
        }

        return $mail_ret;
    }

    /**
     * 予約データから RESERVE_ID を求める
     *
     */
    private function _make_reserve_id($booking_id, $booking_time) {
        return date('ymd', $booking_time) . substr("00{$booking_id}", -3);
    }

    /**
     * 予約登録のメール
     *
     */
    public function booking_mail() {
        global $mts_simple_booking;

        $client_ret = $send_ret = $my_ret = true;

        $booking = $mts_simple_booking->oBooking_form->getBooking();
        $charge = $mts_simple_booking->oBooking_form->getCharge();
        $article = $mts_simple_booking->oBooking_form->getArticle();

        // 予約IDの生成
        $reserve_id = $this->_make_reserve_id($booking['booking_id'], $booking['booking_time']);

        // 予約者情報(CLIENT_NAME, RESERVE_ID)
        $adda = $this->setTempVar($article, $booking);

        // メールテンプレートの読込み
        $this->template = get_option($this->domain . '_reserve');

        // メール送信パラメータ
        $default_params = array(
            'title' => $this->template['title'],
            'header' => $this->template['header'],
            'body' => '',
            'footer' => $this->template['footer'],
            'article_id' => $article['article_id'],
            'receiver' => '',
            'adda' => $adda,
        );

        // メール文生成
        $body = $this->_booking_content($booking);

        // クライアント
        $body_c = $body;
        if ($charge['charge_list'] == 1) {
            // 支払についての追加
            $body_c .= empty($booking['client']['transaction_id']) ? $this->_unsettled_message() : $this->_settled_message($booking['client']['transaction_id']);
        }

        if (!empty($booking['client']['email'])) {
            $client_ret = $this->_send_booking_mail($booking['client']['email'], array_merge($default_params, array(
                'body' => $body_c,
                'receiver' => 'client',
            )));
        }

        // 自社
        $myheader = date_i18n('Y年n月j日 H:i') . "\n予約ID：{$reserve_id}\n\n";
        $body_m = $body;
        if ($charge['charge_list'] == 1 && !empty($booking['client']['transaction_id'])) {
            // 支払についての追加
            $body_m .= "[Transaction ID]\n{$booking['client']['transaction_id']}\n";
        }

        // 予約状況を取得する
        $oStatus = $mts_simple_booking->oBooking_form->getBookingStatus($booking['article_id'], $booking['booking_time']);

        // 予約状況の追加
        $bookingInfo = $this->bookingStatusInfo($booking['booking_time'], $oStatus, $article);
        $body_m .= $bookingInfo;

        $params = array_merge($default_params, array(
            'header' => $myheader,
            'body' => $body_m,
            'footer' => '',
            'receiver' => 'article'
        ));

        // 複数メール送信
        $addresses = $article['addition']->booking_mail;
        if (!empty($addresses)) {
            foreach ($addresses as $send_to) {
                if (!empty($send_to)) {
                    $send_ret = $this->_send_booking_mail($send_to, $params);
                    if (!$send_ret) {
                        break;
                    }
                }
            }
        }

        // 施設管理者宛メール送信
        if (!empty($this->shop['email'])) {
            $my_ret = $this->_send_booking_mail($this->shop['email'], array_merge($params, array(
                'receiver' => 'admin'
            )));
        }

        // 携帯
        if (!empty($this->shop['mobile'])) {
            if (!apply_filters('mtssb_mail_booking_mobile', false)) {
                $params['body'] = '';
            }
            $mobile_ret = $this->_send_booking_mail($this->shop['mobile'], array_merge($params, array(
                'receiver' => 'mobile',
            )));
        }

        if (!$client_ret || !$my_ret || !$send_ret) {
            return false;
        }

        return true;
    }

    /**
     * メールに含める予約状況文字列
     */
    public function bookingStatusInfo($bookingTime, $oStatus, $article) {
        $str = "現在の予約状況：\n";

        // 予約枠
        $totalSeat = $article[$article['restriction']] + $oStatus->schedule['delta'];


        // 予約済み
        $bookedSeat = 0;
        if (isset($oStatus->count[$bookingTime])) {
            $bookedSeat = $article['restriction'] == 'capacity' ? $oStatus->count[$bookingTime]['number'] : $oStatus->count[$bookingTime]['count'];
        }

        // 予約情報
        $str .= sprintf("　予約数 %d / 予約枠 %d\n", $bookedSeat, $totalSeat);

        $info = apply_filters('mtssb_mail_booking_info', $str, array(
            'article_id' => $article['article_id'],
            'booked' => $bookedSeat,
            'total' => $totalSeat));

        return $info;
    }

    /**
     * 予約メールを送信する
     */
    private function _send_booking_mail($address, $params) {
        $fParams = ['article_id' => $params['article_id'], 'receiver' => $params['receiver']];

        // 件名
        $subject = apply_filters('mtssb_mail_booking_subject', $params['title'], $fParams);

        // 前文
        $header = apply_filters('mtssb_mail_booking_header', $params['header'], $fParams);

        // 後文
        $footer = apply_filters('mtssb_mail_booking_footer', $params['footer'], $fParams);

        // 変数を変換し全文を作る
        $content = $header . $params['body'] . $footer;
        $body = $this->replaceVariable($content, $params['adda']);

        // 送信元情報
        $from = apply_filters('mtssb_mail_booking_from', $this->fromshop, $fParams);

        // アタッチメント
        $attachments = apply_filters('mtssb_mail_booking_attachments', [], $fParams);

        return wp_mail($address, $subject, $body, $from, $attachments);
    }

    /**
     * ショップ情報をテンプレート変数にセットして戻す
     *
     */
    private function _shopTempVar() {
        return array(
            '%NAME%' => $this->shop['name'],
            '%POSTCODE%' => $this->shop['postcode'],
            '%ADDRESS%' => $this->shop['address1'] . ($this->shop['address2'] ? "\n{$this->shop['address2']}" : ''),
            '%TEL%' => $this->shop['tel'],
            '%FAX%' => $this->shop['fax'],
            '%EMAIL%' => $this->shop['email'],
            '%WEB%' => $this->shop['web'],
        );
    }

    /**
     * 予約品目、予約データ、ショップ情報をテンプレート変数にセットする
     *
     */
    public function setTempVar($article = null, $booking = null) {
        $this->tempVar = $aVar = $bVar = array();

        if ($article) {
            $aVar = array(
                '%ARTICLE_NAME%' => $article['name'],
            );
        }

        if ($booking) {
            $bVar = array(
                '%RESERVE_ID%' => $this->_make_reserve_id($booking['booking_id'], $booking['booking_time']),
                '%BOOKING_DATE%' => date_i18n('Y年n月j日', $booking['booking_time']),
                '%BOOKING_TIME%' => date_i18n('H時i分', $booking['booking_time']),
                '%BOOKING_NUMBER%' => $booking['number'],
                '%BOOKING_NOTE%' => $this->_form_message($booking['note']),
                '%CLIENT_COMPANY%' => $booking['client']['company'],
                '%CLIENT_NAME%' => $booking['client']['name'],
                '%CLIENT_SEI%' => $booking['client']['sei'],
                '%CLIENT_MEI%' => $booking['client']['mei'],
                '%CLIENT_FURIGANA%' => $booking['client']['furigana'],
                '%CLIENT_SEI_KANA%' => $booking['client']['sei_kana'],
                '%CLIENT_MEI_KANA%' => $booking['client']['mei_kana'],
                '%CLIENT_EMAIL%' => $booking['client']['email'],
                '%CLIENT_POSTCODE%' => $booking['client']['postcode'],
                '%CLIENT_ADDRESS1%' => $booking['client']['address1'],
                '%CLIENT_ADDRESS2%' => $booking['client']['address2'],
                '%CLIENT_ADULT%' => $booking['client']['adult'],
                '%CLIENT_CHILD%' => $booking['client']['child'],
                '%CLIENT_BABY%' => $booking['client']['baby'],
            );
        }

        $sVar = $this->_shopTempVar();

        $this->tempVar = array_merge($aVar, $bVar, $sVar);

        return $this->tempVar;
    }

    /**
     * テンプレート変数をテンプレートに埋込む
     *
     */
    public function replaceVariable($temp, $vars) {
        // 送信文の変数を置換する
        return str_replace(array_keys($vars), array_values($vars), $temp);
    }

    /**
     * 予約メールの本文生成
     *
     */
    private function _booking_content($booking) {
        global $mts_simple_booking;

        $charge = $mts_simple_booking->oBooking_form->getCharge();
        $controls = $mts_simple_booking->oBooking_form->getControls();
        $article = $mts_simple_booking->oBooking_form->getArticle();
        $client = &$booking['client'];

        $body = apply_filters('booking_form_number_title', '[ご予約]', 'mail') . "\n"
                . "{$article['name']}\n"
                . apply_filters('booking_form_date_title', '日時：', 'mail') . apply_filters('booking_form_date', date_i18n('Y年n月j日', $booking['booking_time']), $booking['booking_time'], 'mail') . "\n"
                . apply_filters('booking_form_date_number', '人数：', 'mail');

        // 人数表示をしないようにする条件確認
        if (apply_filters('booking_form_date_number_print', true)) {
            foreach ($controls['count'] as $key => $val) {
                if (0 < $client[$key]) {
                    $body .= apply_filters('booking_form_count_label', __(ucwords($key), $this->domain), 'mail') . " $client[$key]" . ($key == 'car' ? '台' : '名') . ', ';
                }
            }
            if (substr($body, -2) == ', ') {
                $body = substr($body, 0, -2);
            }
            $body .= "\n";
        }

        // オプション　前指定のとき
        if ($article['addition']->isOption() && $article['addition']->position == 0) {
            $body .= $this->_booking_content_option($booking['options']);
        }

        // 連絡先
        $body .= $this->_booking_content_client($booking['client']);

        // オプション　後指定のとき
        if ($article['addition']->isOption() && $article['addition']->position == 1) {
            $body .= $this->_booking_content_option($booking['options']);
        }

        // 連絡事項
        if (!empty($booking['note'])) {
            $body .= "\n" . apply_filters('booking_form_message_title', '[ご要望・ご質問など]', 'mail') . "\n";
            $body .= $this->_form_message($booking['note']) . "\n";
        }

        $body .= "\n";


        // 料金明細データの追加
        if ($charge['charge_list'] == 1) {
            $body .= $this->_bill_content($article['addition']);
        }

        return $body;
    }

    /**
     * 予約メール本文　連絡先
     *
     */
    private function _booking_content_client($client) {
        // フォーム並び順配列
        $column_order = explode(',', $this->template['column_order']);

        $body = "\n" . apply_filters('booking_form_client_title', '[連絡先]', 'mail') . "\n";

        foreach ($column_order as $cname) {
            if (intval($this->template['column'][$cname]) <= 0) {
                continue;
            }

            switch ($cname) {
                case 'company' :
                    $body .= apply_filters('booking_form_company', '会社名', 'mail') . "：{$client['company']}\n";
                    break;
                case 'name' :
                    $body .= apply_filters('booking_form_name', '名前', 'mail') . "：{$client['name']}\n";
                    break;
                case 'furigana' :
                    $body .= apply_filters('booking_form_furigana', 'フリガナ', 'mail') . "：{$client['furigana']}\n";
                    break;
                case 'birthday' :
                    $body .= apply_filters('booking_form_birthday', '生年月日', 'mail') . "："
                            . apply_filters('booking_form_birthday_date', $client['birthday']->get_date('j'), $client['birthday']->get_date()) . "\n";
                    break;
                case 'gender' :
                    $body .= apply_filters('booking_form_gender', '性別', 'mail') . "："
                            . apply_filters('booking_form_gender_type', (empty($client['gender']) ? '' : ($client['gender'] == 'male' ? '男性' : '女性')), $client['gender']) . "\n";
                    break;
                case 'email' :
                    $body .= apply_filters('booking_form_email', 'E-Mail', 'mail') . "：{$client['email']}\n";
                    break;
                case 'postcode' :
                    $body .= apply_filters('booking_form_postcode', '郵便番号', 'mail') . "：{$client['postcode']}\n";
                    break;
                case 'address' :
                    $body .= apply_filters('booking_form_address', '住所', 'mail') . "：{$client['address1']}";
                    if (!empty($client['address2'])) {
                        $body .= " {$client['address2']}";
                    }
                    $body .= "\n";
                    break;
                case 'tel' :
                    $body .= apply_filters('booking_form_tel', '電話番号', 'mail') . "：{$client['tel']}\n";
                    break;
                case 'newuse' :
                    if (empty($client['newuse'])) {
                        $ans = '';
                    } else if ($client['newuse'] == 1) {
                        $ans = apply_filters('booking_form_newuse_yes', 'はい');
                    } else {
                        $ans = apply_filters('booking_form_newuse_no', 'いいえ');
                    }
                    $body .= apply_filters('booking_form_newuse', '新規利用', 'mail') . "：{$ans}\n";
                    break;
                default :
                    break;
            }
        }

        return $body;
    }

    /**
     * 予約メール本文　オプション選択
     *
     */
    private function _booking_content_option($options) {
        $body = "\n" . apply_filters('booking_form_option_title', '[オプション注文]', 'mail') . "\n";

        foreach ($options as $option) {
            $keyname = $option->getKeyname();
            // オプション項目をメール出力しないようにするメッセージフィルター
            if (!apply_filters('option_confirm_output', true, array('name' => $keyname, 'mail' => true))) {
                continue;
            }

            // オプション項目名
            $body .= apply_filters("option_confirm_label", $option->getLabel(), array('name' => $keyname, 'mail' => true)) . '：';

            // オプション入力データ
            if ($option->type == 'textarea') {
                $body .= $this->_form_message($option->getText(), $keyname);
            } else {
                $body .= apply_filters("option_confirm_text", $option->getText(), array('name' => $keyname, 'mail' => true)) . ' ';
            }

            // オプションの注釈
            $body .= apply_filters("option_confirm_note", $option->getNote(), array('name' => $keyname, 'mail' => true));

            $body .= "\n";
        }

        return $body;
    }

    /**
     * 入力メッセージの幅を整形する
     *
     * @message		入力メッセージ
     * @sub			対象メッセージ
     */
    private function _form_message($message, $sub = '') {
        // メールの１行文字数
        $width = apply_filters('booking_mail_chg_chars', 72, $sub);

        // 改行文字を\nに統一する
        $message = preg_replace("/(\r\n|\r)/", "\n", $message);

        // 行を切り出す
        $strs = mb_split("\n", $message);

        // 行がない場合は文字をそのまま戻す
        if (!is_array($strs)) {
            return $message;
        }

        $formed = '';
        // 各行を72桁幅にする
        foreach ($strs as $str) {
            while ($width < mb_strwidth($str)) {
                $strw = mb_strimwidth($str, 0, $width, "\n");
                $formed .= $strw;
                $str = mb_substr($str, mb_strlen($strw) - 1);
            }
            $formed .= $str . "\n";
        }

        return $formed;
    }

    /**
     * 予約メール料金明細情報
     *
     * @opflag		オプションの有無
     */
    protected function _bill_content($opflag) {
        global $mts_simple_booking;

        // 予約条件パラメータのロード
        $charge = $mts_simple_booking->oBooking_form->getCharge();

        $oBooking = &$mts_simple_booking->oBooking_form;
        $bill = $oBooking->make_bill();

        $total = $bill->get_total();
        if ($total == 0) {
            return '';
        }

        $check = apply_filters('booking_form_bill_title', '[ご請求]', 'mail_bill') . "\n";

        $check .= "明細\n";

        // 予約料金
        if (0 < $bill->basic_charge) {
            $check .= apply_filters('booking_form_charge_booking', "　{$bill->article_name}　料金:", 'mail_bill')
                    . "1　単価：" . $oBooking->money_format($bill->basic_charge)
                    . "　金額:" . $oBooking->money_format($bill->basic_charge) . "\n";
        }

        // 予約品目
        foreach (array('adult', 'child', 'baby') as $type) {
            if ($bill->number->$type != 0) {
                $cost = $bill->get_cost($type);
                if ($cost != 0) {
                    $check .= apply_filters('booking_form_charge_count', "　{$bill->article_name}　", 'mail_bill')
                            . apply_filters('booking_form_count_label', __(ucwords($type), $this->domain), 'mail_bill')
                            . ":{$bill->number->$type}　単価:" . $oBooking->money_format($bill->amount->$type)
                            . "　金額:" . $oBooking->money_format($bill->get_cost($type)) . "\n";
                }
            }
        }

        // オプション
        if ($opflag) {
            foreach ($bill->option_items as $item) {
                $check .= "　{$item['name']}"
                        . ":{$item['number']}　単価:" . $oBooking->money_format($item['price'])
                        . "　金額:" . $oBooking->money_format($item['number'] * $item['price']) . "\n";
            }
        }

        // 消費税表示
        if (0 < $charge['tax_notation']) {
            $check .= "合計　" . $oBooking->money_format($total) . "\n"
                    . ($charge['tax_notation'] == 1 ? '内' : '') . "消費税({$bill->tax}%)　"
                    . $oBooking->money_format($bill->get_amount_tax($charge['tax_notation'] == 1)) . "\n";
        }

        // 総合計表示
        $check .= "総合計　" . $oBooking->money_format($total + ($charge['tax_notation'] == 2 ? $bill->get_amount_tax() : 0)) . "\n";

        return $check . "\n";
    }

    /**
     * 未決済における「支払について」を戻す
     *
     */
    protected function _unsettled_message() {
        global $mts_simple_booking;

        $charge = $mts_simple_booking->oBooking_form->getCharge();

        $message = $charge['unsettled_mail'] . "\n\n";

        return $message;
    }

    /**
     * 決済終了時の「支払について」を戻す
     *
     * @transaction_id
     */
    protected function _settled_message($transaction_id = 0) {
        global $mts_simple_booking;

        $search = array('%TRANSACTION_ID%');
        $replace = array($transaction_id);

        $charge = $mts_simple_booking->oBooking_form->getCharge();

        $message = str_replace($search, $replace, $charge['settled_mail']) . "\n\n";

        return $message;
    }

    public function getShopEMail() {
        return $this->shop['email'];
    }

}
