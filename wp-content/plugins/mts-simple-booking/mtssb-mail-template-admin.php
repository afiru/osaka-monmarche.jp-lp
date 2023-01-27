<?php
if (!class_exists('MTSSB_Mail_Template')) {
    require_once(dirname(__FILE__) . '/mtssb-mail-template.php');
}
/**
 * MTS Simple Booking Mail Template メール文テンプレート管理モジュール
 *
 * @Filename    mtssb-mail-template-admin.php
 * @Date        2014-01-10 Ver.1.14.0
 * @Author      S.Hayashi
 *
 * Updated to Ver.1.21.0 on 2015-01-09
 */

class MTSSB_Mail_Template_Admin extends MTSSB_Mail_Template
{
    const PAGE_NAME = 'simple-booking-mail-template';

    private static $iTemplate = null;


    private $mode = 'new';
    private $action = '';
    private $message = '';
    private $errflg = false;

    private $templates = null;

    /**
     * インスタンス化
     *
     */
    static function get_instance() {
        if (!isset(self::$iTemplate)) {
            self::$iTemplate = new MTSSB_Mail_Template_Admin();
    }

        return self::$iTemplate;
    }

    public function __construct()
    {
        global $mts_simple_booking;

        $this->domain = MTS_Simple_Booking::DOMAIN;

        // CSSロード
        $mts_simple_booking->enqueue_style();

        // Javascriptロード
        //wp_enqueue_script("mtssb_mail_template__admin_js", $mts_simple_booking->plugin_url . "js/mtssb-mail-template-admin.js", array('jquery'));
    }

    /**
     * 管理画面メニュー処理
     *
     */
    public function mail_template_page()
    {

        $this->errflg = false;
        $this->message = '';

        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            // モード設定
            $this->mode = empty($_POST['oname']) ? 'new' : 'edit';

            // メールテンプレートが選択されたとき
            if ($action == 'select') {
                if ($this->get_mail_template(intval($_POST['mtssb_tno']))) {
                    $this->mode = 'edit';
                } else {
                    $this->message = 'The template has not been found.';
                    $this->errflg = true;
                }
            }

            // NONCEチェックがOKならテープレートデータを保存する
            elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], self::PAGE_NAME . "_{$this->mode}")) {
                // 入力データをメンバ変数にセットする
                $this->normalize();
                $result = true;
                switch ($action) {
                    case 'new':
                        if (!($result = $this->_add_mail_template())) {
                            $this->message = __('Adding the new template has been failed.', $this->domain);
                        }
                        break;
                    case 'save':
                        if (!($result = $this->_save_mail_template())) {
                            $this->message = __('Saving the template has been failed.', $this->domain);
                        }
                        break;
                    case 'sample':
                        if (!($result = $this->_sample_mail_template())) {
                            $this->message = __('The sample template has been failed to save.', $this->domain);
                        }
                        break;
                    case 'delete':
                        if ($result = $this->_delete_mail_template()) {
                            $this->message = __('The template has been deleted.', $this->domain);
                        } else {
                            $this->message = __('Deleting the template has been failed.', $this->domain);
                        }
                        //break; modeをnewにするためbreakをコメントアウト
                    default:
                        $this->mode = 'new';
                        $this->_clear_mail_template();
                }
                // エラーフラグをセットする
                if (!$result) {
                    $this->errflg = true;
                }

                // 正常保存時のメッセージ設定
                elseif ($action == 'new' || $action == 'save' || $action == 'sample') {
                    $this->mode = 'edit';
                    $this->message = __('The mail template has been saved.', $this->domain);
                }
            }

            // 入力処理対象外
            else {
                $this->mode = 'new';
            }
        }

        ob_start();
