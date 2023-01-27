<?php
/*
Plugin Name: MTS Simple Booking
Plugin URI:http://mtssb.mt-systems.jp/
Description: 予約対象に時間割を設定して予約受付処理をする汎用簡易予約処理システムです。予約数を管理し、オンラインで予約を受付けメールで管理者へお知らせします。PHP Ver.7、WordPress Ver.5.0以降で動作させて下さい。
Version: 1.33.1
Author: S.Hayashi
Author URI: http://web.mt-systems.jp
*/
/*  Copyright 2012 - 2020 S.Hayashi

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, see <http://www.gnu.org/licenses/>.
*/
/*
 * Updated to 1.33.1 on 2020-07-25
 * Updated to 1.33.0 on 2020-04-17
 * Updated to 1.32.2 on 2020-01-07
 * Updated to 1.32.1 on 2019-12-13
 * Updated to 1.32.0 on 2019-10-31
 * Updated to 1.31.0 on 2019-05-17
 * Updated to 1.30.0 on 2019-02-07
 * Updated to 1.29.1 on 2019-01-10
 * Updated to 1.29.0 on 2018-03-28
 * Updated to 1.28.3 on 2018-03-01
 * Updated to 1.28.2 on 2018-02-27
 * Updated to 1.28.1 on 2018-01-25
 * Updated to 1.28.0 on 2017-10-26
 * Updated to 1.27.0 on 2017-08-02
 * Updated to 1.26.0 on 2017-04-27
 * Updated to 1.25.0 on 2016-10-27
 * Updated to 1.24.0 on 2016-07-27
 * Updated to 1.23.2 on 2016-06-29
 * Updated to 1.23.1 on 2016-02-09
 * Updated to 1.23.0 on 2015-11-24
 * Updated to 1.22.0 on 2015-06-30
 * Updated to 1.21.0 on 2014-12-22
 * Updated to 1.20.0 on 2014-11-28
 * Updated to 1.19.0 on 2014-10-28
 * Updated to 1.18.0 on 2014-09-30
 * Updated to 1.17.1 on 2014-09-04
 * Updated to 1.17.0 on 2014-07-11
 * Updated to 1.16.0 on 2014-06-09
 * Updated to 1.15.1 on 2014-05-16
 * Updated to 1.15.0 on 2014-05-02
 * Updated to 1.14.0 on 2014-01-15
 * Updated to 1.13.0 on 2014-01-04
 * Updated to 1.12.0 on 2013-11-19
 * Updated to 1.11.0 on 2013-10-28
 * Updated to 1.10.0 on 2013-10-14
 * Updated to 1.9.5 on 2013-09-07
 * Updated to 1.9.0 on 2013-07-22
 * Updated to 1.8.5 on 2013-07-09
 * Updated to 1.8.0 on 2013-05-22
 * Updated to 1.7.0 on 2013-05-08
 * Updated to 1.6.5 on 2013-04-30
 * Updated to 1.6.0 on 2013-03-18
 * Updated to 1.5.0 on 2013-03-01
 * Updated to 1.4.5 on 2013-02-21
 * Updated to 1.4.0 on 2013-01-28
 * Updated to 1.3.0 on 2012-12-29
 * Updated to 1.2.0 on 2012-12-26
 * Updated to 1.1.5 on 2012-12-03
 * Updated to 1.1.1 on 2012-11-01
 * Updated to 1.1.0 on 2012-10-04
 * Updated to 1.0.1 on 2012-09-14
 */

$mts_simple_booking = new MTS_Simple_Booking();

class MTS_Simple_Booking
{
	const DOMAIN = 'mts_simple_booking';

	const ADMIN_MENU = 'simple-booking';
	const PAGE_LIST = 'simple-booking-list';
	const PAGE_BOOKING = 'simple-booking-booking';
	const PAGE_SETTINGS = 'simple-booking-settings';
	const PAGE_OPTION = 'simple-booking-option';
	const PAGE_SCHEDULE = 'simple-booking-schedule';
	const PAGE_MAIL_TEMPLATE = 'simple-booking-mail-template';

