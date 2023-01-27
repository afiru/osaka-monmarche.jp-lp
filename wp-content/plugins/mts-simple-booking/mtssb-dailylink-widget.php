<?php
/**
 * MTS Simple Booking 時間割カレンダーリンクウィジェットモジュール
 *
 * @Filename    mtssb-dailylink-widget.php
 * @Date        2013-11-26 Ver.1.12.0
 * @Author      S.Hayashi
 *
 * Updated to 1.15.4 on 2014-07-15
 */
class MTSSB_Dailylink_Widget extends WP_Widget
{
    const BASE_ID = 'mtssb_dailylink_widget';
    const DAILYLINK = 'mtssb_dailylink_calendar';     // Function name called by ajax

    const JS_PATH = 'js/mtssb-dailylink-widget.js';      // JavaScript file path

    private $domain = '';
    private $plugin_url = '';

    /**
     * Constructor
     *    Register widget with WordPress.
     */
    public function __construct() {
        global $mts_simple_booking;

        //$this->domain = MTS_Simple_Booking::DOMAIN;
        $this->domain = $mts_simple_booking::DOMAIN;
        $this->plugin_url = $mts_simple_booking->plugin_url;

        parent::__construct(
            self::BASE_ID,                    // Base ID
            __('MTS Simple Booking Daily Link', $this->domain),    // Name
            array('description' => __('This is a month mix calendar in the side bar and replace to a daily on the page.', $this->domain))
        );

        // JavaScriptのenqueue
        add_action('wp', array($this, 'enqueue_script'));
    }

    /**
     * Front End AJAX エントリーの登録
     *
     */
    public static function set_ajax_hook() {
        add_action('wp_ajax_' . self::DAILYLINK, array('MTSSB_Dailylink_Widget', 'dailylink_calendar'));
        add_action('wp_ajax_nopriv_' . self::DAILYLINK, array('MTSSB_Dailylink_Widget', 'dailylink_calendar'));
    }

    /**
     * Sanitize widget form values as they are saved
     *
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['post_id'] = intval($new_instance['post_id']);
        $instance['caption'] = intval($new_instance['caption']);
        $instance['pagination'] = intval($new_instance['pagination']);
        $instance['class'] = trim(strip_tags(stripslashes($new_instance['class'])));

        return $instance;
    }

    /**
     * Back-end widget form
     *
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $post_id = empty($instance['post_id']) ? '' : $instance['post_id'];
        $caption = isset($instance['caption']) ? $instance['caption'] : '1';
        $pagination = isset($instance['pagination']) ? $instance['pagination'] : '1';
        $class = empty($instance['class']) ? 'mix-widget-calendar' : $instance['class'];

        $pagination_id = $this->get_field_id('pagination');
        $pagination_name = $this->get_field_name('pagination');
?>
        <p>
            <label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:') ?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" value="<?php echo $title ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_id') ?>"><?php _e('ID:') ?></label><br />
            <input type="text" id="<?php echo $this->get_field_id('post_id') ?>" name="<?php echo $this->get_field_name('post_id') ?>" value="<?php echo $post_id ?>" size="5" /><br />
            <?php _e('The post ID of the page within a mix calendar shortcode.', $this->domain) ?>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('caption') ?>"><?php _e('Display Caption', $this->domain) ?></label><br />
            <input type="hidden" id="<?php echo $this->get_field_id('caption')  . '_' ?>" name="<?php echo $this->get_field_name('caption') ?>" value="0" />
            <input type="checkbox" id="<?php echo $this->get_field_id('caption') ?>" name="<?php echo $this->get_field_name('caption') ?>" value="1"<?php echo $caption ? ' checked' : ''; ?> />
        </p>
        <p>
            <label for="<?php echo $pagination_id . '-1' ?>"><?php _e('Month Link', $this->domain) ?></label><br />
            <?php echo sprintf('<label><input type="radio" id="%s" name="%s" value="1"%s /> %s</label>',
                    $pagination_id . '-1', $pagination_name, ($pagination == 1 ? ' checked' : ''), __('Down side', $this->domain)) . "\n";
                echo sprintf('<label><input type="radio" id="%s" name="%s" value="2"%s /> %s</label>',
                    $pagination_id . '-2', $pagination_name, ($pagination == 2 ? ' checked' : ''), __('Upper side', $this->domain)) . "\n";
                echo sprintf('<label><input type="radio" id="%s" name="%s" value="3"%s /> %s</label>',
                    $pagination_id . '-3', $pagination_name, ($pagination == 3 ? ' checked' : ''), __('Both side', $this->domain)) . "\n";
                echo sprintf('<label><input type="radio" id="%s" name="%s" value="0"%s /> %s</label>',
                    $pagination_id . '-0', $pagination_name, (($pagination < 1 || 3 < $pagination) ? ' checked' : ''), __('Not display', $this->domain)) . "\n";
            ?>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('class') ?>"><?php _e('Class Name', $this->domain) ?></label><br />
            <input type="text" id="<?php echo $this->get_field_id('class') ?>" name="<?php echo $this->get_field_name('class') ?>" value="<?php echo $class ?>" />
        </p>

<?php
    }

    /**
     * Front-end display of widget
     *
     */
    public function widget($args, $instance)
    {
        // ウィジェットに設定されたパラメータを取り出す
        $wparam = $this->ins2param($instance);

        // ショートコードのパラメータを取得する
        $params = self::retrieve_params($wparam);
        if (!is_array($params)) {
            echo $params;
        }

        // ミックス時間割カレンダー表示ページ
        $linkurl = $params['linkurl'] . "#{$params['anchor']}";

        extract($args);

        echo $before_widget;

        if ($instance['title']) {
            echo $before_title . esc_html($instance['title']) . $after_title;
        }

?>
        <div class="mtssb-dailylink-calendar" style="position: relative">
            <div class="wrap-<?php echo $params['class'] ?>">
                <?php echo self::mix_calendar($params) ?>
            </div>

            <div class="ajax-calendar-loading-img" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%">
                <img src="<?php echo $this->plugin_url . "image/ajax-loaderf.gif" ?>" style="height:24px; width:24px; position:absolute; top:50%; left:50%; margin-top:-12px; margin-left:-12px;" />
            </div>
        </div>

        <div class="mtssb-dailylink-params" style="display:none">
            <div class="mtssb-nonce"><?php echo wp_create_nonce(self::DAILYLINK) ?></div>
            <div class="mtssb-ajaxurl"><?php echo admin_url('admin-ajax.php') ?></div>
            <div class="mtssb-param"><?php echo urlencode(serialize($params)) ?></div>
            <div class="mtssb-class"><?php echo $params['class'] ?></div>
            <div class="mtssb-mix-calendar"><?php echo $linkurl ?></div>
        </div>
<?php

        echo $after_widget;
    }

