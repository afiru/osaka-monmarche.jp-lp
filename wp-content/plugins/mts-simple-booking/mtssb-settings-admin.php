<?php
/**
 * MTS Simple Booking 管理予約システム 各種設定管理モジュール
 *
 * @filename	mtssb-settings-admin.php
 * @date		2012-04-23
 * @author		S.Hayashi
 *
 * Updated to 1.33.0 on 2020-04-20
 * Updated to 1.31.0 on 2019-05-17
 * Updated to 1.29.0 on 2018-03-28
 * Updated to 1.28.0 on 2017-10-26
 * Updated to 1.26.0 on 2017-04-24
 * Updated to 1.21.0 on 2014-12-22
 * Updated to 1.17.0 on 2014-07-02
 * Updated to 1.12.0 on 2013-11-18
 * Updated to 1.8.0 on 2013-05-23
 * Updated to 1.6.0 on 2013-03-18
 * Updated to 1.4.5 on 2013-02-20
 * Updated to 1.2.5 on 2013-02-07	// 予約入りマーク追加
 * Updated to 1.3.0 on 2012-12-26
 * Updated to 1.1.5 on 2012-12-02
 */
class MTSSB_Settings_Admin
{
	const PAGE_NAME = 'simple-booking-settings';

	/**
	 * Instance of this object module
	 */
	static private $iSettings = null;

	/**
	 * Private valiable
	 */
	private $domain;

	private $data = array();	// Option data of reading

	private $tab = '';			// Current Tab

	// 設定ページタブ
	private $tabs = array('controls', 'premise', 'reserve', 'contact', 'miscellaneous', 'charge', 'paypal');

	/**
	 * インスタンス化
	 *
	 */
	static function get_instance() {
		if (!isset(self::$iSettings)) {
			self::$iSettings = new MTSSB_Settings_Admin();
		}

		return self::$iSettings;
	}


	protected function __construct()
	{
		global $mts_simple_booking;

		$this->domain = MTS_Simple_Booking::DOMAIN;

		// PayPal Managerの利用準備
		//$this->ppm = new MTSSB_PPManager;

		// オプションデータ保存のためのホワイトリスト登録
		if (isset($_POST['mts_page_tag'])) {
			$option_name = $_POST['option_page'];
			$tab = substr($_POST['option_page'], strlen($this->domain . '_'));
			register_setting($option_name, $option_name, array($this, "{$tab}_validate"));
			return;
		}

		// オプションデータの新規登録・更新
		$this->_install_option();

		// CSS,JSロード
		$mts_simple_booking->enqueue_style();
	}

	/**
	 * Option page html lounched from admin menu 'General Settings'
	 *
	 */
	public function settings_page() {

		if (isset($_GET['tab']) && in_array($_GET['tab'], $this->tabs)) {
			$this->tab = $_GET['tab'];
		} else {
			$this->tab = $this->tabs[0];
		}

		$option_name = "{$this->domain}_{$this->tab}";

		$this->data = get_option($option_name);

		// PayPalデータを復号する
		$controls = get_option("{$this->domain}_charge");
		//if ($this->tab == 'paypal' && $controls['checkout']) {
		//	$this->data['pp_username'] = $this->ppm->mts_decode($this->data['pp_username']);
		//	$this->data['pp_password'] = $this->ppm->mts_decode($this->data['pp_password']);
		//	$this->data['pp_signature'] = $this->ppm->mts_decode($this->data['pp_signature']);
		//}

		// 当該タブページの設定
		add_settings_section($option_name, $this->_option_title(), array($this, 'add_fields_settings'), $option_name);
		//$this->add_fields_settings();
?>
	<div class="wrap">
	<h2><?php _e('Setting Parameters', $this->domain); ?></h2>
	<h3 class="nav-tab-wrapper">
		<?php foreach ($this->tabs as $tb) : if ($tb != 'paypal' || !empty($controls['checkout'])) : ?>
			<a class="nav-tab<?php echo $this->tab == $tb ? ' nav-tab-active' : '' ?>" href="<?php echo admin_url('admin.php?page=' . self::PAGE_NAME . "&amp;tab={$tb}") ?>"><?php echo $this->_tab_caption($tb) ?></a>
		<?php endif; endforeach; ?>
	</h3>
	<?php settings_errors() ?>

	<form method="post" action="options.php">
		<?php settings_fields($option_name) ?>
		<?php do_settings_sections($option_name) ?>
		<?php submit_button() ?>
		<input type="hidden" name="mts_page_tag" value="<?php echo self::PAGE_NAME ?>" />
	</form>
	<?php $this->_footer_description() ?>
	</div>

<?php
	}


	/**
	 * Validate Settings
	 *
	 */
	public function controls_validate($input)
	{
		global $mts_simple_booking;

        // 予約受付終了
        if ($input['start_accepting'] < 1440) {
            $input['until_accepting'] = 0;
        } else {
            $input['until_accepting'] = intval($input['until_accepting']['hour'])
             + intval($input['until_accepting']['minute']);
        }

		// スケジュール動作をクリアする
		if (wp_next_scheduled(MTS_Simple_Booking::CRON_AWAKING)) {
			wp_clear_scheduled_hook(MTS_Simple_Booking::CRON_AWAKING);
		}

		// 予約事前メール送信が指定された場合はスケジュール設定する
		if (!empty($input['awaking']['mail'])) {
			$mts_simple_booking->putSchedule($input);
		}

		return $input;
	}

	public function miscellaneous_validate($input) {
		return $input;
	}

	public function charge_validate($input) {
		return $input;
	}

	public function paypal_validate($input) {
        $input['pp_username'] = trim($input['pp_username']);
        $input['pp_password'] = trim($input['pp_password']);
        $input['pp_signature'] = trim($input['pp_signature']);

		return $input;
	}

	public function premise_validate($input) {
		return $input;
	}

	public function reserve_validate($input)
	{
		// 初期定義のカラム名順配列
		$def = $this->_get_default('reserve');
		$orders = $def['column'];

		// 入力されたカラム名順配列
		$inorders = explode(',', $input['column_order']);

		// 入力カラム名を操作しスペルミスがあれば排除する
		$neworders = '';
		foreach ($inorders as $keyname) {
			if (array_key_exists($keyname, $orders)) {
				$neworders .= empty($neworders) ? $keyname : ",{$keyname}";
				unset($orders[$keyname]);
			}
		}

		// スペルミスや入力不足の場合は追加する
		foreach ($orders as $keyname => $val) {
			$neworders .= empty($neworders) ? $keyname : ",{$keyname}";
		}

		$input['column_order'] = $neworders;

		return $input;
	}