	// フロントページ
	const PAGE_BOOKING_FORM = 'booking-form';
	const PAGE_BOOKING_THANKS = 'booking-thanks';
	const PAGE_CONTACT = 'contact';
	const PAGE_CONTACT_THANKS = 'contact-thanks';
	const PAGE_SUBSCRIPTION = 'subscription';
	const PAGE_CANCEL_SEND = 'cancel-send';
	const PAGE_CANCEL_THANKS = 'cancel-thanks';
    const PAGE_USERS = 'mtssb-users';
    const PAGE_REGISTER = 'mtssb-register';
    const PAGE_REGISTER_THANKS = 'register-thanks';

	const ADMIN_CSS_FILE = 'css/mtssb-admin.css';
	const FRONT_CSS_FILE = 'css/mtssb-front.css';
	const UI_CSS_FILE = 'css/smoothness/jquery-ui-1.10.1.custom.min.css';

	// ユーザー登録のロール
	const USER_ROLE = 'customer';

	// スケジュール名プレフィックス
	const SCHEDULE_NAME = 'schedule_';

	// オプションカタログ名サフィックス
	const CATALOG_NAME = '_optioncatalog';

	// CRON wp_schedule_event フック名称
	const CRON_AWAKING = 'mtssb_awaking';

    // スケジュール管理モジュール
    const SCHEDULE_MODULE = 'mtssb-scheduler-admin.php';

	// モジュールオブジェクト
	public $oArticle = null;
	public $oBooking_form = null;
	public $oFront = null;
	public $oFrontFreak = null;
	public $oCalendar_widget = null;
	public $oMail = null;
	public $oContact = null;
	public $oSubscription = null;
	public $oUser = null;
	public $oPPManager = null;
    public $oUsersPage = null;
    public $oRegister = null;
	public $oMailTemplate = null;
	public $oAwaking = null;

    // 管理画面処理モジュールとフック
    public $blist;

	// MTS Customerプラグインの組込み
	public $mtscu_activation = false;

	// ランゲージファイルロード
	protected $lang = false;

	public $plugin_url;
	public $settings;
	public $controls;

	public function __construct()
	{
		// Set Plug in URL
		$this->plugin_url = plugin_dir_url(__FILE__);	// WP_PLUGIN_URL . '/mts-simple-booking/'

		// Register Activation hook
		register_activation_hook(__FILE__, array($this, 'activation'));

		// ランゲージファイルロード
		if (!$this->lang) {
			if (load_textdomain(self::DOMAIN, dirname(__FILE__) . '/languages/' . get_locale() . '.mo')) {
				$this->lang = true;
			}
		}

		add_action('init', array(&$this, 'init_mtssb'));

		// 予約カレンダーウィジェットモジュールのロード
		require_once('mtssb-calendar-widget.php');
		MTSSB_Calendar_Widget::set_ajax_hook();
		add_action('widgets_init', function() {
		    register_widget(MTSSB_Calendar_Widget::BASE_ID);
		});

        require_once('mtssb-dailylink-widget.php');
        MTSSB_Dailylink_Widget::set_ajax_hook();
        add_action('widgets_init', function() {
            register_widget(MTSSB_Dailylink_Widget::BASE_ID);
        });

	}

