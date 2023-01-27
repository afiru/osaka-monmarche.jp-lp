<?php
if (!class_exists('MTSSB_Booking')) {
    require_once(dirname(__FILE__) . '/mtssb-booking.php');
}

/**
 * MTS Simple Booking Booking 予約登録・編集モジュール
 *
 * @Filename	mtssb-booking-admin.php
 * @Date		2012-04-30
 * @Author		S.Hayashi
 *
 * Updated to 1.31.0 on 2019-07-03
 * Updated to 1.28.0 on 2017-12-13
 * Updated to 1.27.0 on 2017.08-02
 * Updated to 1.21.0 on 2015-01-06
 * Updated to 1.17.1 on 2014-09-04
 * Updated to 1.17.0 on 2014-07-13
 * Updated to 1.15.0 on 2014-04-12
 * Updated to 1.9.0 on 2013-07-17
 * Updated to 1.8.5 on 2013-07-04
 * Updated to 1.7.0 on 2013-05-11
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.4.5 on 2013-02-21
 * Updated to 1.4.0 on 2013-01-30
 * Updated to 1.3.0 on 2013-01-25
 * Updated to 1.1.5 on 2012-12-04
 * Updated to 1.1.0 on 2012-10-03
 * Updated to 1.0.1 on 2012-09-14
 */
class MTSSB_Booking_Admin extends MTSSB_Booking {

    const PAGE_NAME = 'simple-booking-booking';
    const LOADER_ICON = 'image/ajax-loader.gif';

    private static $iBooking = null;
    // 読み込んだ予約品目データ
    private $article = null;  // 予約品目
    // 各種設定
    private $premise = array();
    private $message = '';
    private $errflg = false;

    /**
     * インスタンス化
     *
     */
    static function get_instance() {
        if (!isset(self::$iBooking)) {
            self::$iBooking = new MTSSB_Booking_Admin();
        }

        return self::$iBooking;
    }

    public function __construct() {
        global $mts_simple_booking;

        parent::__construct();

        // CSSロード
        $mts_simple_booking->enqueue_style();
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Javascriptロード
        wp_enqueue_script("mtssb_booking_admin_js", $this->plugin_url . "js/mtssb-booking-admin.js", array('jquery', 'jquery-ui-dialog'));

        // 郵便番号検索の指定
        $this->premise = get_option($this->domain . '_premise');
        if ($this->premise['zip_search'] == 1) {
            wp_enqueue_script('mts_assistance', $this->plugin_url . 'js/mts-assistance.js');
        } elseif ($this->premise['zip_search'] == 2) {
            wp_enqueue_script('postcodejp', 'https://postcode-jp.com/js/postcodejp.js');
            wp_enqueue_script('mts_postcodejp', $this->plugin_url . 'js/mts-postcodejp.js');
        }


        //$zipSearch = isset($premise['zip_search']) ? $premise['zip_search'] : 0;
        $apiKey = isset($premise['api_key']) ? $premise['api_key'] : '';
        //if ($zipSearch == 1) {
        //    wp_enqueue_script('mts_assistance', $mts_simple_booking->plugin_url . self::JS_ASSISTANCE);
        //} elseif ($zipSearch == 2) {
        //    wp_enqueue_script('postcodejp', self::POSTCODEJP);
        //    wp_enqueue_script('mts_postcodejp', $mts_simple_booking->plugin_url . self::JS_POSTCODEJP);
        //}
    }