	public function contact_validate($input) {
		return $input;
	}

	/**
	 * タブ見出し
	 *
	 */
	private function _tab_caption($tab) {
		$captions = array(
			'controls' => __('Booking Paramters', $this->domain),
			'premise' => __('Premise Informations', $this->domain),
			'reserve' => __('Booking Mail', $this->domain),
			'contact' => __('Contact Mail', $this->domain),
			'miscellaneous' => __('Miscellaneous', $this->domain),
			'charge' => __('Charge Settings', $this->domain),
			'paypal' => __('PayPal Config', $this->domain),
		);

		return $captions[$tab];
	}

	/**
	 * タブページのタイトル
	 *
	 */
	private function _option_title() {
		$titles = array(
			'controls' => __('Booking control parameters', $this->domain),
			'premise' => __('Information of the premise', $this->domain),
			'reserve' => __('Reply mail sentences for booking', $this->domain),
			'contact' => __('Reply mail sentences for contact', $this->domain),
			'miscellaneous' => __('Other settings', $this->domain),
			'charge' => __('Charge information for web page and e-mail', $this->domain),
			'paypal' => __('Configuration to use PayPal API', $this->domain),
		);

		return $titles[$this->tab];
	}

	/**
	 * Add settings' fields to the section
	 *
	 */
	public function add_fields_settings() {
		$option_name = "{$this->domain}_{$this->tab}";
		if ($this->tab == 'controls') {
			add_settings_field('available', __('Booking available', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'available'));
			add_settings_field('closed_page', __('Closed Page', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'closed_page'));
			add_settings_field('start_accepting', __('Close accepting', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'start_accepting'));
			add_settings_field('cancel', __('Cancel available', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'cancel'));
			add_settings_field('output_margin', __('Output in the margin', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'output_margin'));
			add_settings_field('period', __('Period', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'period'));
            add_settings_field('hedge', __('Hedge', $this->domain), array($this, 'controls_form'),
                $option_name, $option_name, array('label_for' => 'hedge'));
			add_settings_field('awaking', __('Awaking Mail', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'awaking'));
			add_settings_field('vacant_mark', __('Vacant Mark', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'vacant_mark'));
			add_settings_field('booked_mark', __('Booked Mark', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'booked_mark'));
			add_settings_field('low_mark', __('Low Mark', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'low_mark'));
			add_settings_field('full_mark', __('Full Mark', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'full_mark'));
			add_settings_field('disable', __('Disable Mark', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'disable'));
			add_settings_field('vacant_rate', __('Vacant Rate', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'vacant_rate'));
			add_settings_field('count', __('Count Number', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'count'));
			add_settings_field('message', __('Message', $this->domain), array($this, 'controls_form'),
				$option_name, $option_name, array('label_for' => 'message'));

		} else if ($this->tab == 'premise') {
			add_settings_field('name', __('The name of premise', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'name'));
			add_settings_field('postcode', __('Postcode', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'postcode'));
			add_settings_field('address1', __('Address 1', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'address1'));
			add_settings_field('address2', __('Address 2', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'address2'));
			add_settings_field('tel', __('Tel', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'tel'));
			add_settings_field('fax', __('Fax', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'fax'));
			add_settings_field('email', __('E-Mail', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'email'));
			add_settings_field('mobile', __('Mobile Mail', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'mobile'));
			add_settings_field('web', __('Web Site URL', $this->domain), array($this, 'premise_form'),
				$option_name, $option_name, array('label_for' => 'web'));
			add_settings_field('zip_search', __('Postcode Retrieval', $this->domain), array($this, 'premise_form'),
                $option_name, $option_name, array('label_for' => 'zip_search'));
			add_settings_field('api_key', __('Web API Key', $this->domain), array($this, 'premise_form'),
                $option_name, $option_name, array('label_for' => 'api_key'));

		} else if ($this->tab == 'reserve') {
			add_settings_field('column', __('Column Setting', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'column'));
			add_settings_field('column_order', __('Column Order', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'column_order'));
			add_settings_field('title', __('Subject', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'title'));
			add_settings_field('header', __('Mail Header', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'header'));
			add_settings_field('footer', __('Mail Footer', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'footer'));
			add_settings_field('cancel_title', __('Cancel Subject', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'cancel_title'));
			add_settings_field('cancel_body', __('Cancel Body', $this->domain), array($this, 'reserve_form'),
				$option_name, $option_name, array('label_for' => 'cancel_body'));

		} else if ($this->tab == 'contact') {
			add_settings_field('column', __('Column Setting', $this->domain), array($this, 'contact_form'),
				$option_name, $option_name, array('label_for' => 'column'));
			add_settings_field('title', __('Subject', $this->domain), array($this, 'contact_form'),
				$option_name, $option_name, array('label_for' => 'title'));
			add_settings_field('header', __('Mail Header', $this->domain), array($this, 'contact_form'),
				$option_name, $option_name, array('label_for' => 'header'));
			add_settings_field('footer', __('Mail Footer', $this->domain), array($this, 'contact_form'),
				$option_name, $option_name, array('label_for' => 'footer'));

		} else if ($this->tab == 'miscellaneous') {
			add_settings_field('adminbar', __('Admin Bar', $this->domain), array($this, 'miscellaneous_form'),
				$option_name, $option_name, array('label_for' => 'adminbar'));
			add_settings_field('schedule_dialog', __('Schedule Dialog', $this->domain), array($this, 'miscellaneous_form'),
				$option_name, $option_name, array('label_for' => 'schedule_dialog'));

		} else if ($this->tab == 'charge') {
			add_settings_field('accedence', __('Accedence Checkbox', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'accedence'));
			add_settings_field('terms_url', __('Terms and conditions URL', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'terms_url'));
			add_settings_field('charge_list', __('Charge List', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'charge_list'));
			add_settings_field('currency_code', __('Currency Code', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'currency_code'));
			add_settings_field('tax_notation', __('Tax Notation', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'tax_notation'));
			add_settings_field('consumption_tax', __('Consumption tax(%)', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'consumption_tax'));
			add_settings_field('pay_first', __('Payment Required', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'pay_first'));
			add_settings_field('checkout', __('Checkout Function', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'checkout'));
            add_settings_field('checkout_button', __('Checkout Button', $this->domain), array($this, 'charge_form'),
                $option_name, $option_name, array('label_for' => 'checkout_button'));
			add_settings_field('unsettled_mail', __('Not Paid Sentence', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'unsettled_mail'));
			add_settings_field('settled_mail', __('Paid Sentence', $this->domain), array($this, 'charge_form'),
				$option_name, $option_name, array('label_for' => 'settled_mail'));

		} else if ($this->tab == 'paypal') {
			add_settings_field('pp_username', __('API Username', $this->domain), array($this, 'paypal_form'),
				$option_name, $option_name, array('label_for' => 'pp_username'));
			add_settings_field('pp_password', __('API Password', $this->domain), array($this, 'paypal_form'),
				$option_name, $option_name, array('label_for' => 'pp_password'));
			add_settings_field('pp_signature', __('Signature', $this->domain), array($this, 'paypal_form'),
				$option_name, $option_name, array('label_for' => 'pp_signature'));
			add_settings_field('https_url', __('HTTPS URL', $this->domain), array($this, 'paypal_form'),
				$option_name, $option_name, array('label_for' => 'https_url'));
			add_settings_field('logo_url', __('Logo image URL', $this->domain), array($this, 'paypal_form'),
				$option_name, $option_name, array('label_for' => 'logo_url'));
            add_settings_field('use_sandbox', __('Use Sandbox', $this->domain), array($this, 'paypal_form'),
                $option_name, $option_name, array('label_for' => 'use_sandbox'));
		}
	}