	/**
	 * プラグイン初期化処理
	 *
	 */
	public function init_mtssb()
	{
		// MTS Customerプラグインの組み込み確認
		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		if (is_plugin_active('mts-customer/mts-customer.php')) {
			$this->mtscu_activation = true;
		} else {
			// 顧客ロールの追加・確認
			if (!get_role(self::USER_ROLE)) {
				add_role(self::USER_ROLE, __('Customer', self::DOMAIN), array(
					'read' => true,
				));
			}
		}

		if (is_admin()) {
			// ユーザー管理パックが組み込まれていなければユーザー項目を追加する
			if (!$this->mtscu_activation) {
				add_filter('user_contactmethods', array($this, 'user_contactmethod_extend'));
			}

			// 管理画面処理メニュー登録
			add_action('admin_menu', array($this, 'add_admin_menu'));

			// その他管理モジュールのロードとインスタンス化
			add_action('admin_init', array($this, 'admin_init'));

			// 予約品目処理オブジェクトのロード
            $this->_loadArticleAdmin();

            // 予約リストの予約確認AJAX
            add_action('wp_ajax_admin_ajax_assist', array($this, 'admin_ajax_assist'));

		} else {
			// 予約品目処理オブジェクトのロード
			require_once('mtssb-article.php');
			$this->oArticle = new MTSSB_Article();

			// モジュールロードと内部処理(予約の登録、メール送信、etc.)
			add_action('wp', array($this, 'internal_dispatcher'));

			// ショートコードの登録
			add_shortcode('monthly_calendar', array($this, 'monthly_calendar'));
			add_shortcode('multiple_calendar', array($this, 'multiple_calendar'));
			add_shortcode('timetable_calendar', array($this, 'timetable_calendar'));
			add_shortcode('list_calendar', array($this, 'list_calendar'));
            add_shortcode('mix_calendar', array($this, 'mix_calendar'));
            add_shortcode('list_monthly_calendar', array($this, 'listMonthlyCalendar'));

			// フォームのフロント処理ディスパッチャー
			add_filter('the_content', array($this, 'form_dispatcher'), apply_filters('mtssb_the_content_priority', 11));


			// その他設定の読み込み
			$miscellaneous = get_option(self::DOMAIN . '_miscellaneous');

			// フロント admin bar を非表示にする
			if (!empty($miscellaneous['adminbar']) and $miscellaneous['adminbar']) {
				add_filter('show_admin_bar', '__return_false');
			}

			// フロント表示のための設定
			add_action('wp_enqueue_scripts', array($this, 'front_enqueue_style'));
		}

        add_action('wp_before_admin_bar_render', array($this, 'adminBar'));

		// Cronの設定を取得する
		$this->controls = get_option(self::DOMAIN . '_controls');

		// 予約事前メール送信処理の設定
		if (!empty($this->controls['awaking']['mail'])) {
			add_action(self::CRON_AWAKING, array($this, 'holderAwaking'));
		}

	}

	/**
	 * cronスケジュールを登録する
	 *
	 */
	public function putSchedule($controls)
	{
		$current = time();

		// CRONを利用しない場合は hourly で指定時間を設定する
		if (empty($controls['awaking']['crontab'])) {
			$schedule = $current - $current % 3600 + intval($controls['awaking']['time']) * 60;
			if ($schedule <= $current) {
				$schedule += 3600;
			}
			wp_schedule_event($schedule, 'hourly', self::CRON_AWAKING);

		// CRONを利用する場合はcrontabでwp-cron.phpが実行された際に直ぐ動作するようにする
		} else {
			//$schedule = $current + intval($controls['awaking']['minute']) * 60;
			wp_schedule_single_event($current, self::CRON_AWAKING);
		}
	}

	/**
	 * 予約者に事前メールを送信する
	 *
	 */
	public function holderAwaking()
	{
		// 事前メール送信モジュールをロードして対象事前メールを送信する
		$oAwaking = $this->_load_module('MTSSB_Awaking');
		$oAwaking->awaking();

		// スケジュールがクリアされている場合は再設定する
		$scheduled = wp_next_scheduled(self::CRON_AWAKING);
		if (empty($scheduled)) {
			// CRON(crontab)の場合はwp-cron.phpが実行されたとき直ぐ動作するよう再スケジュールしておく
			$this->putSchedule($this->controls);
		}
	}

	/**
	 * 管理画面メニュー登録
	 *
	 */
	public function add_admin_menu() {
		add_menu_page(__('MTS Simple Booking', self::DOMAIN), __('Simple Booking', self::DOMAIN), 'publish_pages', self::ADMIN_MENU, array($this, 'menu_calendar'));
		add_submenu_page(self::ADMIN_MENU, __('Calendar', self::DOMAIN), __('Calendar', self::DOMAIN), 'publish_pages', self::ADMIN_MENU, array($this, 'menu_calendar'));
        $blistHook = add_submenu_page(self::ADMIN_MENU, __('List Booking', self::DOMAIN), __('List Booking', self::DOMAIN), 'publish_pages', self::PAGE_LIST, array($this, 'menu_list'));
        add_action('load-' . $blistHook, array($this, 'loadListBooking'));
		add_submenu_page(self::ADMIN_MENU, __('Add & Edit', self::DOMAIN), __('Add & Edit', self::DOMAIN), 'publish_pages', self::PAGE_BOOKING, array($this, 'menu_booking'));
		add_submenu_page(self::ADMIN_MENU, __('Schedule', self::DOMAIN), __('Schedule', self::DOMAIN), 'publish_pages', self::PAGE_SCHEDULE, array($this, 'menu_schedule'));
		add_submenu_page(self::ADMIN_MENU, __('Option', self::DOMAIN), __('Option', self::DOMAIN), 'publish_pages', self::PAGE_OPTION, array($this, 'menu_option'));
        add_submenu_page(self::ADMIN_MENU, __('Mail Template', self::DOMAIN), __('Mail Template', self::DOMAIN), 'publish_pages', self::PAGE_MAIL_TEMPLATE, array($this, 'menu_mail_template'));
		add_submenu_page(self::ADMIN_MENU, __('Settings', self::DOMAIN), __('Settings', self::DOMAIN), 'publish_pages', self::PAGE_SETTINGS, array($this, 'menu_settings'));
	}

