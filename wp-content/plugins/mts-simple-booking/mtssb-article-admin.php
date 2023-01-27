<?php
if (!class_exists('MTSSB_Article')) {
	require_once('mtssb-article.php');
    require_once('mtssb-mail-template.php');
	require_once(__DIR__ . '/lib/MtssbFormCommon.php');
}
/**
 * カスタムポストタイプ予約品目 管理画面処理
 *
 * @Filename	mtssb-article-admin.php
 * @Date		2012-04-19
 * @Author		S.Hayashi
 *
 * Updated to 1.28.0 on 2017-12-19
 * Updated to 1.24.0 on 2016-07-26
 * Updated to 1.23.1 on 2016-05-16
 * Updated to 1.22.0 on 2015-07-17
 * Updated to 1.21.0 on 2014-12-26
 * Updated to 1.17.0 on 2014-07-13
 * Updated to 1.14.0 on 2014-01-15
 * Updated to 1.9.5 on 2013-09-04
 * Updated to 1.9.0 on 2013-07-17
 * Updated to 1.8.0 on 2013-05-28
 * Updated to 1.7.0 on 2013-05-11
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.2.5 on 2012-12-27
 * Updated to 1.0.2 on 2012-10-09
 */
class MTSSB_Article_Admin extends MTSSB_Article
{
	private $oCForm;

	/**
	 * Private Variables
	 */
	private $module_name;		// mtssb-article-admin
	private $nonce_name;		// mtssb-article-admin_nonce
	private $nonce_timetable;	// mtsbb-article-admin_nonce_timetable

	private $article = null;

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		global $mts_simple_booking;

		parent::__construct();

		// Set nonce variables
		$this->module_name = basename(__FILE__, '.php');
		$this->nonce_name = $this->module_name . '_nonce';
		$this->nonce_timetable = $this->nonce_name . '_timetable';

		// Register fook procedure to save custom fields
		add_action('save_post', array($this, 'save_custom_fields'));

		// Register fook procedure to display MTSBB Room list view
		add_filter("manage_edit-" . self::POST_TYPE . "_columns", array($this, 'get_column_titles'));

		// カスタム投稿タイプのedit.phpに表示するカスタムカラム処理のフック
		add_action('manage_posts_custom_column', array($this, 'out_custom_column'));

		// Load JavaScript at post.php
		add_action("admin_print_scripts-post.php", array($this, 'post_enqueue_script'));
		add_action("admin_print_scripts-post-new.php", array($this, 'post_enqueue_script'));

		// AJAX登録
		//add_action('wp_ajax_mtssb_get_timetable', array($this, 'ajax_get_the_timetable'));

		// CSSロード
		$mts_simple_booking->enqueue_style();

