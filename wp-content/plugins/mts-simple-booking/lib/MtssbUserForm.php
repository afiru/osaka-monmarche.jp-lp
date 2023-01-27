<?php
/**
 * MTS Simple Booking クライアントデータフォーム処理
 *
 * @Filename    MtssbUserForm.php
 * @Date		2014-11-28
 * @Implemented Ver.1.20.0
 * @Author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-07-01
 */
class MtssbUserForm
{
    const PAGE_NAME = MTSSB_Register::PAGE_NAME;

    const JS = 'js/mtssb-register.js';
    const LOADING = 'image/ajax-loaderf.gif';
    const JS_ASSISTANCE = 'js/mts-assistance.js';
    const JS_POSTCODEJP = 'js/mts-postcodejp.js';
    const POSTCODEJP = 'https://postcode-jp.com/js/postcodejp.js';

    // コントローラー
    private $ctrl = null;

    public function __construct(MTSSB_Register $controller)
    {
        global $mts_simple_booking;

        $this->ctrl = $controller;

        // 住所検索JSの挿入
		wp_enqueue_script('mtssb-register', $mts_simple_booking->plugin_url . self::JS, array('jquery'));

        // 郵便番号検索の指定
        $premise = get_option(MTS_Simple_Booking::DOMAIN . '_premise');
        $zipSearch = isset($premise['zip_search']) ? $premise['zip_search'] : 0;

        if ($zipSearch == 1) {
            wp_enqueue_script('mts_assistance', $mts_simple_booking->plugin_url . self::JS_ASSISTANCE);
        } elseif ($zipSearch == 2) {
            wp_enqueue_script('postcodejp', self::POSTCODEJP);
            wp_enqueue_script('mts_postcodejp', $mts_simple_booking->plugin_url . self::JS_POSTCODEJP);
        }
    }

    /**
     * ユーザー登録完了メールフォームを戻す
     */
    public function registerMailForm($user, $shop)
    {
        return $this->_registerMail((object) $user, (object) $shop);
    }

    /**
     * ユーザー登録確認フォーム出力
     */
    public function confirmForm($user)
    {
        return $this->_confirmationForm((object) $user, $this->_ctrlInfo('confirm'));
    }

    /**
     * ユーザー登録フォーム出力
     */
    public function registerForm($user, $err)
    {
        $oError = $this->_errMessage($err, $this->ctrl->formItems());
        $oError->email2 = array_key_exists('email2', $err) ? $this->_errorField($err['email2']) : '';

        return $this->_inputForm((object) $user, $oError, $this->_ctrlInfo('entry'));
    }

    // エラーメッセージを生成する
    private function _errMessage($err, $formItems)
    {
        $error = new stdClass;

        foreach ($formItems as $column => $require) {
            if (is_array($require)) {
                $customErr = empty($err[$column]) ? array() : $err[$column];
                $error->$column = $this->_errMessage($customErr, $require);
            } elseif (!empty($err[$column])) {
                $error->$column = $this->_errorField($err[$column]);
            } else {
                $error->$column = '';
            }
        }

        return $error;
    }

    // フォーム送信の管理データを生成する
    private function _ctrlInfo($action)
    {
        global $mts_simple_booking;

        $premise = get_option(MTS_Simple_Booking::DOMAIN . '_premise');

        return (object) array(
            'action' => $action,
            'nonce' => wp_create_nonce(self::PAGE_NAME),
            'time' => current_time('timestamp'),
            'loadingImg' => $mts_simple_booking->plugin_url . self::LOADING,
            'zipSearch' => isset($premise['zip_search']) ? $premise['zip_search'] : 0,
            'apiKey' => isset($premise['api_key']) ? $premise['api_key'] : '',
        );
    }