	/**
	 * 管理画面メニュー処理　予約カレンダー
	 *
	 */
	public function menu_calendar() {
		$this->calendar->calendar_page();
	}

	/**
	 * 管理画面メニュー処理　予約の一覧
	 *
	 */
	public function menu_list() {
		$this->blist->list_page();
	}

	/**
	 * 管理画面メニュー処理　予約の新規追加
	 *
	 */
	public function menu_booking() {
		$this->booking->booking_page();
	}

	/**
	 * 管理画面メニュー処理　スケジュール
	 *
	 */
	public function menu_schedule() {
		$this->schedule->schedule_page();
	}

	/**
	 * 管理画面メニュー処理　オプション
	 *
	 */
	public function menu_option() {
		$this->option->option_page();
	}

    /**
     * 管理画面メニュー処理　メールテンプレート
     *
     */
    public function menu_mail_template()
    {
        $this->mail_template->mail_template_page();
    }

	/**
	 * 管理画面メニュー処理　各種設定
	 *
	 */
	public function menu_settings() {
		$this->settings->settings_page();
	}

	/**
	 * 管理画面ユーザー管理 プロファイル処理
	 *
	 */
	public function user_contactmethod_extend($user_contactmethods) {
		$ouser = $this->_load_module('MTSSB_User_Admin');
		return $ouser->extend_user_contactmethod($user_contactmethods);
	}

    /**
     * アドミンバーにユーザーズページをリンクする
     *
     */
    public function adminBar()
    {
        global $wp_admin_bar;

        $wpPost = get_page_by_path(self::PAGE_USERS);

        if ($wpPost) {
            $pageUrl = get_permalink($wpPost);

            $wp_admin_bar->add_node(array(
                'id' => self::PAGE_USERS,
                'title' => 'MTSSBユーザーページ',
                'href' => $pageUrl,
                'parent' => 'my-account',
            ));
        }
    }

    /**
     * 管理画面AJAX処理
     *
     */
    public function admin_ajax_assist()
    {
        // NONCEチェック
        $nonce_key = isset($_POST['module']) ? $_POST['module'] : '';
        check_ajax_referer($nonce_key, 'nonce');

        $ret = array('result' => 'false', 'message' => 'Failed.');

        // 処理振り分け
        switch ($_POST['module']) {
            case 'mtssb_list_admin' :
                // モジュールロード
                $this->loadListBooking();
                $ret = $this->blist->ajax_dispatcher();
                break;
            case 'mtssb_get_timetable' :
                $this->_loadArticleAdmin();
                $ret['result'] = true;
                $ret['message'] = $this->oArticle->ajax_get_the_timetable();
                break;
            case 'mtssb_booking_count' :
                $this->_mtssb_load_module(self::PAGE_BOOKING);
                $ret['message'] = $this->booking->booking_day_info($_POST['day_time'], $_POST['article_id'], $_POST['method']);
                $ret['result'] = true;
                break;
        }

        // 処理結果をJSONで戻す
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($ret);

        exit();
    }

	/**
	 * admin_init アクションで処理しなければならないモジュール処理
	 *
	 */
	public function admin_init()
	{
		if (isset($_REQUEST['page'])) {
			$page = $_REQUEST['page'];
		// WP options.phpでオプションデータ保存のためのホワイトリスト登録
		} else if (isset($_POST['option_page']) && isset($_POST['mts_page_tag'])) {
			$page = $_POST['mts_page_tag'];
		} else {
			return;
		}

        // 対象処理モジュールのオブジェクトロード
        $this->_mtssb_load_module($page);
	}