?>
    <div class="wrap">
        <h2><?php _e('Mail Template', $this->domain) ?></h2>
        <?php if (!empty($this->message)) : ?>
            <div class="<?php echo ($this->errflg) ? 'error' : 'updated' ?>"><p><strong><?php echo $this->message; ?></strong></p></div>
        <?php endif; ?>

        <?php $this->_select_template_form_out() ?>

        <form id="mail-template" method="post"><?php /* action="?page=<?php echo self::PAGE_NAME ?>"> */ ?>

            <?php $this->_template_form_out() ?>

            <?php if ($this->mode == 'edit') {
                echo sprintf('<button type="submit" class="button-primary" name="action" value="save">%s</button> ', __('Save'));
                echo sprintf('<button type="submit" class="button-primary" name="action" value="delete" onclick="%s">%s</button> ',
                    ("return confirm('" . __('This template will be deleted.', $this->domain) . "');"), __('Delete'));
            } ?>
            <button type="submit" class="button-primary" name="action" value="new"><?php _e('Add new', $this->domain) ?></button>
            <?php // テンプレートが何も登録されていない場合はサンプルテンプレート追加ボタンを追加
                if (empty($this->templates)) {
                    echo '<button type="submit" class="button-primary" name="action" value="sample">' . __("Sample", $this->domain) . '</button>';
                } ?>

            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(self::PAGE_NAME . "_{$this->mode}") ?>">
        </form>

        <?php $this->_footer_variable_out() ?>
    </div><!-- wrap -->