    /**
     * 管理画面メニュー処理
     *
     */
    public function booking_page() {

        $this->errflg = false;
        $this->message = '';

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        // 予約品目、予約日付の入力初期画面
        if (empty($action) && !isset($_GET['article_id'])) {
            $this->_select_article_page();
            return;
        }

        if (isset($_POST['action'])) {

            if (!wp_verify_nonce($_POST['nonce'], self::PAGE_NAME . "_{$action}")) {
                die("Nonce error!");
            }

            // 予約データを正規化し、登録データを取得する
            $this->booking = $this->normalize_booking($_POST['booking']);

            switch ($action) {
                case 'add' :
                    try {
                        $booking_id = $this->add_series_booking();
                        $this->message = __('Booking data has been added.', $this->domain);
                    } catch (Exception $e) {
                        $this->message = __('Booking data has been failed to add.', $this->domain);
                        $this->errflg = true;
                        $this->_select_article_page();
                        return;
                    }
                    break;
                case 'save' :
                    try {
                        $booking_id = $this->save_series_booking();
                        $this->message = __('Booking data has been saved.', $this->domain);
                    } catch (Exception $e) {
                        $this->message = __('Booking data has been failed to save.', $this->domain);
                        $this->errflg = true;
                    }
                    break;
                default :
                    break;
            }
        } else if ($action == 'edit') {
            // 指定予約データを読み込む
            $this->booking = $this->get_booking(intval($_REQUEST['booking_id']));
        } else {
            // 指定された予約品目ID
            $article_id = intval($_GET['article_id']);
            // 指定された予約日時
            if (isset($_GET['dt'])) {
                $daytime = isset($_GET['dt']) ? intval($_GET['dt']) : 0;
            } else if (isset($_GET['bd'])) {
                $daytime = mktime(0, 0, 0, $_GET['bd']['month'], $_GET['bd']['day'], $_GET['bd']['year']) + $_GET['timetable'];
            }

            $this->booking = $this->new_booking($daytime, $article_id);
        }

        // 予約品目を取得する
        $this->article = MTSSB_Article::get_the_article($this->booking['article_id']);
        if (empty($this->article)) {
            $this->_select_article_page();
            return;
        }

        $action = $this->booking['booking_id'] ? 'save' : 'add';
        ?>
        <div class="wrap columns-2">
            <h2><?php echo $action == 'save' ? __('Edit Booking', $this->domain) : __('Add Booking', $this->domain) ?></h2>
        <?php if (!empty($this->message)) : ?>
                <div class="<?php echo ($this->errflg) ? 'error' : 'updated' ?>"><p><strong><?php echo $this->message; ?></strong></p></div>
        <?php endif; ?>

            <form id="add-booking" method="post" action="?page=<?php echo self::PAGE_NAME ?>">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">

                        <div id="post-body-content">
        <?php $this->_postbox_booking() ?>
                        </div>

                        <div id="postbox-container-1" class="postbox-container">
                            <div id="addsubmitdiv" class="postbox">
                                <h3><?php echo $action == 'save' ? __('Edit Booking', $this->domain) : __('Add Booking', $this->domain) ?></h3>
                                <div id="minor-publishing">
                                    <div id="misc-publishing-actions">
                                        <div class="misc-pub-section">
                                            <label for="booking-confirmed"><?php _e('Booking Confirmation:', $this->domain) ?></label>
                                            <input type="hidden" name="booking[confirmed]" value="<?php echo (int) $this->booking['confirmed'] & ~1 ?>" />
                                            <input id="booking-confirmed" type="checkbox" name="booking[confirmed]" value="<?php echo (int) $this->booking['confirmed'] | 1 ?>"<?php echo (int) $this->booking['confirmed'] & 1 ? ' checked="checked"' : '' ?> />
                                        </div>
                                    </div>
                                </div>
                                <div id="major-publishing-actions">
        <?php if ($action == 'save') : ?><div id="delete-action">
                                            <a href="?page=simple-booking-list&amp;booking_id=<?php echo $this->booking['booking_id'] ?>&amp;action=delete&amp;nonce=<?php echo wp_create_nonce('simple-booking-list_delete') ?>" onclick="return confirm('<?php _e('Do you really want to delete this booking?', $this->domain) ?>')"><?php _e('Delete') ?></a>
                                        </div><?php endif; ?>
                                    <div id="publishing-action">
                                        <input id="publish" class="button-primary" type="button" name="save" value="<?php echo $action == 'save' ? __('Save Booking', $this->domain) : __('Add Booking', $this->domain) ?>" onclick="<?php echo sprintf('booking_admin.checkRemain(%d)', $this->article['article_id']) ?>">
                                        <input type="hidden" id="ajax-nonce" value="<?php echo wp_create_nonce('mtssb_booking_count') ?>" />
                                    </div>
                                    <div class="clear"> </div>
                                    <div id="ajax-checking-saving" style="display:none">予約データ確認中…</div>
                                </div>
                            </div>
                        </div>

                        <div id="postbox-container-2" class="postbox-container">
        <?php if ($this->article['addition']->isOption() && $this->article['addition']->position == 0) {
            $this->_postbox_options();
        } ?>
        <?php $this->_postbox_client() ?>
        <?php if ($this->article['addition']->isOption() && $this->article['addition']->position == 1) {
            $this->_postbox_options();
        } ?>
                            <?php $this->_postbox_note() ?>
                        </div>

                    </div>
                </div>
                <input type="hidden" name="booking[user_id]" value="<?php echo $this->booking['user_id'] ?>" />
                <input type="hidden" name="action" value="<?php echo $action ?>" />
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(self::PAGE_NAME . "_{$action}") ?>" />
            </form>

            <div id="booking-info-dialog" style="display:none"> </div>
        </div><!-- wrap -->

        <?php
        return;
    }

