<?php
if (!class_exists('MtssbUserForm')) {
	require_once(__DIR__ . '/lib/MtssbUserForm.php');
}
/**
 * MTS Simple Booking フロントエンドユーザー登録ページ
 *
 * @Filename	mtssb-register.php
 * @Date		2014-11-28
 * @Implemented Ver.1.20.0
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-07-01
 */
class MTSSB_Register
{
	const PAGE_NAME = MTS_Simple_Booking::PAGE_REGISTER;
    const PAGE_THANKS = MTS_Simple_Booking::PAGE_REGISTER_THANKS;

    private $user = array();

    private $view = null;

    private $errflg = false;
    private $errcode = '';
    private $err = array();

    public function __construct()
    {
        // フォームのフロント処理ディスパッチャー
        add_filter('the_content', array($this, 'content'), apply_filters('mtssb_the_content_priority', 11, 'mtssb-register'));

        $this->view = new MtssbUserForm($this);
    }

    /**
     * ユーザー登録処理
     */
    public function registerUser()
    {
        // 新規ユーザーデータを取得する
        $this->user = $this->clearUser();
    
        // エラーなら処理中止
        if (isset($_POST['nonce']) && $this->_illegalInput()) {
            if ($this->_normalize()) {
                if (isset($_POST['cmd_register'])) {
                    // ユーザー新規登録
                    if (!$this->_newUser()) {
                        return $this->_setError('FAILED_INSERT');
                    }
        
                    // 登録完了メール送信
                    if (!$this->_sendMail()) {
                        return $this->_setError('FAILED_SENDING');
                    }
        
                    // 登録完了リダイレクト表示
                    $oWp = get_page_by_path(self::PAGE_THANKS);
                    if ($oWp) {
                        $url = get_permalink($oWp);
                        $redirect = add_query_arg(array(
                            'action' => 'registered',
                            'nonce'  => wp_create_nonce(self::PAGE_NAME),
                        ), $url);
                        wp_redirect($redirect);
                        exit();
                    }
                }
            }
            elseif (isset($_POST['cmd_register'])) {
                return $this->_setError('OUT_OF_DATE');
            }
        }

        return true;
    }

    // ユーザー登録完了メールの送信
    private function _sendMail()
    {
        // 施設情報を取得する
        $shop = get_option(MTS_Simple_Booking::DOMAIN . '_premise');

        // メールデータを取得する
        $mail = $this->view->registerMailForm($this->user, $shop);

        // メール送信元
        $from = $this->_fromMail($shop);

        // メール送信準備
        $headers = array();
        $headers[] = sprintf('From: %s', $mail['from'] ?: $from);
        $headers[] = sprintf('Bcc: %s', $from);
        if (!empty($mail['cc'])) {
            $headers[] = sprintf('Cc: %s', $mail['cc']);
        }
        if (!empty($mail['bcc'])) {
            $headers[] = sprintf('Bcc: %s', $mail['bcc']);
        }

        $headers = apply_filters('mtssb_register_mail_header', $headers);

        // メール送信
        return wp_mail($this->user['email'], $mail['subject'], $mail['body'], $headers);
    }

    // 施設、from メールアドレス
    private function _fromMail($shop)
    {
        return sprintf('%s <%s>', ($shop['name'] ?: $shop['email']), $shop['email']);
    }

    /**
     * 新規ユーザーの登録
     */
    private function _newUser()
    {
        global $mts_simple_booking;

        // パスワード
        $this->user['password'] = wp_generate_password(12, false);

        $userdata = array(
            'user_login' => $this->user['username'],
            'user_pass' => $this->user['password'],
            'user_nicename' => $this->user['username'],
            'user_email' => $this->user['email'],
            'display_name' => sprintf('%s %s', $this->user['sei'], $this->user['mei']),
            'nickname' => $this->user['username'],
            'first_name' => $this->user['mei'],
            'last_name' => $this->user['sei'],
            'role' => $mts_simple_booking::USER_ROLE,
        );

        // ユーザー新規追加
        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            return false;
        }
        $this->user['user_id'] = $user_id;

        // ユーザーのオプションデータを保存する
        add_user_meta($user_id, 'mtscu_company', $this->user['company'], true);
        $furigana = trim(sprintf('%s %s', $this->user['sei_kana'], $this->user['mei_kana']));
        add_user_meta($user_id, 'mtscu_furigana', $furigana, true);
        add_user_meta($user_id, 'mtscu_postcode', $this->user['postcode'], true);
        add_user_meta($user_id, 'mtscu_address1', $this->user['address1'], true);
        add_user_meta($user_id, 'mtscu_address2', $this->user['address2'], true);
        add_user_meta($user_id, 'mtscu_tel', $this->user['tel'], true);