    /**
     * 入力項目のエラー出力
     */
    private function _errorField($errCode)
    {
        static $errMessage = array(
            'INVALID_LENGTH' => '入力文字の長さが無効です。',
            'INVALID_CHARACTER' => '無効な文字が入力されました。',
            'INVALID_EMAIL' => 'メールアドレスの形式が正しくありません。',
            'DIFFERENT_EMAIL' => '再入力のメールアドレスが一致しません。',
            'USED_ALREADY' => '入力データは登録済みです。',
            'REQUIRED' => '必須入力項目です。',
            'UNPROCESSED' => '未処理状態です。',
        );

        return sprintf('<div class="error-message">%s</div>',
            isset($errMessage[$errCode]) ? $errMessage[$errCode] : $errCode);
    }

    /**
     * エラー終了の出力
     */
    public function errorOut($errCode)
    {
        static $errMessage = array(
            'MISSING_DATA' => 'パラメータエラーです。',
            'OUT_OF_DATE' => '操作が正しくありません。',
            'NOT_SELECTED' => '予約の入力を確認して下さい。',
            'OVER_TIME' => '時間がオーバーしました。',
            'FAILED_INSERT' => '新規登録でエラーが発生しました。',
            'FAILED_SENDING' => '登録メールの送信を失敗しました。確認のためご連絡下さい。'
        );

        if (array_key_exists($errCode, $errMessage)) {
            $msg = $errMessage[$errCode];
        } else {
            $msg = $errCode;
        }
    
        return sprintf('<div class="mtssb-error-content">%s</div>', $msg);
    }

    /**
     * ページメッセージ出力
     */
    public function messageOut($code)
    {
        static $message = array(
            'REGISTERED' => 'ユーザー登録を実行、仮パスワードをメール送信いたしました。',
        );

        if (array_key_exists($code, $message)) {
            $msg = $message[$code];
        } else {
            $msg = $code;
        }

        return sprintf('<div class="mtssb-message-content">%s</div>', $msg);
    }
    