    /**
     * 予約データ入力フォーム postbox
     *
     */
    private function _postbox_booking() {
        $odate = new MTS_WPDate;
        ?>
        <div class="postbox">
            <h3><?php _e('Booking Data', $this->domain) ?></h3>
            <div class="inside">
                <table class="form-table" style="width: 100%">
                    <tr class="form-field">
                        <th>
        <?php _e('Booking Date', $this->domain) ?>
                        </th>
                        <td>
                            <input type="hidden" name="booking[booking_id]" value="<?php echo $this->booking['booking_id'] ?>" />
                            <?php echo $odate->set_time($this->booking['booking_time'])->date_form('booking_time', 'booking') ?>
                            <a href="#" class="button-secondary" onclick="booking_admin.infoRemain(<?php echo $this->booking['article_id'] ?>); return false;">予約確認</a>
                            <span id="loader-img" style="display:none"><img src="<?php echo $this->plugin_url . self::LOADER_ICON ?>" alt="Loading" /></span>
                            <input type="hidden" id="ajax-nonce" value="<?php echo wp_create_nonce('mtssb_booking_count') ?>" />
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th>
        <?php _e('Booking Event', $this->domain) ?>
                        </th>
                        <td>
                            <input type="hidden" name="booking[article_id]" value="<?php echo $this->booking['article_id'] ?>" />
                            <?php echo $this->article['name'] ?>
                            <!--<select id="booking-time" class="booking-select-time" name="booking[timetable]">
        <?php
        $timetable = $this->booking['booking_time'] % 86400;
        if (empty($this->article['timetable'])) {
            echo '<option value="">' . __('Nothing', $this->domain) . "</option>\n";
        } else {
            foreach ($this->article['timetable'] as $time) {
                echo "<option value=\"$time\"" . ($timetable == $time ? ' selected="selected"' : '') . ">" . date('H:i', $time) . "</option>\n";
            }
        }
        ?>
                            </select>-->
                            <input id="booking-time-old" type="hidden" value="<?php echo $this->booking['booking_time'] ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
        <?php _e('Attendance', $this->domain) ?>
                        </th>
                        <td>
                            <input id="booking-attendance" class="small-text" type="text" name="booking[number]" value="<?php echo $this->booking['number'] ?>"> 人
                            <input id="booking-attendance-old" type="hidden" value="<?php echo $this->booking['number'] ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
    }