		$this->oCForm = new MtssbFormCommon();
	}

	/**
	 * Enqueue scripts to load JavaScript at post.php
	 *
	 */
	public function post_enqueue_script() {
		global $post;

		if ($post->post_type == self::POST_TYPE) {
			wp_enqueue_script($this->module_name . '-js', plugin_dir_url(__FILE__) . "js/mtssb-article-admin.js", array('jquery'));
		}
	}

	/**
	 * Add Meta Box of Room's Custom fields
	 *
	 */
	public function register_meta_box() {
		add_meta_box($this->module_name . '_timetable', __('Booking Timetable', $this->domain),
			array($this, 'meta_box_timetable'), self::POST_TYPE, 'normal', 'low');
		add_meta_box($this->module_name . '_provision', __('Booking Provisions', $this->domain),
			array($this, 'meta_box_provision'), self::POST_TYPE, 'normal', 'low');
		add_meta_box($this->module_name . '_charge', __('Booking Charge', $this->domain),
			array($this, 'meta_box_charge'), self::POST_TYPE, 'normal', 'low');
		add_meta_box($this->module_name . '_miscellaneous', __('Booking Miscellaneous', $this->domain),
			array($this, 'meta_box_miscellaneous'), self::POST_TYPE, 'normal', 'low');
	}

	/**
	 * 予約の時間割編集画面
	 *
	 */
	public function meta_box_timetable($post) {

		$timetable = self::get_the_timetable($post->ID);

		if (empty($timetable)) {
			$timetable = array();
		}

		ob_start();
?>
	<table class="form-table">
		<tr>
			<th scope="row"><label><?php _e('Start Time', $this->domain) ?></label></th>
			<td>
				<select id="timetable-hour" name="start[hour]">
					<?php echo $this->_out_option_hour() ?>
				</select><?php _e('Hour', $this->domain) ?>
				<select id="timetable-minute" name="start[minute]">
					<?php echo $this->_out_option_minute(0, apply_filters('mtssb_article_admin_minute', 5)) ?>
				</select><?php _e('Minute', $this->domain) ?> <a id="add-timetable" class="add-timetable button" href="javascript:void(0)" onclick="timeop.add(this)"><?php _e('Add time', $this->domain) ?></a>
				<p class="article-description"><?php _e('The reservation time of this reservation item.', $this->domain) ?></p>
				<div id="article-timetable">
					<input type="hidden" name="article[timetable]" value="" />
					<ul id="article-list">
						<?php
						if (empty($timetable)) {
								echo '<li><input type="hidden" name="article[timetable][36000]" value="36000" />10:00 <a href="javascript:void(0)" onclick="timeop.delete(this)"> ' . __('Delete') . "</a></li>\n";
							} else {
								foreach ($timetable as $time) {
									echo "<li><input type=\"hidden\" name=\"article[timetable][$time]\" value=\"$time\" />" . date('H:i ', $time)
									 . ' <a href="javascript:void(0)" onclick="timeop.delete(this)"> ' . __('Delete') . "</a></li>\n";
								}
							} ?>
					</ul>
					<div id="delete-title" style="display:none"><?php _e('Delete') ?></div>
				</div>
			</td>
		</tr>
	</table>

<?php
		ob_end_flush();
	}

	// 予約品目を読み出す
	private function _get_the_article($postId)
	{
		if (!empty($this->article) && $postId == $this->article['article_id']) {
			return $this->article;
		}

		$this->article = self::get_the_article($postId);
		if (empty($this->article) || $this->article['post_status'] == 'auto-draft') {
			$this->article = self::get_new_article();
		}

		return $this->article;
	}

	/**
	 * 予約品目の予約条件編集画面
	 *
	 */
	public function meta_box_provision($post)
	{
		$article = $this->_get_the_article($post->ID);

		ob_start();
?>
	<input type="hidden" name="<?php echo $this->nonce_name ?>" value="<?php echo wp_create_nonce($this->module_name) ?>" />

	<table class="form-table">
		<tr>
			<th scope="row"><label for="article-restriction"><?php _e('Restriction', $this->domain) ?></label></th>
			<td>
				<select id="article-restriction" name="article[restriction]">
					<option value="capacity"<?php echo $article['restriction'] == 'capacity' ? ' selected="selected"' : '' ?>><?php _e('Capacity', $this->domain) ?></option>
					<option value="quantity"<?php echo $article['restriction'] == 'quantity' ? ' selected="selected"' : '' ?>><?php _e('Quantity', $this->domain) ?></option>
				</select>
				<p class="article-description"><?php _e('Choose the conditions which restrict reservation.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-capacity"><?php _e('Fixed Number', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-capacity" class="small-text" name="article[capacity]" value="<?php echo $article['capacity'] ?>" /> <?php _e('Persons', $this->domain) ?>
				<p class="article-description"><?php _e('The maximum number which accepts this reservation item.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-quantity"><?php _e('Booking Limit', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-quantity" class="small-text" name="article[quantity]" value="<?php echo $article['quantity'] ?>" /> <?php _e('Items', $this->domain) ?>
				<p class="article-description"><?php _e('The maximum reservation number which accepts reservation.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-minimum"><?php _e('Minimum Entry', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-minimum" class="small-text" name="article[minimum]" value="<?php echo $article['minimum'] ?>" /> <?php _e('Persons', $this->domain) ?>
				<p class="article-description"><?php _e('The minimum acceptance number required for one reservation.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-maximum"><?php _e('Maximum Entry', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-maximum" class="small-text" name="article[maximum]" value="<?php echo $article['maximum'] ?>" /> <?php _e('Persons', $this->domain) ?>
				<p class="article-description"><?php _e('The number in which the maximum acceptance of one reservation is possible.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-count-adult"><?php _e('Count Rate', $this->domain) ?></label></th>
			<td>
				<?php echo apply_filters('mtssb_article_admin_adult', __('Adult: ', $this->domain)) ?> <input type="text" id="article-count-adult" class="small-text" name="article[count][adult]" value="<?php echo sprintf('%.1f', $article['count']['adult']) ?>" /><br />
				<?php echo apply_filters('mtssb_article_admin_child', __('Child: ', $this->domain)) ?> <input type="text" id="article-count-child" class="small-text" name="article[count][child]" value="<?php echo sprintf('%.1f', $article['count']['child']) ?>" /><br />
				<?php echo apply_filters('mtssb_article_admin_baby', __('Baby: ', $this->domain)) ?> <input type="text" id="article-count-baby" class="small-text" name="article[count][baby]" value="<?php echo sprintf('%.1f', $article['count']['baby']) ?>" />
				<p class="article-description"><?php _e('Calculation rates when calculating the number.', $this->domain) ?></p>
			</td>
		</tr>
	</table>

<?php
		ob_end_flush();
	}

	/**
	 * 予約品目の料金編集画面
	 *
	 */
	public function meta_box_charge($post)
	{
		$article = $this->_get_the_article($post->ID);

		ob_start();
?>
	<input type="hidden" name="<?php echo $this->nonce_name ?>" value="<?php echo wp_create_nonce($this->module_name) ?>" />

	<table class="form-table">
		<tr>
			<th><label for="article-restriction"><?php _e('Unit Price', $this->domain) ?></label></th>
			<td>
				<label><?php echo apply_filters('mtssb_article_admin_adult', __('Adult: ', $this->domain)) ?> <input type="text" id="price-adult" class="currency-box" name="article[price][adult]" value="<?php echo $article['price']->adult ?>" /></label><br />
				<label><?php echo apply_filters('mtssb_article_admin_child', __('Child: ', $this->domain)) ?> <input type="text" id="price-child" class="currency-box" name="article[price][child]" value="<?php echo $article['price']->child ?>" /></label><br />
				<label><?php echo apply_filters('mtssb_article_admin_baby', __('Baby: ', $this->domain)) ?> <input type="text" id="price-baby" class="currency-box" name="article[price][baby]" value="<?php echo $article['price']->baby ?>" /></label><br />
				<label><?php echo apply_filters('mtssb_article_admin_booking', __('Booking: ', $this->domain)) ?> <input type="text" id="price-base" class="currency-box" name="article[price][booking]" value="<?php echo $article['price']->booking ?>" /></label><br />
				<p class="article-description"><?php _e('Set unit price of each class or be able to charge only this booking.', $this->domain) ?></p>
			</td>
		</tr>
	</table>

<?php
		ob_end_flush();
	}

	/**
	 * 予約処理の各種設定
	 *
	 */
	public function meta_box_miscellaneous($post)
	{
		$article = $this->_get_the_article($post->ID);

		$option_catalog = get_option($this->domain . MTS_Simple_booking::CATALOG_NAME);

		$limit = $this->_get_cancel_margin();

		$booking_mail = $article['addition']->booking_mail;
		$bookingmail = empty($booking_mail) ? '' : $booking_mail[0];

        // 予約確認メールテンプレートオブジェクトリストを取得する
        $templates = MTSSB_Mail_Template::list_all();

		$awaking = $this->_get_awaking_margin();

		ob_start();
?>
	<input type="hidden" name="<?php echo $this->nonce_name ?>" value="<?php echo wp_create_nonce($this->module_name) ?>" />

	<table class="form-table">
		<tr>
			<th scope="row"><label for="article-addition-option"><?php _e('Option Settings', $this->domain) ?></label></th>
			<td>
				<input type="hidden" name="article[addition][option]" value="0" /><input type="checkbox" id="article-addition-option" name="article[addition][option]" value="1" <?php echo $article['addition']->isOption() ? 'checked="checked" ' : '' ?>/>
				<p class="article-description"><?php _e('Selection of whether to use an additional option.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-addition-option_name"><?php _e('Option Name', $this->domain) ?></label></th>
			<td>
				<select id="article-addition-option_name" name="article[addition][option_name]" style="letter-spacing:1px">
					<option value=""><?php _e('Select option', $this->domain) ?></option>
					<?php if (is_array($option_catalog)) {
					   foreach ($option_catalog as $catalog_name => $catalog_title) : ?>
					       <option value="<?php echo $catalog_name ?>"<?php echo $catalog_name == $article['addition']->option_name ? ' selected="selected"' : '' ?>><?php echo $catalog_title ?></option>
					   <?php endforeach;
				    } ?>
				</select>
				<p class="article-description"><?php _e('Select a option group for this article.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-addition-cancel_limit"><?php _e('Cancel Limit Date', $this->domain) ?></label></th>
			<td>
				<select id="article-addition-cancel_limit" name="article[addition][cancel_limit]" style="letter-spacing:1px"><?php foreach ($limit as $min => $label) : ?>
					<option value="<?php echo $min ?>"<?php echo $min == $article['addition']->cancel_limit ? ' selected="selected"' : '' ?>><?php echo $label ?></option>
				<?php endforeach; ?></select> <?php _e('before', $this->domain) ?><br />
				<p class="article-description"><?php _e('Limited time to be able to accept canceling except unavailable.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-addition-position"><?php _e('Position Of The Option', $this->domain) ?></label></th>
			<td>
				<select id="article-addition-position" name="article[addition][position]" style="letter-spacing:1px">
					<option value="0"<?php echo $article['addition']->position == 0 ? ' selected="selected"' : '' ?>><?php _e('Before', $this->domain) ?></option>
					<option value="1"<?php echo $article['addition']->position == 1 ? ' selected="selected"' : '' ?>><?php _e('After', $this->domain) ?></option>
				</select>
				<p class="article-description"><?php _e("Select the position of the option's field on a booking form page.", $this->domain) ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="article-addition-bookingmail"><?php _e('Auto Booking Mail Address', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-addition-bookingmail" class="mts-fat" name="article[addition][booking_mail][0]" value="<?php echo esc_html($bookingmail) ?>" />
				<p class="article-description"><?php _e("Set auto booking mail address when sending to the different address from the settings.", $this->domain) ?></p>
			</td>
		</tr>
        <tr>
            <th scope="row"><label for="article-addition-template"><?php _e('Confirmation Mail', $this->domain) ?></label></th>
            <td>
                <select id="article-addition-template" name="article[addition][template]" style="letter-spacing:1px">
                    <option value=""><?php _e('Not send', $this->domain) ?></option>
                    <?php if ($templates) : foreach ($templates as $template) : ?>
                        <option value="<?php echo $template->template_number() ?>"<?php echo $article['addition']->template == $template->template_number() ? ' selected="selected"' : '' ?>><?php echo $template->mail_subject ?></option>
                <?php endforeach; endif; ?></select><br />
                <p class="article-description"><?php _e('Select a template for a sending confirmation mail.', $this->domain) ?></p>
            </td>
        </tr>
		<tr>
			<th scope="row"><label for="article-addition-awaking_time"><?php _e('Awaking Mail', $this->domain) ?></label></th>
			<td>
				<select id="article-addition-awaking_time" name="article[addition][awaking_time]" style="letter-spacing:1px"><?php foreach ($awaking as $min => $label) : ?>
					<option value="<?php echo $min ?>"<?php echo $min == $article['addition']->awaking_time ? ' selected="selected"' : '' ?>><?php echo $label ?></option>
					<?php endforeach; ?></select> <?php _e('before', $this->domain) ?>
				<span id="awaking-time"><?php
					echo __('Sending Time', $this->domain) . '&nbsp;';
					echo $this->oCForm->selectHour('article-addition-awaking_hour', 'select-time', 'article[addition][awaking_hour]', $article['addition']->awaking_hour);
					_e('Hour', $this->domain);
					echo $this->oCForm->selectMinute('article-addition=awaking_minute', 'select-time', 'article[addition][awaking_minute]', $article['addition']->awaking_minute);
					_e('Minute', $this->domain);
				?></span><br>
				<select id="article-addition-awaking_mail" name="article[addition][awaking_mail]" style="letter-spacing:1px">
					<?php if ($templates) : foreach ($templates as $template) : ?>
						<option value="<?php echo $template->template_number() ?>"<?php echo $article['addition']->awaking_mail == $template->template_number() ? ' selected="selected"' : '' ?>><?php echo $template->mail_subject ?></option>
					<?php endforeach; endif; ?></select><br>
				<p class="article-description"><?php _e('Select sending time and a template to send E-Mail.', $this->domain) ?></p>
			</td>
		</tr>
		<tr>
            <th scope="row"><label for="article-addition-check_name"><?php _e('Multiple Booking Check', $this->domain) ?></label></th>
            <td>
                <label for="article-addition-check_name">
                    <input type="hidden" name="article[addition][check_name]" value="0">
                    <input id="article-addition-check_name" type="checkbox" name="article[addition][check_name]" value="1"<?php echo $this->_checkoutChecked($article['addition']->check_name, 1) ?>>
                    <?php _e('Check Name', $this->domain); ?>
                </label>
                <label for="article-addition-check_email">
                    <input type="hidden" name="article[addition][check_email]" value="0">
                    <input id="article-addition-check_email" type="checkbox" name="article[addition][check_email]" value="1"<?php echo $this->_checkoutChecked($article['addition']->check_email, 1) ?>>
                    <?php _e('Check Email', $this->domain); ?>
                </label>
                <label for="article-addition-check_tel">
                    <input type="hidden" name="article[addition][check_tel]" value="0">
                    <input id="article-addition-check_tel" type="checkbox" name="article[addition][check_tel]" value="1"<?php echo $this->_checkoutChecked($article['addition']->check_tel, 1) ?>>
                    <?php _e('Check Phone Number', $this->domain); ?>
                </label>
                <p class="article-description"><?php _e('Check multiple bookings on the same date and time using checked input item.', $this->domain) ?></p>
            </td>
        </tr>
		<tr>
			<th scope="row"><label for="article-addition-tracking"><?php _e('Tracking Code', $this->domain) ?></label></th>
			<td>
				<input type="text" id="article-addition-tracking" class="mts-fat" name="article[addition][tracking]" value="<?php echo esc_html($article['addition']->tracking) ?>" />
				<p class="article-description"><?php _e("Set tracking code when using affiliate. It's able to use %RESERVE_ID%.", $this->domain) ?></p>
			</td>
		</tr>
	</table>

<?php
		ob_end_flush();
	}

    private function _checkoutChecked($val1, $val2)
    {
        return ($val1 == $val2) ? ' checked="checked"' : '';
    }

	/**
	 * 時間選択のoptionタグ列出力
	 *
	 * $the_time	unix time
	 */
	private function _out_option_hour($the_time='') {
		$the_hour = $the_time == '' ? '10' : date('H', intval($the_time));

		$out = '';
		for ($hour = 0; $hour <= 23; $hour++) {
			$out .= "<option value=\"" . sprintf("%02d", $hour) . "\""
				. ($hour==$the_hour ? " selected=\"selected\"" : '') . ">"
				. sprintf("%02d", $hour) . "</option>\n";
		}

		return $out;
	}

	/**
	 * 分選択のoptionタグ列出力
	 *
	 * $the_time	unix time
	 */
	private function _out_option_minute($the_time=0, $min_step=10) {
		$the_minute = date('i', intval($the_time));

		$out = '';
		for ($minute = 0; $minute <= 59; $minute += $min_step) {
			$out .= "<option value=\"" . sprintf("%02d", $minute) . "\""
				. ($minute==$the_minute ? " selected=\"selected\"" : '') . ">"
				. sprintf("%02d", $minute) . "</option>\n";
		}

		return $out;
	}

	/**
	 * Save custom fields
	 *
	 */
	public function save_custom_fields($post_id) {

		// Check capability and post type
		if (!current_user_can('edit_page', $post_id)) {
			return;
		} else if (!isset($_POST['post_type']) || self::POST_TYPE != $_POST['post_type']) {
			return;
		}

		// Check nonce
		if (!isset($_POST[$this->nonce_name]) || !wp_verify_nonce($_POST[$this->nonce_name], $this->module_name)) {
			return;
		}

		// Check auto save
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// 予約品目フィールド名として利用
		$fields = self::get_new_article();

		// Save Plan data
		$article = isset($_POST['article']) ? $_POST['article'] : array();

		foreach ($fields as $column => $val) {
			$old = get_post_meta($post_id, $column, true);
			$new = $val;

			switch ($column) {
				case 'timetable':
					if (is_array($article[$column])) {
						$new = array();
						foreach ($article[$column] as $key => $time) {
							$new[] = intval($time);
						}
					} else {
						$new = '';
					}
					break;
				case 'restriction':
					$new = $article[$column] == 'capacity' ? $article[$column] : 'quantity';
					break;
				case 'capacity':
				case 'quantity':
				case 'minimum':
				case 'maximum':
					$new = intval($article[$column]);
					break;
				case 'count':
					$new = array(
						'adult' => (preg_match('/^[0-9](.[0-9]|)$/', $article[$column]['adult']) ? $article[$column]['adult'] : '0.0'),
						'child' => (preg_match('/^[0-9](.[0-9]|)$/', $article[$column]['child']) ? $article[$column]['child'] : '0.0'),
						'baby' => (preg_match('/^[0-9](.[0-9]|)$/', $article[$column]['baby']) ? $article[$column]['baby'] : '0.0'),
					);
					break;
				case 'price':
					$new = $this->_price2obj($article[$column]);
					break;
				case 'addition':
					$new = $this->_addition2obj($article[$column]);
					break;
				default:
					break;
			}

			if ($old != $new || $new == '') {
				update_post_meta($post_id, $column, $new);
			}
		}
	}

	/**
	 * 金額入力データをオブジェクトに変換する
	 *
	 */
	private function _price2obj($price)
	{
		// 料金設定
		$charge = get_option($this->domain . '_charge');

		$oPrice = new MTS_Value;

		foreach ($price as $key => $val) {
			if (empty($charge) || $charge['currency_code'] == 'JPY') {
				$oPrice->$key = intval($val);
			} else {
				$oPrice->$key = floatval($val);
			}
		}

		return $oPrice;
	}

	/**
	 * 追加情報(オプション機能等)をオブジェクトに変換する
	 *
	 */
	private function _addition2obj($addition)
	{
		$oAddition = new MTSSB_Article_Addition;

		foreach ($addition as $key => $val) {
			$oAddition->$key = $val;
		}

		return $oAddition;
	}

	/**
	 * Column title for list view
	 *
	 */
	public function get_column_titles() {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Article Name', $this->domain),
			'timetable' => __('Timetable', $this->domain),
			'restriction' => __('Restriction', $this->domain),
			'capacity' => __('Fixed Number', $this->domain),
			'quantity' => __('Booking Limit', $this->domain),
			'minimum' => __('Minimum Entry', $this->domain),
			'maximum' => __('Maximum Entry', $this->domain),
			'addition' => __('Option Settings', $this->domain),
		);
	}

	/**
	 * Output the custom column value
	 *
	 */
	public function out_custom_column($column) {
		global $post;

		if ($post->post_type != self::POST_TYPE) {
			return false;
		} else {
			$val = get_post_meta($post->ID, $column, true);
			if ($column == 'timetable' && is_array($val)) {
				foreach ($val as $key => &$time) {
					$time = date('H:i', $time);
				}
				$val = implode(',', $val);
			} else if ($column == 'restriction') {
				$val = __(ucwords($val), $this->domain);
			} else if ($column == 'addition') {
				if (is_object($val)) {
					$val = $val->option;
				}
			}
			echo $val;
		}
	}

	/**
	 * 時間割AJAX取得
	 *
	 */
	public function ajax_get_the_timetable() {

		// Check nonce
		//check_ajax_referer($this->domain . '_ajax', 'nonce');

		$article_id = intval($_POST['article_id']);

		$timetable = self::get_the_timetable($article_id);

		if (!empty($timetable)) {
			$options = '';
			foreach ($timetable as $time) {
				$options .= "<option value=\"$time\">" . date('H:i', $time) . '</option>';
			}
		} else {
			$options = '<option value="">' . __('Nothing', $this->domain) . '</option>';
		}

        return $options;
/*
		echo $options;

		exit();
*/
	}

	/**
	 * キャンセル受付マージンの取得
	 *
	 */
	private function _get_cancel_margin()
	{
		return apply_filters('mtssb_cancel_limit_margin', array(
			'0' => __('Unavilable', $this->domain),
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
	 * 事前メール送信日時選択肢取得
	 *
	 */
	private function _get_awaking_margin()
	{
		return apply_filters('mtssb_awaking_time_margin', array(
			'0' => __('Unavilable', $this->domain),
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

}