	/**
	 * Controls form
	 */
	public function controls_form($args)
	{
		$priorities = array('capacity', 'quantity');

		switch ($args['label_for']) {
			case 'available' : ?>
				<input type="hidden" id="available_" name="mts_simple_booking_controls[available]" value="0" />
				<input id="available" name="mts_simple_booking_controls[available]" value="1" type="checkbox"<?php echo $this->data['available'] ? ' checked="checked"' : '' ?> /><br />
				<?php _e('Uncheck to stop displaying and accepting reservations.', $this->domain);
				break;
			case 'closed_page' : ?>
				<input id="closed_page" name="mts_simple_booking_controls[closed_page]" type="text" value="<?php echo esc_html($this->data['closed_page']) ?>" /><br />
				<?php _e('Input the closed message to display.', $this->domain);
				break;
			case 'start_accepting' :
				$accepting = $this->_get_booking_margin(); ?>
				<select id="start_accepting" name="mts_simple_booking_controls[start_accepting]" style="letter-spacing:2px"><?php foreach ($accepting as $min => $label) : ?>
					<option value="<?php echo $min ?>"<?php echo $min == $this->data['start_accepting'] ? ' selected="selected"' : '' ?>><?php echo $label ?></option>
				<?php endforeach; ?></select> <?php _e('before', $this->domain);
                    $hour = intval(intval($this->data['until_accepting']) / 60);
                    $minute = intval(intval($this->data['until_accepting']) % 60); ?>
                <select id="until_accepting" name="mts_simple_booking_controls[until_accepting][hour]"><?php for ($i = 0; $i < 24; $i++) :
                    echo sprintf('<option value="%d"%s>%02d</option>', $i * 60, ($i == $hour ? ' selected="selected"' : ''), $i) . "\n";
                    endfor; ?></select><?php _e('Hour', $this->domain) ?>
                <select id="until_accepting_minute" name="mts_simple_booking_controls[until_accepting][minute]"><?php for ($i = 0; $i < 60; $i += 5) :
                    echo sprintf('<option value="%d"%s>%02d</option>', $i, ($i == $minute ? ' selected="selected"' : ''), $i) . "\n";
                    endfor; ?></select><?php _e('Minute', $this->domain) ?><br>
                <?php _e('The accepting is closed under selected date and the time. This feature is available over a day.', $this->domain);
                break;
            case 'cancel' : ?>
                <label><input id="cancel" type="radio" name="mts_simple_booking_controls[cancel]" value="0"<?php echo $this->_cmpChecked($this->data['cancel'], 0) ?> />受け付けない</label>&nbsp;
                <label><input type="radio" name="mts_simple_booking_controls[cancel]" value="1"<?php echo $this->_cmpChecked($this->data['cancel'], 1) ?> />メール方式</label>&nbsp;
                <label><input type="radio" name="mts_simple_booking_controls[cancel]" value="2"<?php echo $this->_cmpChecked($this->data['cancel'], 2) ?> />ボタン実行</label><br>
				<?php echo 'キャンセルはメール内のURLアクセスで実行か、ボタン押下で即実行か選択してください。';
				break;
			case 'output_margin' : ?>
				<label><input id="output_margin" type="radio" name="mts_simple_booking_controls[output_margin]" value="1"<?php echo $this->data['output_margin'] ? ' checked="checked"' : '' ?> /><?php _e('Output mark', $this->domain) ?></label>&nbsp;
				<label><input type="radio" name="mts_simple_booking_controls[output_margin]" value="0"<?php echo empty($this->data['output_margin']) ? ' checked="checked"' : '' ?> /><?php _e('Disable mark', $this->domain) ?></label><br />
				<?php _e('Mark out or disable in the margin of accepting.', $this->domain);
				break;
			case 'period' : ?>
				<select id="period" name="mts_simple_booking_controls[period]" style="letter-spacing:2px"><?php $period_months = $this->_get_period_months(); for ($i = 1; $i <= $period_months; $i++) : ?>
					<option value="<?php echo $i ?>"<?php echo $i == $this->data['period'] ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
				<?php endfor; ?></select> <?php _e('months', $this->domain) ?><br />
				<?php _e('Number of months that you accept bookings in the future.', $this->domain);
				break;
            case 'hedge' : ?>
                <label><input id="hedge" type="radio" name="mts_simple_booking_controls[hedge]" value="0"<?php echo empty($this->data['hedge']) ? ' checked="checked"' : '' ?> /><?php _e('Month hedge', $this->domain) ?></label>&nbsp;
                <label><input type="radio" name="mts_simple_booking_controls[hedge]" value="1"<?php echo $this->data['hedge'] ? ' checked="checked"' : '' ?> /><?php _e('Day hedge', $this->domain) ?></label><br />
                <?php _e('Open booking in whole month or the day before of after the period month.', $this->domain);
                break;
			case 'awaking' : ?>
				<input type="hidden" name="mts_simple_booking_controls[awaking][mail]" value="0">
			    <input id="awaking" type="checkbox" name="mts_simple_booking_controls[awaking][mail]" value="1"<?php echo $this->_cmpChecked($this->data['awaking']['mail'], 1) ?>>&nbsp;
				<span id="awaking-time-area"><?php _e('Every hour', $this->domain) ?>
					<select name="mts_simple_booking_controls[awaking][time]"><?php for ($i = 0; $i < 60; $i++) {
						echo sprintf('<option value="%s"%s>%d</option>', $i, $this->_cmpSelected($i, $this->data['awaking']['time']), $i);
					} ?></select> <?php _e('Minute', $this->domain) ?>
					<span id="awaking-crontab-area">
						<input type="hidden" name="mts_simple_booking_controls[awaking][crontab]" value="0">
						<input id="awaking-crontab" type="checkbox" name="mts_simple_booking_controls[awaking][crontab]" value="1"<?php echo $this->_cmpChecked($this->data['awaking']['crontab'], 1) ?>>
						<label for="awaking-crontab"><?php _e('Using CRON', $this->domain) ?></label>
					</span>
				</span><br>
				<?php _e('If you need to send the email before booking date, set the checkbox please.', $this->domain);
				break;
			case 'vacant_mark' : ?>
				<input id="vacant_mark" type="text" name="mts_simple_booking_controls[vacant_mark]" value="<?php echo esc_html($this->data['vacant_mark']) ?>" /><br />
				<?php _e("The mark which shows that there are enough openings of reservation.", $this->domain);
				break;
			case 'booked_mark' : ?>
				<input id="booked_mark" type="text" name="mts_simple_booking_controls[booked_mark]" value="<?php echo esc_html($this->data['booked_mark']) ?>" /><br />
				<?php _e("The mark which shows that there are some bookings.", $this->domain);
				break;
			case 'low_mark' : ?>
				<input id="low_mark" type="text" name="mts_simple_booking_controls[low_mark]" value="<?php echo esc_html($this->data['low_mark']) ?>" /><br />
				<?php _e("The mark which shows that there are few openings of reservation.", $this->domain);
				break;
			case 'full_mark' : ?>
				<input id="full_mark" type="text" name="mts_simple_booking_controls[full_mark]" value="<?php echo esc_html($this->data['full_mark']) ?>" /><br />
				<?php _e("The mark which shows that reservation is full and it cannot reserve.", $this->domain);
				break;
			case 'disable' : ?>
				<input id="disable" type="text" name="mts_simple_booking_controls[disable]" value="<?php echo esc_html($this->data['disable']) ?>" /><br />
				<?php _e("The mark which shows not accepting reservation.", $this->domain);
				break;
			case 'vacant_rate' : ?>
				<input id="vacant_rate" type="text" name="mts_simple_booking_controls[vacant_rate]" value="<?php echo esc_html($this->data['vacant_rate']) ?>" style="width:3em" /><br />
				<?php _e("Full mark is displayed until the rate becomes to the percentage.", $this->domain);
				break;
			case 'count' : ?>
				<input type="hidden" name="mts_simple_booking_controls[count][adult]" value="0" />
				<label><?php _e('Adult', $this->domain) ?>:<input id="count" type="checkbox" name="mts_simple_booking_controls[count][adult]" value="1"<?php echo $this->data['count']['adult'] ? ' checked="checked"' : '' ?> /></label>
				<input type="hidden" name="mts_simple_booking_controls[count][child]" value="0" />
				<label><?php _e('Child', $this->domain) ?>:<input type="checkbox" name="mts_simple_booking_controls[count][child]" value="1"<?php echo $this->data['count']['child'] ? ' checked="checked"' : '' ?> /></label>
				<input type="hidden" name="mts_simple_booking_controls[count][baby]" value="0" />
				<label><?php _e('Baby', $this->domain) ?>:<input type="checkbox" name="mts_simple_booking_controls[count][baby]" value="1"<?php echo $this->data['count']['baby'] ? ' checked="checked"' : '' ?> /></label>
				<input type="hidden" name="mts_simple_booking_controls[count][car]" value="0" />
				<label><?php _e('Car', $this->domain) ?>:<input type="checkbox" name="mts_simple_booking_controls[count][car]" value="1"<?php echo $this->data['count']['car'] ? ' checked="checked"' : '' ?> /></label><br />
				<?php _e('Checked items is included in reservation form page.', $this->domain);
				break;
			case 'message' : ?>
				<input type="hidden" name="mts_simple_booking_controls[message][temps_utile]" value="0" />
				<label><input id="message" name="mts_simple_booking_controls[message][temps_utile]" value="1" type="checkbox"<?php echo $this->data['message']['temps_utile'] ? ' checked="checked"' : '' ?> /> <?php _e('Entrance and exit schedule', $this->domain) ?></label><br />
				<?php _e('Check to use the input item of entrance and exit schedule time.', $this->domain);
				break;
			default :
				break;
		}
	}