    /**
     * 予約オプション入力フォーム postbox
     *
     */
    private function _postbox_options() {
        $odate = new MTS_WPDate;
        $opt_number = $this->option->count_option();
        ?>
        <div class="postbox">
            <h3><?php _e('Select Options', $this->domain) ?></h3>
            <div class="inside">
        <?php if (empty($opt_number)) : ?><p>
            <?php _e('The option which can be chosen has nothing.', $this->domain) ?>
                    </p><?php else : ?>

                    <table class="form-table" style="width: 100%">
                    <?php for ($i = 0; $i < $opt_number; $i++) : $option = $this->booking['options'][$i]; ?><tr>
                                <th>
                                    <label for="option_<?php echo $option->getType() . '_' . $option->getKeyname() ?>"><?php echo $option->getLabel() ?></label>
                                </th>
                                <td><?php switch ($option->getType()) :
                    case 'number':
                                ?>
                                            <input id="option_number_<?php echo $option->getKeyname() ?>" type="text" name="booking[options][<?php echo $option->getKeyname() ?>]" class="small-text" value="<?php echo $option->getValue() ?>" /> (0 | <?php echo $option->getPrice() ?>)
                        <?php break;
                    case 'text':
                        ?>
                                            <input id="option_text_<?php echo $option->getKeyname() ?>" class="mts-fat" type="text" name="booking[options][<?php echo $option->getKeyname() ?>]" class="mts-fat" value="<?php echo $option->getValue() ?>" /> (0 | <?php echo $option->getPrice() ?>)
                                            <?php break;
                                        case 'textarea':
                                            ?>
                                            <textarea id="option_textarea_<?php echo $option->keyname ?>" class="mts-fat" name="booking[options][<?php echo $option->keyname ?>]" rows="8" cols="50"><?php echo esc_textarea($option->getValue()) ?></textarea> (0 | <?php echo $option->getPrice() ?>)
                                            <?php
                                            break;
                                        case 'radio':
                                            foreach ($option->getField() as $fieldname => $val) :
                                                ?>
                                                <label class="field-item">
                                                    <input id="option_radio_<?php echo $fieldname ?>" type="radio" name="booking[options][<?php echo $option->getKeyname() ?>]" value="<?php echo $fieldname ?>"<?php echo $fieldname == $option->getValue() ? ' checked="checked"' : '' ?>> <?php echo $val['label'] . " ({$val['time']} | {$val['price']})" ?>
                                                </label><br />
                                            <?php
                                            endforeach;
                                            break;
                                        case 'select':
                                            ?>
                                            <select id="option_select_<?php echo $option->getKeyname() ?>" name="booking[options][<?php echo $option->getKeyname() ?>]">
                                                <option value=""> </option>
                                                <?php foreach ($option->getField() as $fieldname => $val) : ?>
                                                    <option value="<?php echo $fieldname ?>"<?php echo $fieldname == $option->getValue() ? ' selected="selected"' : '' ?>><?php echo $val['label'] . " ({$val['time']} | {$val['price']})" ?></option>
                                            <?php endforeach; ?>
                                            </select>
                                            <?php
                                            break;
                                        case 'check':
                                            foreach ($option->getField() as $fieldname => $val) :
                                                ?>
                                                <input id="option_check_<?php echo $fieldname ?>_" type="hidden" name="booking[options][<?php echo $option->getKeyname() ?>][<?php echo $fieldname ?>]" value="0" />
                                                <label class="field-item">
                                                    <input id="option_check_<?php echo $fieldname ?>" type="checkbox" name="booking[options][<?php echo $option->getKeyname() ?>][<?php echo $fieldname ?>]" value="1"<?php echo $option->isChecked($fieldname) ? ' checked="checked"' : '' ?> /> <?php echo $val['label'] . " ({$val['time']} | {$val['price']})" ?>
                                                </label><br />
                                            <?php
                                            endforeach;
                                            break;
                                        case 'date':
                                            echo $odate->set_time($option->getValue())->date_form('booking-options-' . $option->getKeyname(), 'booking[options][' . $option->getKeyname() . ']') . ' (0 | ' . $option->getPrice() . ')';
                                            break;
                                        case 'time':
                                            $otime = new MTS_WPTime($option->getValue());
                                            echo $otime->time_form($option->keyname, 'booking[options]') . ' (0 | ' . $option->getPrice() . ')';
                                            break;
                                        default:
                                            break;
                                    endswitch;
                                    ?></td>
                            </tr><?php endfor; ?>
                    </table><?php endif; ?>
            </div>
        </div>

        <?php
    }