        // 登録データの保存
        add_user_meta($user_id, 'mtssb_entry', $this->user, true);

        return true;
    }

    /**
     * wp_content
     */
    public function content($content)
    {
        // NONCE、操作時間、上位処理エラーの確認
        if ($this->errcode) {
            return $this->view->errorOut($this->errcode);
        }

        // ユーザー登録データのフォーム送信後
        if (isset($_POST['nonce'])) {
            // 入力フォームエラーなし
            if (isset($_POST['cmd_entry']) && empty($this->err)) {
                return $this->view->confirmForm($this->user) . $content;
            }
            // ユーザ−登録終了
            elseif (isset($_POST['cmd_register'])) {
                return $this->view->messageOut('REGISTERED');
            }
        }

        // ユーザー登録フォームを表示する
        $content = $this->view->registerForm($this->user, $this->err) . $content;

        return $content;
    }

    // 入力チェック
    private function _illegalInput()
    {
        // NONCEチェック
        if (!wp_verify_nonce($_POST['nonce'], self::PAGE_NAME)) {
            return $this->_setError('OUT_OF_DATE');
        }

        // 操作時間チェック
        if ((intval($_POST['start']) + 3600 < current_time('timestamp'))) {
            return $this->_setError('OVER_TIME');
        }

        return true;
    }

    /**
     * フォーム入力データの正規化
     */
    private function _normalize()
    {
        $ret = true;
        $_POST = stripslashes_deep($_POST);

        // 入力フォームカラムを取得する
        $items = $this->formItems();

        foreach ($items as $column => $require) {
            if ($require === 'none') {
                continue;
            }
            switch ($column) {
                case 'username':
                    $ret &= $this->_normalizeUsername($require);
                    break;
                case 'email':
                    $ret &= $this->_normalizeEmail($require);
                    break;
                case 'name':
                case 'kana':
                    $ret &= $this->_normalizeName($require, $column);
                    break;
                case 'tel':
                    $ret &= $this->_normalizeTel($require);
                    break;
                case 'company':
                    $ret &= $this->_normalizeCompany($require);
                    break;
                case 'postcode':
                    $ret &= $this->_normalizePostcode($require);
                    break;
                case 'address':
                    $ret &= $this->_normalizeAddress($require);
                    break;
                default:
                    break;
            }
        }

        return $ret;
    }

    // username ユーザー名の正規化
    private function _normalizeUsername($info)
    {
        $this->user['username'] = mb_substr($_POST['user']['username'], 0, 255);

        if ($info === 'true' && empty($this->user['username'])) {
            $this->err['username'] = 'REQUIRED';
            return false;
        }

        // 文字列長さ
        $length = strlen($_POST['user']['username']);
        if ($length < 6 || 32 <= $length) {
            $this->err['username'] = 'INVALID_LENGTH';
            return false;
        }

        // ユーザー名の文字チェック
        if (!preg_match('/[\w@_-]+/', $this->user['username'])) {
            $this->err['username'] = 'INVALID_CHARACTER';
            return false;
        }

        // 既存ユーザー名の確認
        if (username_exists($this->user['username'])) {
            $this->err['username'] = 'USED_ALREADY';
            return false;
        }

        return true;
    }

    // メールアドレスの正規化
    private function _normalizeEmail($info)
    {
        $this->user['email'] = mb_substr($_POST['user']['email'], 0, 256);
        $this->user['email2'] = empty($_POST['user']['email2']) ? '' : mb_substr($_POST['user']['email2'], 0, 255);

        if ($info === 'true' && empty($this->user['email'])) {
            $this->err['email'] = 'REQUIRED';
            return false;
        }

        // 文字列の長さ
        if (256 < strlen($_POST['user']['email'])) {
            $this->err['email'] = 'INVALID_LENGTH';
            return false;
        }

        // E Mailの確からしさ
        if (!preg_match("/^[0-9a-z_\.\-]+@[0-9a-z_\-\.]+$/i", $this->user['email'])) {
            $this->err['email'] = 'INVALID_EMAIL';
            return false;
        }

        // 再入力データと比較する
        if (isset($_POST['user']['email2']) && $this->user['email'] != $_POST['user']['email2']) {
            $this->err['email2'] = 'DIFFERENT_EMAIL';
            return false;
        }

        // 既存メールアドレスか確認する
        if (email_exists($this->user['email'])) {
            $this->err['email'] = 'USED_ALREADY';
            return false;
        }

        return true;
    }

    // 名前の正規化
    private function _normalizeName($info, $column)
    {
        if ($column === 'name') {
            $sei = $this->user['sei'] = trim(mb_convert_kana(mb_substr($_POST['user']['sei'], 0, 255), 's'));
            $mei = $this->user['mei'] = trim(mb_convert_kana(mb_substr($_POST['user']['mei'], 0, 255), 's'));
        } else {
            $sei = $this->user['sei_kana'] = trim(mb_convert_kana(mb_substr($_POST['user']['sei_kana'], 0, 255), 'asKCV'));
            $mei = $this->user['mei_kana'] = trim(mb_convert_kana(mb_substr($_POST['user']['mei_kana'], 0, 255), 'asKCV'));
        }
        if ($info === 'true' && (empty($sei) || empty($mei))) {
            $this->err[$column] = 'REQUIRED';
            return false;
        }

        // 文字の長さ
        if (256 < (strlen($sei) + strlen($mei))) {
            $this->err[$column] = 'INVALID_LENGTH';
            return false;
        }

        return true;
    }

    // 電話番号の正規化
    private function _normalizeTel($info)
    {
        if (isset($_POST['user']['tel'])) {
            $this->user['tel'] = mb_substr(trim(mb_convert_kana($_POST['user']['tel'], 'as')), 0, 32);
        }

        if ($info === 'true' && empty($this->user['tel'])) {
            $this->err['tel'] = 'REQUIRED';
            return false;
        }

        if (!preg_match('/[\d\(\)-]*/', $this->user['tel'])) {
            $this->err['tel'] = 'INVALID_CHARACTER';
            return false;
        }

        return true;
    }

    // 会社名の正規化
    private function _normalizeCompany($info)
    {
        if (isset($_POST['user']['company'])) {
            $this->user['company'] = mb_substr(trim(mb_convert_kana($_POST['user']['company'], 's')), 0, 255);
        }

        if ($info === 'true' && empty($this->user['company'])) {
            $this->err['company'] = 'REQUIRED';
            return false;
        }

        return true;
    }

    // 郵便番号の正規化
    private function _normalizePostcode($info)
    {
        if (isset($_POST['user']['postcode'])) {
            $this->user['postcode'] = mb_substr(trim(mb_convert_kana($_POST['user']['postcode'], 'as')), 0, 8);
        }

        if ($info === 'true' && empty($this->user['postcode'])) {
            $this->err['postcode'] = 'REQUIRED';
            return false;
        }

        if (!preg_match('/[\d-]*/', $this->user['postcode'])) {
            $this->err['postcode'] = 'INVALID_CHARACTER';
            return false;
        }

        return true;
    }

    // 住所の正規化
    private function _normalizeAddress($info)
    {
        if (isset($_POST['user']['address1'])) {
            $this->user['address1'] = mb_substr(trim(mb_convert_kana($_POST['user']['address1'], 'as')), 0, 255);
        }
        if (isset($_POST['user']['address2'])) {
            $this->user['address2'] = mb_substr(trim(mb_convert_kana($_POST['user']['address2'], 'as')), 0, 255);
        }

        if ($info === 'true' && empty($this->user['address1'])) {
            $this->err['address'] = 'REQUIRED';
            return false;
        }

        return true;
    }

    // エラーコード設定
    private function _setError($errCode)
    {
        $this->errcode = $errCode;
        $this->errflg = true;

        return false;
    }

    /**
     * 新規ユーザーデータ
     */
    public function clearUser()
    {
        $user = array(
            'username' => '',
            'email' => '',
            'email2' => '',
            'sei' => '',
            'mei' => '',
            'sei_kana' => '',
            'mei_kana' => '',
            'company' => '',
            'postcode' => '',
            'address1' => '',
            'address2' => '',
            'tel' => '',
            'birthday' => '',
        );

        // カスタマイズデータ項目追加
        return apply_filters('mtssb_register_user', $user);
    }

    /**
     * 入力項目並び
     */
    public function formItems()
    {
        $items = array(
            'username' => 'true',
            'email' => 'true',
            'name' => 'true',
            'kana' => 'true',
            'tel' => 'true',
            'company' => 'false',
            'postcode' => 'false',
            'address' => 'true',
            'birthday' => 'none',
        );

        // カスタマイズ項目追加
        return apply_filters('mtssb_register_items', $items);
    }

}