    /**
     * 管理画面 予約品目モジュールのロード
     *
     */
    private function _loadArticleAdmin()
    {
        if (!class_exists('MTSSB_Article_Admin')) {
            require_once('mtssb-article-admin.php');
        }
        $this->oArticle = new MTSSB_Article_Admin;
    }

    /**
     * 処理モジュールオブジェクトロード
     *
     */
    private function _mtssb_load_module($page)
    {
		switch ($page) {
			case self::ADMIN_MENU :
			//case self::PAGE_CALENDAR :
				if (!class_exists('MTSSB_Calendar_Admin')) {
					require_once('mtssb-calendar-admin.php');
				}
				$this->calendar = MTSSB_Calendar_Admin::get_instance();
				break;
			case self::PAGE_BOOKING :
				if (!class_exists('MTSSB_Booking_Admin')) {
					require_once('mtssb-booking-admin.php');
				}
				$this->booking = MTSSB_Booking_Admin::get_instance();
				break;
			case self::PAGE_SETTINGS :
				if (!class_exists('MTSSB_Settings_Admin')) {
					require_once('mtssb-settings-admin.php');
				}
				$this->settings = MTSSB_Settings_Admin::get_instance();
				break;
			case self::PAGE_OPTION :
				if (!class_exists('MTSSB_Option_Admin')) {
					require_once('mtssb-option-admin.php');
				}
				$this->option = MTSSB_Option_Admin::get_instance();
				break;
            case self::PAGE_MAIL_TEMPLATE :
                if (!class_exists('MTSSB_Mail_Template_Admin')) {
                    require_once('mtssb-mail-template-admin.php');
                }
                $this->mail_template = MTSSB_Mail_Template_Admin::get_instance();
                break;
			case self::PAGE_SCHEDULE :
				if (!class_exists('MTSSB_Schedule_Admin')) {
                    require_once(self::SCHEDULE_MODULE);
				}
				$this->schedule = MTSSB_Schedule_Admin::get_instance();
				break;
			default :
				break;
		}
    }

    /**
     * 管理画面 予約リストモジュールのロード
     *
     */
    public function loadListBooking()
    {
        if (!class_exists('MTSSB_List_Admin')) {
            require_once('mtssb-list-admin.php');
        }
        $this->blist = MTSSB_List_Admin::get_instance();
    }

	/**
	 * 管理画面 CSS ファイルロード登録
	 *
	 */
	public function enqueue_style() {
		$handle = self::DOMAIN . '_admin_css';
		wp_enqueue_style($handle, $this->plugin_url . self::ADMIN_CSS_FILE);
	}

	/**
	 * ショートコード 月間予約カレンダーのロード・実行
	 *
	 */
	public function monthly_calendar($atts) {
		if (!class_exists('MTSSB_Front')) {
			require_once(dirname(__FILE__) . '/mtssb-front.php');
		}

		$this->oFront = new MTSSB_Front();
		return $this->oFront->monthly_calendar($atts);
	}

	/**
	 * ショートコード 月間予約マルチカレンダーのロード・実行
	 *
	 */
	public function multiple_calendar($atts) {
		if (!class_exists('MTSSB_Front')) {
			require_once(dirname(__FILE__) . '/mtssb-front.php');
		}

		$this->oFront = new MTSSB_Front();
		return $this->oFront->multiple_calendar($atts);
	}

	/**
	 * ショートコード 月間時間割カレンダーのロード・実行
	 *
	 */
	public function timetable_calendar($atts) {
		if (!class_exists('MTSSB_Front')) {
			require_once(dirname(__FILE__) . '/mtssb-front.php');
		}

		$this->oFront = new MTSSB_Front();
		return $this->oFront->timetable_calendar($atts);
	}

	/**
	 * ショートコード　リスト予約カレンダーのロード・実行
	 *
	 */
	public function list_calendar($atts)
	{
		if (!class_exists('MTSSB_Front_Freak')) {
			require_once(dirname(__FILE__) . '/mtssb-front-freak.php');
		}

		$this->oFrontFreak = new MTSSB_Front_Freak();
		return $this->oFrontFreak->list_calendar($atts);
	}