	private function _cmpChecked($v1, $v2)
	{
		return $v1 == $v2 ? ' checked="checked"' : '';
	}

	private function _cmpSelected($v1, $v2)
	{
		return $v1 == $v2 ? ' selected="selected"' : '';
	}

	/**
	 * Miscellaneous form
	 *
	 */
	public function miscellaneous_form($args) {
		switch ($args['label_for']) {
			case 'adminbar' : ?>
				<label id="adminbar_off"><input type="radio" name="mts_simple_booking_miscellaneous[adminbar]" value="0"<?php echo empty($this->data['adminbar']) ? ' checked="checked"' : '' ?> /><?php _e('Enable', $this->domain) ?></label>&nbsp;
				<label id="adminbar_on"><input type="radio" name="mts_simple_booking_miscellaneous[adminbar]" value="1"<?php echo $this->data['adminbar'] ? ' checked="checked"' : '' ?> /><?php _e('Disable', $this->domain) ?></label><br />
				<?php _e('Display adminbar out or not on front end page.', $this->domain);
				break;
			case 'schedule_dialog' : ?>
				<label id="schedule_dialog_off"><input type="radio" name="mts_simple_booking_miscellaneous[schedule_dialog]" value="0"<?php echo empty($this->data['schedule_dialog']) ? ' checked="checked"' : '' ?> /><?php _e('Inactive', $this->domain) ?></label>&nbsp;
				<label id="schedule_dialog_on"><input type="radio" name="mts_simple_booking_miscellaneous[schedule_dialog]" value="1"<?php echo !empty($this->data['schedule_dialog']) ? ' checked="checked"' : '' ?> /><?php _e('Active', $this->domain) ?></label><br />
				<?php _e('Selection of using dialog box when it is enterd a note.', $this->domain);
				break;
			default :
				break;
		}
	}