    /**
     * 予約者情報入力フォーム postbox
     *
     */
    private function _postbox_client() {
        $client = &$this->booking['client'];
        ?>
        <div class="postbox">
            <h3><?php _e('Client Information', $this->domain) ?></h3>
            <div class="inside">
                <table class="form-table" style="width: 100%">
                    <tr>
                        <th>
                            <label for="booking-company"><?php _e('Company', $this->domain) ?></label>
                        </th>
                        <td>
                            <input id="booking-company" class="mts-fat" type="text" name="booking[client][company]" value="<?php echo esc_attr($client['company']) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-name"><?php _e('Name') ?></label>
                        </th>
                        <td>
                            <div class="display_flex_center name_contents_in_form">
                                <label class="booking-seimei" for="booking-sei"><?php _e('Family Name', $this->domain) ?></label>
                                <input id="booking-sei" class="mts-small" type="text" name="booking[client][sei]" value="<?php echo esc_attr($client['sei']) ?>" />
                                <label class="booking-seimei" for="booking-mei"><?php _e('First Name', $this->domain) ?></label>
                                <input id="booking-mei" class="mts-small" type="text" name="booking[client][mei]" value="<?php echo esc_attr($client['mei']) ?>" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-furigana"><?php _e('Furigana', $this->domain) ?></label>
                        </th>
                        <td>
                            <div class="display_flex_center name_contents_in_form">
                                <label class="booking-seimei" for="booking-sei_kana"><?php _e('Family Name Roman', $this->domain) ?></label>
                                <input id="booking-sei_kana" class="mts-small" type="text" name="booking[client][sei_kana]" value="<?php echo esc_attr($client['sei_kana']) ?>" />
                                <label class="booking-seimei" for="booking-mei_kana"><?php _e('First Name Roman', $this->domain) ?></label>
                                <input id="booking-mei_kana" class="mts-small" type="text" name="booking[client][mei_kana]" value="<?php echo esc_attr($client['mei_kana']) ?>" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-birthday"><?php _e('Birthday', $this->domain) ?></label>
                        </th>
                        <td>
        <?php echo $client['birthday']->date_form('booking-birthday', 'booking[client][birthday]', 0, 100, true) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-gender"><?php _e('Gender', $this->domain) ?></label>
                        </th>
                        <td>
                            <label class="field-item"><input id="booking-gender-unnecessary" type="radio" name="booking[client][gender]" value=""<?php echo empty($client['gender']) ? ' checked="checked"' : '' ?> /><?php _e('Unnecessary', $this->domain) ?></label>
                            <label class="field-item"><input id="booking-gender-female" type="radio" name="booking[client][gender]" value="female"<?php echo $client['gender'] == 'female' ? ' checked="checked"' : '' ?> /><?php _e('Female', $this->domain) ?></label>
                            <label class="field-item"><input id="booking-gender-male" type="radio" name="booking[client][gender]" value="male"<?php echo $client['gender'] == 'male' ? ' checked="checked"' : '' ?> /><?php _e('Male', $this->domain) ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-email">E-Mail</label>
                        </th>
                        <td>
                            <input id="booking-email" class="mts-fat" type="text" name="booking[client][email]" value="<?php echo esc_attr($client['email']) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-postcode"><?php _e('Postcode', $this->domain) ?></label>
                        </th>
                        <td>
                            <input id="booking-postcode" class="mts-small" type="text" name="booking[client][postcode]" value="<?php echo esc_attr($client['postcode']) ?>" />
                            <button id="mts-postcode-button" type="button" class="button-secondary" onclick="mts_assistance.findByPostcode('<?php echo $this->premise['api_key'] ?>', 'booking-postcode', 'booking-address1')" data-api_key="<?php echo $this->premise['api_key'] ?>" data-postcode="booking-postcode" data-address="booking-address1">検索</button>

                            <img id="booking-postcode-loading" src="<?php echo $this->plugin_url . self::LOADER_ICON ?>" style="display:none" alt="Loading...">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-address1"><?php _e('Address', $this->domain) ?></label>
                        </th>
                        <td>
                            <input id="booking-address1" class="mts-fat" type="text" name="booking[client][address1]" value="<?php echo esc_attr($client['address1']) ?>" /><br />
                            <input id="booking-address2" class="mts-fat" type="text" name="booking[client][address2]" value="<?php echo esc_attr($client['address2']) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-tel"><?php _e('Phone number', $this->domain) ?></label>
                        </th>
                        <td>
                            <input id="booking-tel" class="mts-middle" type="text" name="booking[client][tel]" value="<?php echo esc_attr($client['tel']) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-newuse"><?php echo apply_filters('booking_form_newuse', __('Newuse', $this->domain), 'admin'); ?></label>
                        </th>
                        <td>
                            <label class="field-item"><input id="booking-newuse-unnecessary" type="radio" name="booking[client][newuse]" value="0"<?php echo empty($client['newuse']) ? ' checked="checked"' : '' ?> /><?php _e('Unnecessary', $this->domain) ?></label>
                            <label class="field-item"><input id="booking-newuse-yes" type="radio" name="booking[client][newuse]" value="1"<?php echo $client['newuse'] == 1 ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_newuse_yes', __('Yes')) ?></label>
                            <label class="field-item"><input id="booking-newuse-no" type="radio" name="booking[client][newuse]" value="2"<?php echo $client['newuse'] == 2 ? ' checked="checked"' : '' ?> /><?php echo apply_filters('booking_form_newuse_no', __('No')) ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-adult"><?php _e('Numbers', $this->domain) ?></label>
                        </th>
                        <td>
                            <div class="human_genre_container">
                                <div class="display_flex_center human_genre_contents">
                                    <span class="title_human_genre_contents">大人</span>
                                    <input id="booking-adult" class="small-text" type="text" name="booking[client][adult]" value="<?php echo esc_attr($client['adult']) ?>" />
                                    <span class="title_human_genre_contents_many">人</span>
                                </div>
                                <div class="display_flex_center human_genre_contents">
                                    <span class="title_human_genre_contents">子供</span>
                                    <input class="small-text" type="text" name="booking[client][child]" value="<?php echo esc_attr($client['child']) ?>" />
                                    <span class="title_human_genre_contents_many">人</span>
                                </div>
                                <div class="display_flex_center human_genre_contents">
                                    <span class="title_human_genre_contents">幼児</span>
                                    <input class="small-text" type="text" name="booking[client][baby]" value="<?php echo esc_attr($client['baby']) ?>" />
                                    <span class="title_human_genre_contents_many">人</span>
                                </div>
                                <div class="display_flex_center human_genre_contents">
                                    <span class="title_human_genre_contents">車</span>
                                    <input class="small-text" type="text" name="booking[client][car]" value="<?php echo esc_attr($client['car']) ?>" />
                                    <span class="title_human_genre_contents_many">台</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="booking-transaction_id"><?php _e('Transaction ID', $this->domain) ?></label>
                        </th>
                        <td>
                            <input id="booking-transaction_id" class="mts-middle" type="text" name="booking[client][transaction_id]" value="<?php echo esc_attr($client['transaction_id']) ?>" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
    }