    /**
     * ショートコード　ミックス予約カレンダーのロード・実行
     *
     */
    public function mix_calendar($atts)
    {
        if (!class_exists('MTSSB_Front_Freak')) {
            require_once(dirname(__FILE__) . '/mtssb-front-freak.php');
        }

        $this->oFrontFreak = new MTSSB_Front_Freak();
        return $this->oFrontFreak->mix_calendar($atts);
    }

    /**
     * ショートコード　月間リストカレンダーのロード・実行
     *
     */
    public function listMonthlyCalendar($atts)
    {
        if (!class_exists('MTSSB_Front')) {
            require_once(dirname(__FILE__) . '/mtssb-front.php');
        }

        $this->oFront = new MTSSB_Front();
        return $this->oFront->listMonthlyCalendar($atts);
    }
    /**
	 * フロント CSS ファイルロード登録
	 *
	 */
	public function front_enqueue_style() {
		$handle = self::DOMAIN . '_front';
		wp_enqueue_style($handle, $this->plugin_url . self::FRONT_CSS_FILE);
	}

	/**
	 * 予約登録・メール送信処理内部ディスパッチャー
	 *
	 */
	public function internal_dispatcher()
	{
		$action = isset($_POST['action']) ? $_POST['action'] : '';

		if (is_page(self::PAGE_BOOKING_FORM)) {
			$booking_form = $this->_load_module('MTSSB_Booking_Form');

			if ($action == 'confirm') {
				// フォーム入力の確認
				if ($booking_form->check_post_booking()) {
					// PayPal SetExpressCheckoutの発行
					if (isset($_POST['reserve_action']) && $_POST['reserve_action'] == 'checkout') {
						// PayPal SetExpressCheckoutを実行する
						$ppman = $this->_load_module('MTSSB_PP_Manager');
						try {
							$setECResponse = $ppman->setExpressCheckout();
                            // リダイレクト終了しない場合はエラー
                            $booking_form->error_paypal($ppman->transaction_error($setECResponse));
						} catch (Exception $ex) {
							// PayPalサーバー接続エラー
							$booking_form->error_paypal($ppman->exception_error($ex));
						}

					// 予約データの保存とメール送信
					} else if ($booking_form->front_booking()) {

						$mail = $this->_load_module('MTSSB_Mail');
						// 予約メールをお客・自社・モバイルへ送信、リダイレクト
						if ($mail->booking_mail()) {
							// アフィリエイト情報を取得する
							$affiliate = $this->_affiliate_info($booking_form);
							$next_url = self::get_permalink_by_slug(self::PAGE_BOOKING_THANKS);
							if (empty($next_url)) {
								$next_url = add_query_arg(array('action' => 'thanks') + $affiliate,
								 self::get_permalink_by_slug(MTSSB_Booking_Form::PAGE_NAME));
							} else {
								$next_url = add_query_arg($affiliate, $next_url);
							}

							// リロードで再予約できないようにリダイレクトする
							wp_redirect($next_url);
							exit();
						} else {
							// メールの送信エラーセット
							$booking_form->error_send_mail();
						}
					}
				}

			// PayPal SetExpressCheckoutからのリダイレクトリターンのエントリポイント
			} else if (isset($_GET['pp'])) {
				if (in_array($_GET['pp'],
				 array('checkout', 'doecdone', 'doecerr', 'doecexp', 'closed', 'cancel', 'recancel'))) {
					// PayPalモジュールをロード
					$ppman = $this->_load_module('MTSSB_PP_Manager');

					// PayPalからリターンしたか確認する
					if ($ppman->checkPaypalReturn()) {
						// Bookingオブジェクトにbookingデータをセットする
						$booking_form->setBooking($ppman->getSessionData('booking'));

						switch ($_GET['pp']) {
							// https 支払実行してリダイレクトされた処理
							case 'checkout' :
								// 予約満杯の場合はDoExpressCheckoutを実行しないでリダイレクト
								if (!$booking_form->pre_check() || $booking_form->check_booking_vacancy() !== true) {
									$ppman->do_redirect('closed');
								}

								// DoExpressCheckoutの実行
								try {
									$result = $ppman->doExpressCheckout();
								} catch (Exception $ex) {
									// PayPalサーバー接続エラー
									$ppman->do_redirect('doecexp');
								}

								// DoExpressCheckoutが正常終了なら予約処理を実行する
								if ($result) {
									$ppman->do_redirect('doecdone');
								}
								$ppman->do_redirect('doecerr');
								break;

							// http DoExpreeCheckout決済完了のリダイレクトされた処理
							case 'doecdone' :
								// 決済トランザクションIDを予約データに保存する
								$booking_form->setTransactionID($ppman->get_transactionId());

								if ($booking_form->pre_check() && $booking_form->front_booking()) {
									$mail = $this->_load_module('MTSSB_Mail');
									// 予約メールをお客・自社・モバイルへ送信、リダイレクトページがあれば実行
									if ($mail->booking_mail()) {
										// アフィリエイト情報を取得する
										$affiliate = $this->_affiliate_info($booking_form);
										$next_url = self::get_permalink_by_slug(self::PAGE_BOOKING_THANKS);
										if (empty($next_url)) {
											$next_url = add_query_arg(array('action' => 'thanks') + $affiliate,
											 self::get_permalink_by_slug(MTSSB_Booking_Form::PAGE_NAME));
										} else {
											$next_url = add_query_arg($affiliate, $next_url);
										}

										// リロードで再予約できないようにリダイレクトする
										wp_redirect($next_url);
										exit();

									} else {
										// メールの送信エラーセット
										$booking_form->error_send_mail();
									}
								} else {
									$booking_form->error_paypal('BOOKING_ERROR');
								}
								return;

							// http DoExpressCheckoutのレスポンスがSuccessでないときのリダイレクト処理
							case 'doecerr' :
								$booking_form->error_paypal($ppman->transaction_error($ppman->getSessionData('doECResponse')));
								return;

							// http DoExpressCheckoutエラーによるhttpsからのリダイレクトされた処理
							case 'doecexp' :
								// PayPalサーバー接続エラー
								$booking_form->error_paypal($ppman->exception_error($ppman->getSessionData('doECException')));
								return;

							// http SetExpressCheckout後の予約確認で予約不可状態
							case 'closed' :
								$booking_form->error_paypal('CLOSED_BOOKING');
								return;

							// https SetExpressCheckout後のPayPalキャンセル
							case 'cancel' :
								// httpの予約入力フォームへリダイレクトする
								$ppman->do_redirect('recancel');
								break;

							// http キャンセルでhttpsからリダイレクトされた処理
							case 'recancel' :
								$booking_form->set_message('PAYPAL_CANCEL');
								return;
							default:
								break;
						}
					}
				}

				// 処理未定な場合
				global $wp_query;
				$wp_query->is_404 = true;
			}

		// 問合わせフォームメール送信処理
		} else if (is_page(self::PAGE_CONTACT) && $action == 'confirm') {
			$contact = $this->_load_module('MTSSB_Contact');
			if ($contact->check_before_send()) {
				$mail = $this->_load_module('MTSSB_Mail');
				if ($mail->contact_mail()) {
					$next_url = self::get_permalink_by_slug(self::PAGE_CONTACT_THANKS);
					if (!empty($next_url)) {
						// 問い合わせフォーム送信後のリダイレクト
						wp_redirect($next_url);
						exit();
					}
				} else {
					$contact->error_send_mail();
				}
			}

		// 予約のキャンセル
		} else if (is_page(self::PAGE_SUBSCRIPTION)) {
            $subscription = $this->_load_module('MTSSB_Subscription');

			$nextUrl = $subscription->cancelWp();
			if (!empty($nextUrl)) {
				wp_redirect($nextUrl);
				exit();
			}

        // ユーザーズページ
        } elseif (is_page(self::PAGE_USERS)) {
            $users = $this->_load_module('MTSSB_Users_Page');

        // ユーザー登録
        } elseif (is_page(self::PAGE_REGISTER)) {
			$this->holderAwaking();

            $register = $this->_load_module('MTSSB_Register');
            $register->registerUser();
        }

	}