	/**
	 * Premise Form
	 *
	 */
	public function premise_form($args) {
		switch ($args['label_for']) {
			case 'name' : ?>
				<input id="name" type="text" name="mts_simple_booking_premise[name]" value="<?php echo esc_html($this->data['name']) ?>" style="width:80%" />
				<?php break;
			case 'postcode' : ?>
				<input id="postcode" type="text" name="mts_simple_booking_premise[postcode]" value="<?php echo esc_html($this->data['postcode']) ?>" class="30%" />
				<?php break;
			case 'address1' : ?>
                <input id="address1" type="text" name="mts_simple_booking_premise[address1]" value="<?php echo esc_html($this->data['address1']) ?>" style="width:80%" />
				<?php break;
			case 'address2' : ?>
				<input id="address2" type="text" name="mts_simple_booking_premise[address2]" value="<?php echo esc_html($this->data['address2']) ?>" style="width:80%" />
				<?php break;
			case 'tel' : ?>
				<input id="tel" type="text" name="mts_simple_booking_premise[tel]" value="<?php echo esc_html($this->data['tel']) ?>" style="width:30%" />
				<?php break;
			case 'fax' : ?>
				<input id="fax" type="text" name="mts_simple_booking_premise[fax]" value="<?php echo esc_html($this->data['fax']) ?>" style="width:30%" />
				<?php break;
			case 'email' : ?>
				<input id="email" type="text" name="mts_simple_booking_premise[email]" value="<?php echo esc_html($this->data['email']) ?>" style="width:80%" /><br />
				<?php _e("e.g. webmaster@example.com", $this->domain);
				break;
			case 'mobile' : ?>
				<input id="mobile" type="text" name="mts_simple_booking_premise[mobile]" value="<?php echo esc_html($this->data['mobile']) ?>" style="width:80%" /><br />
				<?php _e("A mobile phone will be mailed if its mail address is inputed, and reservation enters.", $this->domain);
				break;
			case 'web' : ?>
				<input id="web" type="text" name="mts_simple_booking_premise[web]" value="<?php echo esc_html($this->data['web']) ?>" style="width:80%" /><br />
				<?php _e("e.g. http://www.example.com", $this->domain);
				break;
            case 'zip_search' :
                $options = array('----', 'Google(利用不可)', 'PostcodeJP'); ?>
                <select id="zip_search" name="mts_simple_booking_premise[zip_search]"><?php foreach ($options as $key => $val) : ?>
                    <option value="<?php echo $key ?>"<?php echo $key == $this->data['zip_search'] ? ' selected="selected"' : '' ?>><?php echo $val ?></option>
                <?php endforeach; ?></select><br />
                <?php break;
            case 'api_key' : ?>
                <input id="api_key" type="text" name="mts_simple_booking_premise[api_key]" value="<?php echo esc_html($this->data['api_key']) ?>" style="width:80%" />
                <?php break;
			default :
				break;
		}
	}

	/**
	 * Charge form
	 *
	 */
	public function charge_form($args) {
		switch ($args['label_for']) {
			case 'accedence' : ?>
				<input type="hidden" name="mts_simple_booking_charge[accedence]" value="0" />
				<input id="accedence" name="mts_simple_booking_charge[accedence]" value="1" type="checkbox"<?php echo $this->data['accedence'] ? ' checked="checked"' : '' ?> /><br />
				<?php _e('Customer can not proceed booking if not checked this.', $this->domain);
				break;
			case 'terms_url' : ?>
				<input id="terms_url" type="text" name="mts_simple_booking_charge[terms_url]" value="<?php echo  esc_html($this->data['terms_url']) ?>" style="width: 20rem" /><br />
				<?php _e("Terms and conditions page URL.", $this->domain);
				break;
			case 'charge_list' : ?>
				<input type="hidden" name="mts_simple_booking_charge[charge_list]" value="0" />
				<input id="charge_list" name="mts_simple_booking_charge[charge_list]" value="1" type="checkbox"<?php echo $this->data['charge_list'] ? ' checked="checked"' : '' ?> /><br />
				<?php _e('Check to display charge list at booking.', $this->domain);
				break;
			case 'currency_code' :
				$currencies = $this->_get_currency_code(); ?>
				<select id="currency_code" name="mts_simple_booking_charge[currency_code]" style="letter-spacing:2px"><?php foreach ($currencies as $key => $currency) : ?>
					<option value="<?php echo $key ?>"<?php echo $key == $this->data['currency_code'] ? ' selected="selected"' : '' ?>><?php echo $currency ?></option>
				<?php endforeach; ?></select><br />
				<?php _e('Select the currency code.', $this->domain);
				break;
			case 'tax_notation' : ?>
				<label><input type="radio" name="mts_simple_booking_charge[tax_notation]" value="0"<?php echo $this->data['tax_notation'] == 0 ? ' checked="checked"' : '' ?> /> <?php _e('Nothing', $this->domain) ?></label>
				<label><input type="radio" name="mts_simple_booking_charge[tax_notation]" value="1"<?php echo $this->data['tax_notation'] == 1 ? ' checked="checked"' : '' ?> /> <?php _e('Included', $this->domain) ?></label>
				<label><input type="radio" name="mts_simple_booking_charge[tax_notation]" value="2"<?php echo $this->data['tax_notation'] == 2 ? ' checked="checked"' : '' ?> /> <?php _e('Excluded', $this->domain) ?></label><br />
				<?php _e('If tax-inclusive is selected, tax cost is not list up on the PayPal.', $this->domain);
				break;
			case 'consumption_tax' : ?>
				<input id="consumption_tax" type="text" style="width:3em" name="mts_simple_booking_charge[consumption_tax]" value="<?php echo esc_html($this->data['consumption_tax']) ?>" /> %<br />
				<?php _e("Consumption tax rate.", $this->domain);
				break;
			case 'pay_first' : ?>
				<input type="hidden" name="mts_simple_booking_charge[pay_first]" value="0" />
				<input id="pay_first" name="mts_simple_booking_charge[pay_first]" value="1" type="checkbox"<?php echo $this->data['pay_first'] ? ' checked="checked"' : '' ?> /><br />
				<?php _e('Customer can not proceed booking without payment.', $this->domain);
				break;
			case 'checkout' : ?>
				<input type="hidden" name="mts_simple_booking_charge[checkout]" value="0" />
				<input id="checkout" name="mts_simple_booking_charge[checkout]" value="1" type="checkbox"<?php echo $this->data['checkout'] ? ' checked="checked"' : '' ?> /><br />
				<?php _e('Check if you use PayPal checkout. (e.g. www.example.com)', $this->domain);
				break;
            case 'checkout_button' : ?>
                <input id="checkout_button" type="text" name="mts_simple_booking_charge[checkout_button]" value="<?php echo esc_html($this->data['checkout_button']) ?>" style="width:80%" /><br />
                <?php _e('Set the image URL to display which you use the button image of PayPals.', $this->domain);
                break;
			case 'settled_mail' : ?>
				<textarea id="settled_mail" class="large-text" cols="60" rows="12" name="mts_simple_booking_charge[settled_mail]"><?php echo esc_textarea($this->data['settled_mail']) ?></textarea><br />
				<?php _e("Sentence is included into booking mail if customer paid using paypal when booking.", $this->domain);
				break;
			case 'unsettled_mail' : ?>
				<textarea id="unsettled_mail" class="large-text" cols="60" rows="12" name="mts_simple_booking_charge[unsettled_mail]"><?php echo esc_textarea($this->data['unsettled_mail']) ?></textarea><br />
				<?php _e("Sentence is included into booking mail if customer not pay when booking.", $this->domain);
				break;
			default :
				break;
		}
	}