    /**
     * ウィジェットで設定されたショートコードパラメータを戻す
     *
     * @param $instance
     * @return array
     */
    private function ins2param($instance)
    {
        return array(
            'post_id' => intval($instance['post_id']),          // ショートコードを埋め込んだページID
            'class' => $instance['class'],                      // ウィジェット上の書き換えクラス名
            'caption' => intval($instance['caption']),          // キャプション表示指定
            'pagination' => intval($instance['pagination']),    // 前翌月リンク表示位置
        );
    }

    /**
     * 指定ページで設定されたショートコードのパラメータを取得してウィジェット設定に合わせる
     *
     */
    public static function retrieve_params($wparam) //post_id, $classname)
    {
        // ミックスカレンダーを指定したページを取得する
        $post = get_post($wparam['post_id']);
        if (empty($post)) {
            return self::error_message("POST_NOT_FOUND");
        }

        // ショートコードを取り出す正規表現
        //$regex = get_shortcode_regex();
        $regex = "\[(\[?)(mix_calendar)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)";

        // ショートコードのパラメータを取り出す
        if (preg_match_all('/' . $regex . '/', $post->post_content, $matches)
            && array_key_exists(2, $matches) && in_array('mix_calendar', $matches[2])) {
            $key = array_search('mix_calendar', $matches[2]);
            $params = shortcode_parse_atts($matches[3][$key]);
        } else {
            return self::error_message("SHORTCODE_NOT_FOUND");
        }

        // 本ウィジェット用のパラメータ設定を合わせる
        $params = array_merge($params, $wparam);
        $params['anchor'] = isset($params['anchor']) ? $params['anchor'] : 'mix-anchor';
        $params['widget'] = 1;
        $params['linkurl'] = get_permalink($params['post_id']);

        return $params;
    }

    /**
     * When this widget is activated, set including javascript
     *
     */
    public function enqueue_script() {
        if (is_active_widget(false, false, self::BASE_ID)) {
            wp_enqueue_script(self::BASE_ID . '_js', $this->plugin_url . self::JS_PATH, array('jquery'));
        }
    }


    private static function mix_calendar($params)
    {
        if (!class_exists('MTSSB_Front_Freak')) {
            require_once('mtssb-front-freak.php');
        }

        $oFrontFreak = new MTSSB_Front_Freak;

        return $oFrontFreak->mix_calendar($params);
    }

    /**
     * Ajax入り口
     *
     */
    public static function dailylink_calendar() {
        global $mts_simple_booking;

        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], self::DAILYLINK)) {
            exit();
        }

        // ショートコードのパラメータを取得する
        $wparam = unserialize(urldecode($_POST['param']));
        $params = self::retrieve_params($wparam);
        if (!is_array($params)) {
            header("Content-type: text/plain; charset=UTF-8");
            exit($params);
        }

        // カレンダーを戻す
        header("Content-type: text/html; charset=UTF-8");
        $ret = self::mix_calendar($params);
        exit($ret);
    }

    /**
     * エラーメッセージ
     *
     */
    private static function error_message($errcode)
    {
        $messages = array(
            'POST_NOT_FOUND' => 'ページが見つかりませんでした.',
            'SHORTCODE_NOT_FOUND' => 'ショートコードがありません。',
        );

        if (array_key_exists($errcode, $messages)) {
            return $messages[$errcode];
        }

        return "Error.";
    }

}