	/**
	 * 終了画面表示でのアフィリエイトトラッキング用情報生成
	 *
	 */
	private function _affiliate_info($booking_form)
	{
		$booking = $booking_form->getBooking();

		// 予約品目のアフィリエイト指定確認
		$article = $booking_form->getArticle();

		if (empty($booking) || empty($article) || !$article['addition']->tracking) {
			return array();
		}

		return array(
			'nonce' => wp_create_nonce('affiliate' . $booking['booking_id']),
			'bid' => $booking['booking_id'],
		);
	}

	/**
	 * 予約処理、お問い合わせ処理フォームディスパッチャー
	 *
	 */
	public function form_dispatcher($content) {

		if (is_page(self::PAGE_BOOKING_FORM) || is_page(self::PAGE_BOOKING_THANKS)) {
			$booking_form = $this->_load_module('MTSSB_Booking_Form');
			$content = $booking_form->booking_form($content);

		} else if (is_page(self::PAGE_CONTACT)) {
			$contact = $this->_load_module('MTSSB_Contact');
			if ($contact) {
				$content = $contact->contact_form($content);
			}

		} else if (is_page(self::PAGE_SUBSCRIPTION)) {
			$subscription = $this->_load_module('MTSSB_Subscription');
			if ($subscription) {
				$content = $subscription->content($content);
			}
		} elseif (is_page(self::PAGE_USERS)) {
            $users = $this->_load_module('MTSSB_Users_Page');
            $content = $users->content($content);
        }

		return $content;
	}