    // ユーザー登録入力フォーム
    private function _inputForm($oUser, $err, $ctrl)
    {
        $searchBtn = '';
        if (0 < $ctrl->zipSearch) {
            $searchBtn = '<button id="mts-postcode-button" type="button" class="button-secondary" onclick="mts_assistance.findByPostcode'
                . sprintf("('%s', 'user-postcode', 'user-address1')", $ctrl->apiKey)
                . sprintf('" data-api_key="%s" data-postcode="user-postcode" data-address="user-address1">検索</button>', $ctrl->apiKey);
        }
        
        ob_start();
?>
        <div class="content-form">
            <div class="form-notice"><span class="required">*</span>の項目は必須です。</div>
            <form method="post">
                <fieldset class="user-form">
                    <legend>ログイン情報</legend>
                    <table>
                        <tr class="user-column username">
                            <th><label for="user-username">ログイン名 (<span class="required">*</span>)</label></th>
                            <td>
                                <input id="user-username" type="text" class="content-text medium" name="user[username]" value="<?php echo $oUser->username ?>">
                                <div class="description">半角英数字(アンダーバーを含む)で6文字以上32文字以下です。</div>
                                <?php echo $err->username ?>
                            </td>
                        </tr>
                        <tr class="user-column email">
                            <th><label for="user-email">E Mail (<span class="required">*</span>)</label></th>
                            <td>
                                <input id="user-email" type="text" class="content-text fat" name="user[email]" value="<?php echo $oUser->email ?>">
                                <?php echo $err->email ?>
                            </td>
                        </tr>
                        <tr class="user-column email2">
                            <th><label for="user-email2">E Mail再入力 (<span class="required">*</span>)</label></th>
                            <td>
                                <input id="user-email2" type="text" class="content-text fat" name="user[email2]" value="<?php echo $oUser->email2 ?>">
                                <?php echo $err->email2 ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <fieldset class="user-form">
                    <legend>連絡先</legend>
                    <table>
                        <tr class="user-column name">
                            <th><label for="user-sei">氏　名 (<span class="required">*</span>)</label></th>
                            <td>
                                <label for="user-sei" class="user-name">姓</label>
                                <input id="user-sei" type="text" class="content-text small-medium" name="user[sei]" value="<?php echo $oUser->sei ?>">
                                <label for="user-mei" class="user-name">名</label>
                                <input id="user-mei" type="text" class="content-text small-medium" name="user[mei]" value="<?php echo $oUser->mei ?>">
                                <?php echo $err->name ?>
                            </td>
                        </tr>
                        <tr class="user-column kana">
                            <th><label for="user-sei_kana">フリガナ (<span class="required">*</span>)</label></th>
                            <td>
                                <label for="user-sei_kana" class="user-name">セイ</label>
                                <input id="user-sei_kana" type="text" class="content-text small-medium" name="user[sei_kana]" value="<?php echo $oUser->sei_kana ?>">
                                <label for="user-mei_kana" class="user-name">メイ</label>
                                <input id="user-mei_kana" type="text" class="content-text small-medium" name="user[mei_kana]" value="<?php echo $oUser->mei_kana ?>">
                                <?php echo $err->kana ?>
                            </td>
                        </tr>
                        <tr class="user-column tel">
                            <th><label for="user-tel">連絡先TEL (<span class="required">*</span>)</label></th>
                            <td>
                                <input id="user-tel" type="text" class="content-text medium" name="user[tel]" value="<?php echo $oUser->tel ?>">
                                <?php echo $err->tel ?>
                            </td>
                        </tr>
                        <tr class="user-column company">
                            <th><label for="user-company">会社・団体名</label></th>
                            <td>
                                <input id="user-company" type="text" class="content-text fat" name="user[company]" value="<?php echo $oUser->company ?>">
                                <?php echo $err->company ?>
                            </td>
                        </tr>
                        <tr class="user-column postcode">
                            <th><label for="user-postcode">郵便番号</label></th>
                            <td>
                                <input id="user-postcode" type="text" class="content-text small-medium" name="user[postcode]" value="<?php echo $oUser->postcode ?>">
                                <?php echo $searchBtn;
                                echo $err->postcode; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="user-address1">住　所 (<span class="required">*</span>)</label></th>
                            <td>
                                <dl>
                                    <dt><label for="user-address1" class="user-address-header">住所</label></dt>
                                    <dd>
                                        <input id="user-address1" class="content-text fat" type="text" name="user[address1]" value="<?php echo $oUser->address1 ?>"><br />
                                    </dd>
                                    <dt><label for="user-address2" class="user-address-header">建物</label></dt>
                                    <dd>
                                        <input id="user-address2" class="content-text fat" type="text" name="user[address2]" value="<?php echo $oUser->address2 ?>">
                                    </dd>
                                </dl>
                                <?php echo $err->address ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <input type="hidden" name="nonce" value="<?php echo $ctrl->nonce ?>">
                <input type="hidden" name="start" value="<?php echo $ctrl->time ?>">
                <input type="hidden" name="action" value="<?php echo $ctrl->action ?>">
                <div id="action-button" class="register-button">
                    <input type="submit" class="button" value="確認する" name="cmd_entry">
                </div>
            </form>
        </div>
        
<?php
        return ob_get_clean();
    }
    
