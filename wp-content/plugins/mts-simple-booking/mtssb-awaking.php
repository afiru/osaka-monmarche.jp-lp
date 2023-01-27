<?php
if (!class_exists('MTSSB_Booking')) {
	require_once(__DIR__ . '/mtssb-booking.php');
}
/**
 * MTS Simple Booking 予約事前メール送信機能
 *
 * @Filename	mtssb-awaking.php
 * @Date		2014-12-24
 * @Implemented Ver.1.21.0
 * @Author		S.Hayashi
 *
 * Updated to Ver.1.22.0 on 2015-06-30
 */
class MTSSB_Awaking
{
    private $oBooking;
    private $oMailTemplate;
    private $oMail;

    public function __construct()
    {
        global $mts_simple_booking;

        // 予約データオブジェクト
        $this->oBooking = new MTSSB_Booking();

        // メールテンプレートオブジェクト
        $this->oMailTemplate = $mts_simple_booking->_load_module('MTSSB_Mail_Template');

        // メールオブジェクト
        $this->oMail = $mts_simple_booking->_load_module('MTSSB_Mail');

        // Cronの設定を取得する
        $this->controls = get_option(MTS_Simple_Booking::DOMAIN . '_controls');
    }

    /**
     * 事前メール送信処理エントリ
     */
    public function awaking()
    {
        // 予約事前メールが設定されていなければ何もしない
        if (empty($this->controls['awaking']['mail'])) {
            return;
        }

        // 事前メール設定予約品目を取得する
        $articles = $this->_findAwakingArticles();
        if (empty($articles)) {
            return;
        }

        // 予約品目毎事前メールを送信する
        foreach ($articles as $article) {
            $this->_awakingBooking($article);
        }
    }

    // 事前メール設定予約品目の検索
    private function _findAwakingArticles()
    {
        $articles = MTSSB_Article::get_all_articles();

        foreach ($articles as $article_id => $article) {
            if (empty($article['addition']->awaking_time)) {
                unset($articles[$article_id]);
            }
         }

        return $articles;
    }

    /**
     * 予約品目毎事前メールを送信する
     *
     * @article     予約品目データ
     */
    private function _awakingBooking($article)
    {
        $today = explode('-', date_i18n('Y-n-j-H-i-s'));
        $dayTime = mktime(0, 0, 0, $today[1], $today[2], $today[0]);

        $nearTime = mktime($today[3], $today[4], 0, $today[1], $today[2], $today[0]);

        // 発信時間が１日以上前の場合は指定メール送信時刻を過ぎているか確認する
        if (1440 <= $article['addition']->awaking_time) {
            $sendTime = $article['addition']->awaking_hour * 3600 + $article['addition']->awaking_minute * 60;
            $checkTime = current_time('timestamp') % 86400;
            if ($checkTime < $sendTime) {
                return;
            }
        }

        // 発信対象時間 (1日以上前)
        if (1440 <= $article['addition']->awaking_time) {
            $farTime = $dayTime + $article['addition']->awaking_time * 60 + 86400;

        // 発信対象時間 (当日ｘ時間前)
        } else {
            $farTime = $nearTime + $article['addition']->awaking_time * 60;
        }

        // 動作確認
        //$tfp = fopen(__DIR__ . '/testcron.txt', 'a');
        //fputs($tfp, sprintf("near:%s far:%s\n", date_i18n('Y-n-j H:i:s', $nearTime), date_i18n('Y-n-j H:i:s', $farTime)));
        //fclose($tfp);

        // 送信対象予約IDを取得する
        $bookings = $this->oBooking->findAwaking($article['article_id'], $nearTime, $farTime);
        if (empty($bookings)) {
            return;
        }

        // 事前メールのテンプレートを取得する
        $template = $this->oMailTemplate->get_mail_template($article['addition']->awaking_mail);

        // 送信対象者に事前メールを送信する
        foreach ($bookings as $booking) {
            // 正常にメール送信されたら予約データのconfirmedカラムのawakedフラグをセットする
            if ($this->_sendMail($booking['booking_id'], $template, $article)) {
                $this->oBooking->setAwaked($booking['booking_id']);
            };
        }
    }

    /**
     * 予約者に事前メールを送信する
     *
     * @bookingId
     * @template
     * @article
     */
    private function _sendMail($bookingId, $template, $article)
    {
        // 予約データを取得する
        $booking = $this->oBooking->get_booking($bookingId);

        // メールモジュールのテンプレート変数をセットする
        $vars = $this->oMail->setTempVar($article, $booking);

        // 施設情報の埋め込み
        $content = $this->oMail->replaceVariable($template->mail_body, $vars);

        // メールタイトル
        $subject = $this->oMail->replaceVariable($template->mail_subject, $vars);

        // メール送信前フィルター
        $param = apply_filters('mtssb_mail_exchange', array(
            'state' => 'remind',
            'aid' => $booking['article_id'],
            'to' => $booking['client']['email'],
            'subject' => $subject,
            'body' => $content,
            'from' => '',
            'header' => array(),
        ));

        // メール送信
        if (!empty($param['to'])) {
            if ($this->oMail->templateMail($param['to'], $param['subject'], $param['body'], $param['from'], $param['header'])) {
                return true;
            }
        }

        // メール送信エラー
        return false;
    }


}