	/**
	 * フロントページ処理モジュールのロード
	 *
	 * @class_name
	 * @return		Module Object
	 */
    public function _load_module($class_name) {

		if (!class_exists($class_name)) {
			$filename = strtolower(str_replace('_', '-', $class_name)) . '.php';
			require(dirname(__FILE__) . "/$filename");
		}

		switch ($class_name) {
			case 'MTSSB_Booking_Form':
				if (empty($this->oBooking_form)) {
					$this->oBooking_form = new MTSSB_Booking_Form();
				}
				return $this->oBooking_form;
			case 'MTSSB_User_Admin':
				if (empty($this->oUser)) {
					$this->oUser = MTSSB_User_Admin::get_instance();
				}
				return $this->oUser;
			case 'MTSSB_Contact':
				if (empty($this->oContact)) {
					$this->oContact = new MTSSB_Contact();
				}
				return $this->oContact;
			case 'MTSSB_Subscription':
				if (empty($this->oSubscription)) {
					$this->oSubscription = new MTSSB_Subscription();
				}
				return $this->oSubscription;
			case 'MTSSB_Mail':
				if (empty($this->oMail)) {
					$this->oMail = new MTSSB_Mail();
				}
				return $this->oMail;
			case 'MTSSB_PP_Manager':
				if (empty($this->oPPManager)) {
					$this->oPPManager = new MTSSB_PPManager();
				}
				return $this->oPPManager;
            case 'MTSSB_Users_Page':
                if (empty($this->oUsersPage)) {
                    $this->oUsersPage = new MTSSB_Users_page;
                }
                return $this->oUsersPage;
            case 'MTSSB_Register':
                if (empty($this->oRegister)) {
                    $this->oRegister = new MTSSB_Register;
                }
                return $this->oRegister;
			case 'MTSSB_Mail_Template':
				if (empty($this->oMailTemplate)) {
					$this->oMailTemplate = new MTSSB_Mail_Template;
				}
				return $this->oMailTemplate;
			case 'MTSSB_Awaking':
				if (empty($this->oAwaking)) {
					$this->oAwaking = new MTSSB_Awaking;
				}
				return $this->oAwaking;
			default:
				break;
		}

		return null;
	}

	/**
	 * スラッグ名から投稿のリンクURLを取得する
	 *
	 * @slug	スラッグ名
	 * @type	post_type(='page')
	 */
	static public function get_permalink_by_slug($name) {
		global $wpdb;

		$post_id = $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts}
			WHERE post_status='publish' AND post_name=%s
			ORDER BY ID", $name));

		if (empty($post_id)) {
			return false;
		}

		return get_permalink($post_id[0]);
	}

	/**
	 * Uninstall
	 *
	 */
	public function uninstall() {
	}

	/**
	 * Plugin activation
	 *
	 */
	public function activation()
	{
		// To flush_rewrite_rules after register_post_type
		add_option(self::DOMAIN . '_activation', 1);
	}

}