<?php
        ob_end_flush();
    }

    /**
     * メールテンプレートの選択フォーム出力
     *
     */
    private function _select_template_form_out()
    {
        $this->templates = self::list_all();
?>
        <div id="select-mail-template">
            <form method="post"> 
                <table class="form-table" style="width: 100%">
                    <tr class="form-field">
                        <th><?php _e('Select templates', $this->domain) ?></th>
                        <td>
                            <?php if (empty($this->templates)) {
                                echo __('Any template has been not saved yet.', $this->domain);
                            } else {
                                $this->_template_select_box_out();
                                echo sprintf(' <button type="submit" class="button-secondary" name="action" value="select">%s</button>',
                                    __('Select'));
                            } ?>

                        </td>
                    </tr>
                </table>
            </form>
        </div>

<?php
    }

    /**
     * メールテンプレートの選択セレクトボックス
     *
     */
    private function _template_select_box_out()
    {
?>
        <select name="mtssb_tno">
            <?php foreach ($this->templates as $template) :
                echo sprintf('<option value="%s"%s>%s</option>',
                    $template->template_number(),
                    ($this->option_name == $template->option_name ? ' selected="selected"' : ''),
                    $template->mail_subject) ?>
            
            <?php endforeach; ?>
        </select>

<?php
        return;
    }



    /**
     * メール編集テーブルフォーム
     *
     */
    private function _template_form_out()
    {
?>
        <h3><?php _e('Edit mail template', $this->domain) ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="mail-subject"><?php _e('Subject', $this->domain) ?></label>
                </th>
                <td>
                    <input id="mail-subject" class="large-text" type="text" value="<?php echo $this->mail_subject?>" name="mtssb_mail_subject"><br>
                    <?php _e("It's the subject of this mail template.", $this->domain) ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="mail-body"><?php _e('Mail body', $this->domain) ?></label>
                </th>
                <td>
                    <textarea id="mail-body" class="large-text" name="mtssb_mail_body" rows="12"><?php echo $this->mail_body ?></textarea><br>
                    <?php _e("It's the mail main sentence. Using under variables are available in it.", $this->domain) ?>
                </td>
            </tr>
        </table>
        <input type="hidden" name="oname" value="<?php echo $this->template_number() ?>">

<?php
    }

    /**
     * 利用できる変数項目の説明文
     *
     */
    private function _footer_variable_out()
    {
?>
        <p><?php _e("The following variables are available to use in a mail.", $this->domain) ?></p>
        <ul class="ul-description">
            <li>%CLIENT_NAME%</br><?php _e("Reservation application guest's name.", $this->domain) ?></li>
            <li>%RESERVE_ID%</br><?php _e("Reservation ID generated automatically in the booking mail only.", $this->domain) ?></li>
            <li>%NAME%</br><?php _e("Shop Name", $this->domain) ?></li>
            <li>%POSTCODE%</br><?php _e("Post Code", $this->domain) ?></li>
            <li>%ADDRESS%</br><?php _e("Address", $this->domain) ?></li>
            <li>%TEL%</br><?php _e("TEL Number", $this->domain) ?></li>
            <li>%FAX%</br><?php _e("FAX Number", $this->domain) ?></li>
            <li>%EMAIL%</br><?php _e("E-Mail", $this->domain) ?></li>
            <li>%WEB%</br><?php _e("Web Site", $this->domain) ?></li>
        </ul>

<?php
    }
    

    /**
     * 入力データをプロパティにセットする
     *
     */
    private function normalize()
    {
        // オプション名
        if ($this->mode == 'edit' && $_POST['oname']) {
            $this->option_name = $this->domain . MTSSB_Mail_Template::TEMPLATE . $_POST['oname'];
        } else {
            $this->option_name = '';
        }
    
        $this->mail_subject = $_POST['mtssb_mail_subject'];
        $this->mail_body = $_POST['mtssb_mail_body'];
    }


    /**
     * メールテンプレートの新規登録
     *
     */
    private function _add_mail_template()
    {
        global $wpdb;

        // 最新登録のテンプレートを取得する
        $sql = $wpdb->prepare(
            "SELECT * FROM " . $wpdb->options . "
            WHERE option_name LIKE %s
            ORDER BY option_id DESC;", $this->domain . self::TEMPLATE . '%');
        $row = $wpdb->get_row($sql);

        // 最新テンプレートの番号を求める
        $no = 0;
        if ($row) {
            $row->option_value = unserialize($row->option_value);
            $option = $this->_data2template($row);
            $no = $option->template_number();
        }

        // 新規登録のオプションネーム
        $this->option_name = $this->domain . self::TEMPLATE . ($no + 1);

        // 新規オプションデータを登録する
        return add_option($this->option_name,
            array(
                'mail_subject' => $this->mail_subject,
                'mail_body' => $this->mail_body
            ), '', 'no');
    }

    /**
     * メールテンプレートの保存
     *
     */
    private function _save_mail_template()
    {
        global $wpdb;

        // 当該テンプレートを取得する
        $option = $this->_read_mail_template($this->option_name);

        if (!$option) {
            return null;
        }

        // オプションデータを保存する
        return update_option($this->option_name,
            array(
                'mail_subject' => $this->mail_subject,
                'mail_body' => $this->mail_body
            ));
    }

    /**
     * メールテンプレートの削除
     *
     */
    private function _delete_mail_template()
    {
        return delete_option($this->option_name);
    }

    /**
     * メールテンプレートを読み込む
     *
     * @option_name
     */
    private function _read_mail_template($option_name='')
    {
        global $wpdb;

        // オプションデータを読み込む
        $sql = $wpdb->prepare("
            SELECT * FROM " . $wpdb->options . "
            WHERE option_name=%s", $option_name);
        $row = $wpdb->get_row($sql);

        // データが存在すればテンプレートオブジェクトを戻す
        if ($row) {
            $row->option_value = unserialize($row->option_value);
            $option = self::_data2template($row);
            return $option;
        }

        return null;
    }

    /**
     * 当該オブジェクトのテンプレートデータをクリアする
     *
     */
    private function _clear_mail_template()
    {
        $this->option_name = '';
        $this->mail_subject = '';
        $this->mail_body = '';
    }

    /**
     * サンプルメールテンプレートの追加
     *
     */
    private function _sample_mail_template()
    {
        $this->option_name = $this->domain . self::TEMPLATE . '1';
        $this->mail_subject = '予約確認のお知らせ';
        $this->mail_body =
            "%CLIENT_NAME% 様\n"
            . "ご予約ID：%RESERVE_ID%\n\n"
            . "この度はご予約いただきありがとうございました。\n\n"
            . "%CLIENT_NAME%様のお越しをお待ちしております。\n\n"
            . "%NAME%\n"
            . "%POSTCODE%\n"
            . "%ADDRESS%\n"
            . "TEL: %TEL%\n"
            . "E-Mail: %EMAIL%\n"
            . "Webサイト: %WEB%\n";
    
        return $this->_add_mail_template();
    }

}
