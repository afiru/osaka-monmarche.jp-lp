<?php
/**
 * MTS Simple Booking Mail Template メール文テンプレート処理モジュール
 *
 * @Filename    mtssb-mail-template.php
 * @Date        2014-01-13 Ver.1.14.0
 * @Author      S.Hayashi
 *
 */

class MTSSB_Mail_Template
{
    const VERSION = '1.14.0';

    // テンプレートオプション名
    const TEMPLATE = '_templates_';

    protected $domain;

    // 操作対象データ
    private $data = array(
        'option_name' => '',
        'mail_subject' => '',
        'mail_body' => ''
    );


    public function __construct()
    {
        global $mts_simple_booking;

        $this->domain = MTS_Simple_Booking::DOMAIN;
    }

    /**
     * メールテンプレートのサブジェクト一覧を取得する
     *
     */
    public static function list_all()
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM " . $wpdb->options . "
            WHERE option_name LIKE %s
            ORDER BY option_id DESC;", MTS_Simple_Booking::DOMAIN . self::TEMPLATE . '%');
        $rows = $wpdb->get_results($sql);

        if (empty($rows)) {
            return $rows;
        }

        // オプションオブジェクトの配列に変換する
        $options = array();
        foreach ($rows as $row) {
            $row->option_value = unserialize($row->option_value);
            $options[] = self::_data2template($row);
        }

        return $options;
    }

    /**
     * メールテンプレートデータからメールテンプレートオブジェクトに変換して戻す
     *
     */
    protected static function _data2template($data)
    {
        $option = new MTSSB_Mail_Template;

        $option->option_name = $data->option_name;
        $option->mail_subject = $data->option_value['mail_subject'];
        $option->mail_body = $data->option_value['mail_body'];

        return $option;
    }

    /**
     * メールテンプレートオブジェクトの取得
     *
     * @template_no     テンプレート番号
     */
    public function get_mail_template($template_no=0)
    {
        // オプション名の確認
        $option_name = $this->domain . self::TEMPLATE . intval($template_no);

        // オプションデータの取得
        $data = get_option($option_name);
        if (empty($data)) {
            return null;
        }

        $this->option_name = $option_name;
        $this->mail_subject = $data['mail_subject'];
        $this->mail_body = $data['mail_body'];

        return $this;
    }

    /**
     * テンプレート番号を戻す
     *
     */
    public function template_number()
    {
        // option_name の mts_simple_booking_templates_ 以下の数字を戻す
        if (preg_match("/^{$this->domain}" . self::TEMPLATE . "([0-9]+)$/", $this->option_name, $ono)) {
            return $ono[1];
        }

        return '';
    }


    /**
     * プロパティをセットする
     *
     */
    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->data)) {
            switch ($key) {
                default:
            }
            
            $this->data[$key] = $value;
            return $value;
        }
        
        return null;
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
        
        $trace = debug_backtrace();
        trigger_error(sprintf(
            "Undefined property: '%s&' in %s on line %d, E_USER_NOTICE",
            $key, $trace[0]['file'], $trace[0]['line']
        ));
        
        return null;
    }

    /**
     * isset(),empty()アクセス不能プロパティ
     *
     */
    public function __isset($key)
    {
        if (array_key_exists($key, $this->data)) {
            // empty()の場合、__isset()の反転を戻す
            return !empty($this->data[$key]);
        }
        
        return false;
    }

}