    // ユーザー登録確認フォーム
    private function _confirmationForm($oUser, $ctrl)
    {
        ob_start();
?>
        <div class="content-form">
            <form method="post">
                <fieldset class="user-form">
                    <legend>ログイン情報</legend>
                    <table>
                        <tr class="user-column username">
                            <th>ログイン名</th>
                            <td>
                                <?php echo esc_html($oUser->username) ?>
                                <input type="hidden" name="user[username]" value="<?php echo $oUser->username ?>">
                            </td>
                        </tr>
                        <tr class="user-column email">
                            <th>E Mail</th>
                            <td>
                                <?php echo esc_html($oUser->email) ?>
                                <input type="hidden" name="user[email]" value="<?php echo($oUser->email) ?>">
                                <input type="hidden" name="user[email2]" value="<?php echo ($oUser->email2) ?>">
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <fieldset class="user-form">
                    <legend>連絡先</legend>
                    <table>
                        <tr class="user-column name">
                            <th>氏　名</th>
                            <td>
                                <span class="name-sei"><?php echo esc_html($oUser->sei) ?></span> <span class="name-mei"><?php echo esc_html($oUser->mei) ?></span>
                                <input type="hidden" name="user[sei]" value="<?php echo $oUser->sei ?>">
                                <input type="hidden" name="user[mei]" value="<?php echo $oUser->mei ?>">
                            </td>
                        </tr>
                        <tr class="user-column kana">
                            <th>フリガナ</th>
                            <td>
                                <span class="name-sei"><?php echo esc_html($oUser->sei_kana) ?></span> <span class="name-mei"><?php echo esc_html($oUser->mei_kana) ?></span>
                                <input type="hidden" name="user[sei_kana]" value="<?php echo $oUser->sei_kana ?>">
                                <input type="hidden" name="user[mei_kana]" value="<?php echo $oUser->mei_kana ?>">
                            </td>
                        </tr>
                        <tr class="user-column tel">
                            <th>連絡先TEL</th>
                            <td>
                                <?php echo esc_html($oUser->tel) ?>
                                <input type="hidden" name="user[tel]" value="<?php echo $oUser->tel ?>">
                            </td>
                        </tr>
                        <tr class="user-column company">
                            <th>会社・団体名</th>
                            <td>
                                <?php echo esc_html($oUser->company) ?>
                                <input type="hidden" name="user[company]" value="<?php echo ($oUser->company) ?>">
                            </td>
                        </tr>
                        <tr class="user-column postcode">
                            <th>郵便番号</th>
                            <td>
                                <?php echo esc_html($oUser->postcode) ?>
                                <input type="hidden" name="user[postcode]" value="<?php echo ($oUser->postcode) ?>">
                            </td>
                        </tr>
                        <tr class="user-column address">
                            <th>住　所</th>
                            <td>
                                <div class="user-address"><?php echo esc_html($oUser->address1) ?></div>
                                <div class="user-address"><?php echo esc_html($oUser->address2) ?></div>
                                <input type="hidden" name="user[address1]" value="<?php echo $oUser->address1 ?>">
                                <input type="hidden" name="user[address2]" value="<?php echo $oUser->address2 ?>">
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <input type="hidden" name="nonce" value="<?php echo $ctrl->nonce ?>">
                <input type="hidden" name="start" value="<?php echo $ctrl->time ?>">
                <input type="hidden" name="action" value="<?php echo $ctrl->action ?>">
                <div id="action-button" class="register-button">
                    <input type="submit" class="button" value="登録する" name="cmd_register">
                    <input type="submit" class="button" value="戻る" name="cmd_previous">
                </div>
            </form>
        </div>
        
<?php
        return ob_get_clean();
    }
    
    // ユーザー登録完了メール
    private function _registerMail($oUser, $oShop)
    {
        $date = date_i18n('Y年n月j日 H:i');
        
        return array(
            'subject' => '【ユーザー登録のお知らせ】',
            'from' => '',
            'cc' => '',
            'bcc' => '',
            'body' =>
                "{$oUser->company}
{$oUser->sei} {$oUser->mei} 様

このたびはユーザー登録いただきまことにありがとうございます。
以下の通り登録が完了しましたのでお知らせいたします。

[手続き完了日] {$date}
[ユーザー名] {$oUser->username}
[仮パスワード] {$oUser->password}
[名　前] {$oUser->sei} {$oUser->mei}({$oUser->sei_kana} {$oUser->mei_kana}) 様
[電話番号] {$oUser->tel}
[E Mail] {$oUser->email}
[連絡先]
 〒{$oUser->postcode}
 {$oUser->address1}
 {$oUser->address2}

ご予約はサイトへログインしてからお申込み下さい。

またログイン中は画面上部にアドミンバーが表示され、右上のお名前からユー
ザーメニューを引き出すことができます。メニューからはプロフィールの編集
や予約データを参照いただけます。

今後ともご愛顧を賜りますようお願い申し上げます。


{$oShop->name}
{$oShop->postcode}
{$oShop->address1} {$oShop->address2}
TEL:{$oShop->tel} FAX:{$oShop->fax}
EMail:{$oShop->email}
Web:{$oShop->web}
"
        );
    }

}