    /**
     * メッセージ等注記 postbox
     *
     */
    private function _postbox_note() {
        ?>
        <div class="postbox">
            <h3><?php _e('Note', $this->domain) ?></h3>
            <div class="inside">
                <table class="form-table" style="width: 100%">
                    <tr class="form-field">
                        <th>
                            <label for="booking-note"><?php _e('Note', $this->domain) ?></label>
                        </th>
                        <td>
                            <textarea id="booking-note" name="booking[note]" rows="8" cols="50"><?php echo esc_textarea($this->booking['note']) ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
    }

    /**
     * 新規予約登録 予約品目の選択
     *
     */
    private function _select_article_page() {
        // 予約品目
        $articles = MTSSB_Article::get_all_articles();

        // 日付フォームオブジェクト生成、初期日付をセット
        $odate = new MTS_WPDate;
        $odate->set_date(date_i18n('Y-n-j'));

        $article_id = key($articles);
        ?>
        <div class="wrap">
            <h2><?php _e('Add Booking', $this->domain) ?></h2>
        <?php if (!empty($this->message)) : ?>
                <div class="<?php echo ($this->errflg) ? 'error' : 'updated' ?>"><p><strong><?php echo $this->message; ?></strong></p></div>
        <?php endif; ?>

            <h3><?php _e('Select article', $this->domain) ?></h3>
            <form id="select-article" method="get" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                <input type="hidden" name="page" value="<?php echo self::PAGE_NAME ?>" />

                <table class="form-table" style="width: 100%">
                    <tr class="form-field">
                        <th>
                            <?php _e('Booking Date', $this->domain) ?>
                        </th>
                        <td>
        <?php echo $odate->date_form('booking_new', 'bd') ?>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th>
                                <?php _e('Booking Event', $this->domain) ?>
                        </th>
                        <td>
                            <select id="booking-article" class="booking-select-article" name="article_id">
        <?php
        foreach ($articles as $aid => $article) {
            echo "<option value=\"$aid\">{$article['name']}</option>\n";
        }
        ?>
                            </select>
                            <select id="booking-time" class="booking-select-time" name="timetable">
                                <?php
                                if (empty($articles[$article_id]['timetable'])) {
                                    echo '<option value="">' . __('Nothing', $this->domain) . "</option>\n";
                                } else {
                                    foreach ($articles[$article_id]['timetable'] as $time) {
                                        echo "<option value=\"$time\">" . date('H:i', $time) . "</option>\n";
                                    }
                                }
                                ?>
                            </select>
                            <span id="loader-img" style="display:none"><img src="<?php echo $this->plugin_url . 'image/ajax-loader.gif' ?>" alt="Loading" /></span>
                            <input type="hidden" id="ajax-nonce" value="<?php echo wp_create_nonce('mtssb_get_timetable') ?>">
                        </td>
                    </tr>
                </table>

                <p><input class="button-secondary" type="submit" value="<?php _e('Add booking', $this->domain) ?>" /></P>
            </form>
        </div>

        <?php
    }