	/**
	 * PayPal form
	 *
	 */
	public function paypal_form($args) {
		switch ($args['label_for']) {
			case 'pp_username' : ?>
				<input id="pp_username" type="text" name="mts_simple_booking_paypal[pp_username]" value="<?php echo esc_html($this->data['pp_username']) ?>" style="width: 20rem" /><br />
				<?php _e("PayPal API Username.", $this->domain);
				break;
			case 'pp_password' : ?>
				<input id="pp_password" type="text" name="mts_simple_booking_paypal[pp_password]" value="<?php echo esc_html($this->data['pp_password']) ?>" style="width: 20rem" /><br />
				<?php _e("PayPal API Password.", $this->domain);
				break;
			case 'pp_signature' : ?>
				<input id="pp_signature" type="text" name="mts_simple_booking_paypal[pp_signature]" value="<?php echo esc_html($this->data['pp_signature']) ?>" style="width: 20rem" /><br />
				<?php _e("PayPal API Signature.", $this->domain);
				break;
			case 'https_url' : ?>
				<input id="https_url" type="text" name="mts_simple_booking_paypal[https_url]" value="<?php echo  esc_html($this->data['https_url']) ?>" style="width: 20rem" /><br />
				<?php _e("HTTPS URL of this web site.", $this->domain);
				break;
			case 'logo_url' : ?>
				<input id="logo_url" type="text" name="mts_simple_booking_paypal[logo_url]" value="<?php echo  esc_html($this->data['logo_url']) ?>" style="width: 20rem" /><br />
				<?php _e("HTTPS URL of the logo of your company.", $this->domain);
				break;
            case 'use_sandbox' : ?>
                <input type="hidden" id="use_sandbox_" name="mts_simple_booking_paypal[use_sandbox]" value="0" />
                <input id="use_sandbox" name="mts_simple_booking_paypal[use_sandbox]" value="1" type="checkbox"<?php echo $this->data['use_sandbox'] ? ' checked="checked"' : '' ?> /><br />
                <?php _e('Use the PayPal Sandbox.', $this->domain);
                break;
			default :
				break;
		}
	}

	/**
	 * Reservation Mail Option Format
	 *
	 */
	public function reserve_form($args) {
		switch ($args['label_for']) {
			case 'column' :
				$options = array(__('Unnecessary', $this->domain), __('Required', $this->domain), __('Arbitrary', $this->domain));
				$items = $this->_get_default('reserve');
				foreach ($items['column'] as $colname => $val) : ?>
					<p><?php _e(ucwords($colname), $this->domain) ?><br />
					<select id="client_column" name="mts_simple_booking_reserve[column][<?php echo $colname ?>]">
						<?php foreach ($options as $key => $optname) : ?>
						<option value="<?php echo $key ?>"<?php echo isset($this->data['column'][$colname]) && $this->data['column'][$colname] == $key ? ' selected="selected"' : '' ?>><?php echo $optname ?></option>
						<?php endforeach; ?>
					</select></p>
				<?php endforeach;
				break;
			case 'column_order' : ?>
				<input id="client_column_order" class="large-text" type="text" name="mts_simple_booking_reserve[column_order]" value="<?php echo esc_html($this->data['column_order']) ?>" /><br />
				<?php _e("These are order of input columns on the booking form.", $this->domain);
				break;
			case 'title' : ?>
				<input id="client_title" class="regular-text" type="text" name="mts_simple_booking_reserve[title]" value="<?php echo esc_html($this->data['title']) ?>" /><br />
				<?php _e("The subject of the automatic reply mail when reserving on the site.", $this->domain);
				break;
			case 'header' : ?>
				<textarea id="client_header" class="large-text" cols="60" rows="12" name="mts_simple_booking_reserve[header]"><?php echo esc_textarea($this->data['header']) ?></textarea><br />
				<?php _e("Above sentence of the automatic reply mail.", $this->domain);
				break;
			case 'footer' : ?>
				<textarea id="client_footer" class="large-text" cols="60" rows="12" name="mts_simple_booking_reserve[footer]"><?php echo esc_textarea($this->data['footer']) ?></textarea><br />
				<?php _e("Below sentence of the automatic reply mail.", $this->domain);
				break;
			case 'cancel_title' : ?>
				<input id="cancel_title" class="regular-text" type="text" name="mts_simple_booking_reserve[cancel_title]" value="<?php echo esc_html($this->data['cancel_title']) ?>" /><br />
				<?php _e("The subject of the automatic cancele mail.", $this->domain);
				break;
			case 'cancel_body' : ?>
				<textarea id="cancel_body" class="large-text" cols="60" rows="12" name="mts_simple_booking_reserve[cancel_body]"><?php echo esc_textarea($this->data['cancel_body']) ?></textarea><br />
				<?php _e("Cancele mail sentence of the automatic reply mail.", $this->domain);
				break;
			default :
				break;
		}
	}