    /**
     * 指定日付、予約品目の予約状況
     */
    public function booking_day_info($day_time, $article_id, $method) {
        $dayTime = $day_time - $day_time % 86400;

        // 指定日の予約数データを取得する
        $count = $this->get_reserved_day_count($dayTime);

        // 予約品目データを取得する
        $article = MTSSB_Article::get_the_article(intval($article_id));

        // 対象年月のスケジュールを読込む
        $key_name = MTS_Simple_Booking::SCHEDULE_NAME . date_i18n('Ym', $dayTime);
        $schedule = get_post_meta($article_id, $key_name, true);

        $day = date_i18n('d', $dayTime);

        // 時間毎の予約数
        $bookingTable = $this->_setCount($article_id, $article['restriction'], $article['timetable'], $dayTime, $count);

        // 保存の際のチェック用予約数データ要求
        if ($method == 'data') {
            // 予約数の設定

            return array(
                'day_time' => $dayTime,
                'booking_table' => $bookingTable,
                'restriction' => $article['restriction'],
                'max' => intval($article[$article['restriction']]),
                'open' => isset($schedule[$day]['open']) ? intval($schedule[$day]['open']) : -1,
                'delta' => isset($schedule[$day]['delta']) ? intval($schedule[$day]['delta']) : 0,
            );
        }

        ob_start();

        echo sprintf('<h3>%s</h3>', $article['name']);

        // 予約日
        echo sprintf('<p><span class="booking-admin-header">予約日</span>：%s</p>',
                date_i18n('Y年n月j日 (D)', $dayTime));

        // 受付数
        echo sprintf('<p><span class="booking-admin-header">上限数</span>：%d</p>',
                $article[$article['restriction']]);

        // スケジュール
        echo '<p><span class="booking-admin-header">スケジュール</span>：';
        if (isset($schedule[$day]['open'])) {
            echo sprintf('予約 [%s]　調整数 [%d]　注記 [%s]',
                    $schedule[$day]['open'] ? '受付中' : '中止', $schedule[$day]['delta'],
                    empty($schedule[$day]['note']) ? 'なし' : $schedule[$day]['note']);
        } else {
            echo '未設定です。';
        }
        echo '</p>';

        // 時間割予約済み数
        echo '<table>';
        echo ('<tr><th class="booking-admin-time">時間</th><th class="booking-admin-count">予約数</th></tr>');

        foreach ($bookingTable as $hour => $number) {
            echo sprintf('<tr><td>%s</td><td>%d</td></tr>', date('H:i', $hour), $number);
        }

        return ob_get_clean();
    }

    // 予約数を時間毎にセットする
    private function _setCount($articleId, $restriction, $timetable, $dayTime, $count) {
        $counts = array();

        foreach ($timetable as $hour) {
            $bookingTime = $dayTime + $hour;

            if (isset($count[$bookingTime][$articleId])) {
                $booking = $count[$bookingTime][$articleId];
                $counts[$hour] = intval($restriction == 'capacity' ? $booking['number'] : $booking['count']);
            } else {
                $counts[$hour] = 0;
            }
        }

        return $counts;
    }

}