	/**
	 * Contact Mail Option Format
	 *
	 */
	public function contact_form($args) {
		switch ($args['label_for']) {
			case 'column' :
				$options = array(__('Unnecessary', $this->domain), __('Required', $this->domain), __('Arbitrary', $this->domain));
				$items = $this->_get_default('contact');
				foreach ($items['column'] as $colname => $val) : ?>
					<p><?php _e(ucwords($colname), $this->domain) ?><br />
					<select id="contact_column" name="mts_simple_booking_contact[column][<?php echo $colname ?>]">
						<?php foreach ($options as $key => $optname) : ?>
						<option value="<?php echo $key ?>"<?php echo isset($this->data['column'][$colname]) && $this->data['column'][$colname] == $key ? ' selected="selected"' : '' ?>><?php echo $optname ?></option>
						<?php endforeach; ?>
					</select></p>
				<?php endforeach;
				break;
			case 'title' : ?>
				<input id="contact-title" class="regular-text" type="text" name="mts_simple_booking_contact[title]" value="<?php echo esc_html($this->data['title']) ?>" /><br />
				<?php _e("The subject of the automatic reply mail when reserving on the site.", $this->domain);
				break;
			case 'header' : ?>
				<textarea id="contact-header" class="large-text" cols="60" rows="12" name="mts_simple_booking_contact[header]"><?php echo esc_textarea($this->data['header']) ?></textarea><br />
				<?php _e("Above sentence of the automatic reply mail.", $this->domain);
				break;
			case 'footer' : ?>
				<textarea id="contact-footer" class="large-text" cols="60" rows="12" name="mts_simple_booking_contact[footer]"><?php echo esc_textarea($this->data['footer']) ?></textarea><br />
				<?php _e("Below sentence of the automatic reply mail.", $this->domain);
				break;
			default :
				break;
		}
	}

	/**
	 * Output footer description of the option
	 *
	 */
	private function _footer_description() {
		if ($this->tab == 'reserve') : ?>
			<p><?php _e("The following variables can be used in Mail Header and Footer.", $this->domain) ?></p>
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
				<li>%CANCEL_URL%</br><?php _e("The URL to cancel the booking in the cancel mail only.", $this->domain) ?></li>
			</ul>
		<?php elseif ($this->tab == 'contact') : ?>
			<p><?php _e("The following variables can be used in Mail Header and Footer.", $this->domain) ?></p>
			<ul class="ul-description">
				<li>%CLIENT_NAME%</br><?php _e("Reservation application guest's name.", $this->domain) ?></li>
				<li>%NAME%</br><?php _e("Shop Name", $this->domain) ?></li>
				<li>%POSTCODE%</br><?php _e("Post Code", $this->domain) ?></li>
				<li>%ADDRESS%</br><?php _e("Address", $this->domain) ?></li>
				<li>%TEL%</br><?php _e("TEL Number", $this->domain) ?></li>
				<li>%FAX%</br><?php _e("FAX Number", $this->domain) ?></li>
				<li>%EMAIL%</br><?php _e("E-Mail", $this->domain) ?></li>
				<li>%WEB%</br><?php _e("Web Site", $this->domain) ?></li>
			</ul>
		<?php elseif ($this->tab == 'charge') : ?>
			<p><?php _e("The following variables can be used in the Paid Sentence.", $this->domain) ?></p>
			<ul class="ul-description">
				<li>%TRANSACTION_ID%</br><?php _e("PayPal transaction ID when customer will have been paid.", $this->domain) ?></li>
			</ul>
		<?php endif;
	}

	/**
	 * オプションデータを新規登録、更新する
	 *
	 */
	private function _install_option() {

		foreach ($this->tabs as $tab) {

		// 登録オプションデータとモジュールの初期オプションデータを読込む
			$option_name = "{$this->domain}_$tab";

			$option = get_option($option_name);
			$default = $this->_get_default($tab);

			// 未登録なら新規登録する
			if (empty($option)) {
				add_option($option_name, $default);
				continue;
			}

			// 新旧オプションデータを比較し、異なるキーがあれば更新する
			$new_keys = array_keys($default);
			sort($new_keys);
			$opt_keys = array_keys($option);
			sort($opt_keys);
			if ($new_keys == $opt_keys) {
				continue;
			}

			// オプションデータの更新
			foreach ($default as $key => $val) {
				if (array_key_exists($key, $option)) {
					$default[$key] = $option[$key];
				}
			}
			update_option($option_name, $default);
		}
	}

	/**
	 * 設定オプションデータの初期値取得
	 *
	 */
	private function _get_default($tab) {

		$options = array(
			'controls' => array(
				'available' => 0,			// 予約受付中
				'closed_page' => '受付は終了しました。',	// 予約受付中止中のメッセージ
				'start_accepting' => 1440,	// 予約受付終了日(単位：分)
                'until_accepting' => 0,     // 予約受付終了時刻(単位：分)
				'cancel' => 0,				// キャンセルの受付
				'output_margin' => 0,		// 受け付け開始マージンのマーク表示
				'period' => 6,				// 予約受付期間
                'hedge' => 0,               // 予約受付開始区切り
				'awaking' => array(			// 予約事前メール送信
					'mail' => 0,
					'time' => 0,
					'crontab' => 0,
				),
				'vacant_mark' => '○',		// 予約カレンダー受付中記号
				'booked_mark' => '☆',		// 予約カレンダー予約入記号
				'low_mark' => '△',			// 予約カレンダー残数僅少記号
				'full_mark' => '×',			// 予約カレンダー受付終了記号
				'disable' => '－',			// 予約カレンダー予約不可記号
				'vacant_rate' => 30,		// 残数僅少を表示する残数率(%)
				'count' => array(
					'adult' => 1,
					'child' => 0,
					'baby' => 0,
					'car' => 0,
				),
				'message' => array(
					'temps_utile' => 0,		// 入退場時間の入力
				),
			),

			'miscellaneous' => array(
				'adminbar' => 0,		// Admin bar 非表示
				'schedule_dialog' => 0,	// Note ダイアログ入力非使用
			),

			'premise' => array(
				'name' => '',
				'postcode' => '',
				'address1' => '',
				'address2' => '',
				'tel' => '',
				'fax' => '',
				'email' => '',
				'mobile' => '',
				'web' => '',
                'zip_search' => 0,          // 郵便番号検索(0:利用しない,1:Google Geocoding,2:PostcodeJP)
                'api_key' => '',            // Web APIを利用するためのアクセスキー
			),

			'charge' => array(
				'charge_list' => 0,			// 予約処理で料金表を表示する
				'currency_code' => 'JPY',	// 通貨 円
				'tax_notation' => 0,		// 消費税表記(0:しない,1:内税,2:外税)
				'consumption_tax' => 0,		// 消費税率(%)
				'terms_url' => '',			// 同意書のリンクURL
				'accedence' => 0,			// 同意書チェック
				'checkout' => 0,			// PayPalを利用した決済を用意する
                'checkout_button' => 'https://www.paypal.com/ja_JP/i/btn/btn_xpressCheckout.gif',
				'pay_first' => 0,			// 決済実行のみ予約できるようにする
				'unsettled_mail' => "※お支払いについて※\n\n"
					. "この度はご予約いただき誠にありがとうございます。お支払いについ\n"
					. "てご案内いたします。\n\n"
					. "銀行への振り込みはこのメールを受取った日から3日以内に、次の銀\n"
					. "行口座へお振り込み下さい。期日を過ぎましてもお振り込みが確認で\n"
					. "ない場合は、予めご連絡がない限りご予約をキャンセルさせていただ\n"
					. "きます。\n\n"
					. "銀行支店名：○○銀行○○支店\n"
					. "口座番号：普通口座 0000000\n"
					. "口座名義：○○（カ\n",
				'settled_mail' => "※お支払いについて※\n\n"
					. "この度はご入金いただき誠にありがとうございました。お取り引きID\n"
					. "は次の通りです。\n\n"
					. "お取り引きID：%TRANSACTION_ID%\n\n"
					. "キャンセルの受付は10日前までとなります。それ以降3日前まで料金\n"
					. "の50%、それを過ぎると全額を違約金としてお支払いいただきます。\n"
					. "ご不明な点がありましたらお気軽にお問合わせ下さい。\n\n",
			),

			'paypal' => array(
				'pp_username' => '',
				'pp_password' => '',
				'pp_signature' => '',
				'https_url' => '',
				'logo_url' => '',
                'use_sandbox' => false,
			),

			'reserve' => array(
				'column' => array(		// 0:不要 1:必須 2:任意
					'company' => 1,
					'name' => 1,
					'furigana' => 2,
					'birthday' => 0,
					'gender' => '',
					'email' => 1,
					'postcode' => 2,
					'address' => 2,
					'tel' => 1,
					'newuse' => 0,
				),
				'column_order' => 'company,name,furigana,birthday,gender,email,postcode,address,tel,newuse',
				'title' => '【ご予約を承りました】',
				'header' => "%CLIENT_NAME% 様\n"
					 . "ご予約ID：%RESERVE_ID%\n\n"
					 . "当%NAME%をご利用いただき誠にありがとうございます。\n\n"
					 . "以下の内容でご予約を承りました。詳細が確認できましたら後ほど予\n"
					 . "約完了のメールをお送りします。なお、内容に不明な点などあった場\n"
					 . "合、こちらからお問合わせさせていただく場合がございますのでご了\n"
					 . "承下さい。\n\n"
					 . "翌日になりましても完了メールをお受け取りできないようでしたら、\n"
					 . "回線事情などによりデータの喪失も考えられますので、お手数をお掛\n"
					 . "けしますがご連絡下さいますようお願い申し上げます。\n\n",
				'footer' => "このメールにお心当たりが無い場合、以下へご連絡下さるよう\n"
					 . "お願い申し上げます。\n\n"
					 . "%NAME%\n"				// 施設名
					 . "%POSTCODE%\n"			// 郵便番号
					 . "%ADDRESS%\n"			// 住所
					 . "TEL: %TEL%\n"			// TEL
					 . "E-Mail: %EMAIL%\n"		// E-Mail
					 . "Webサイト: %WEB%",		// Webサイト
				'cancel_title' => '【ご予約のキャンセル】',
				'cancel_body' => "%CLIENT_NAME% 様\n\n"
					 . "当%NAME%をご利用いただき誠にありがとうございます。\n\n"
					 . "以下のURLをブラウザで開くと予約キャンセルが実行されます。\n\n"
					 . "%CANCEL_URL%\n\n"
					 . "またの機会をお待ちしております。\n\n"
					 . "%NAME%\n"				// 施設名
					 . "%POSTCODE%\n"			// 郵便番号
					 . "%ADDRESS%\n"			// 住所
					 . "TEL: %TEL%\n"			// TEL
					 . "E-Mail: %EMAIL%\n"		// E-Mail
					 . "Webサイト: %WEB%",		// Webサイト
			),

			'contact' => array(
				'column' => array(		// 0:不要 1:必須 2:任意
					'company' => 2,
					'name' => 1,
					'furigana' => 0,
					'email' => 1,
					'postcode' => 0,
					'address' => 0,
					'tel' => 0,
				),
				'title' => '【お問合わせ送信のご案内】',
				'header' => "%CLIENT_NAME% 様\n\n"
					. "お問合わせありがとうございます。以下の内容で承りました。\n"
					. "後ほど担当者よりご連絡いたしますのでしばらくお待ち下さい。\n\n"
					. "なお回線事情等によりメールが届かない事もありますので、\n"
					. "おかしいと感じられましたら、お手数ですが改めてお知らせ下さい。\n\n",
				'footer' => "このメールにお心当たりが無い場合、以下へご連絡下さるよう\n"
					. "お願い申し上げます。\n\n"
					. "%NAME%\n"
					. "%TEL%\n"
					. "E-Mail: %EMAIL%\n"
					. "Webサイト: %WEB%\n",
			),
		);

		return $options[$tab];
	}

	/**
	 * 予約のマージン選択肢取得
	 *
	 */
	private function _get_booking_margin()
	{
		return apply_filters('mtssb_settings_get_booking_margin', array(
			'10' => __('10 Minutes', $this->domain),
			'30' => __('30 Minutes', $this->domain),
			'60' => __('1 Hour', $this->domain),
			'180' => __('3 Hours', $this->domain),
			'360' => __('6 Hours', $this->domain),
			'720' => __('12 Hours', $this->domain),
			'1440' => __('1 Day', $this->domain),
			'2880' => __('2 Days', $this->domain),
			'4320' => __('3 Days', $this->domain),
			'5760' => __('4 Days', $this->domain),
			'7200' => __('5 Days', $this->domain),
			'8640' => __('6 Days', $this->domain),
		));
	}

	/**
	 * 通貨単位の選択肢取得
	 *
	 */
	private function _get_currency_code()
	{
		return array(
			'JPY' => __('Yen' , $this->domain),
			'USD' => __('US dollar', $this->domain),
		);
	}

	/**
	 * 予約受付期間の取得
	 *
	 */
	private function _get_period_months()
	{
		return apply_filters('mtssb_settings_get_period_months', 6);
	}
}
