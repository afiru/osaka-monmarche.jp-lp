<?php
/**
 * MTS Simple Booking コンタクトページ処理モジュール
 *
 * @Filename	mtssb-contact.php
 * @Date		2012-05-19
 * @Author		S.Hayashi
 *
 * Updated to 1.15.1 on 2014-05-15
 */
class MTSSB_Contact {
	const VERSION = '1.15.1';

	const PAGE_NAME = MTS_Simple_Booking::PAGE_CONTACT;
//	const THANK_PAGE = 'thanks';

	private $domain;

	// お問い合わせデータ
	public $contact;
	public $template;
	private $columns;

	/**
	 * Error
	 */
	private $err_message = '';
	private $errmsg = array();


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->domain = MTS_Simple_Booking::DOMAIN;

		// お問い合わせデータのカラム情報を読込む
		$this->template = get_option($this->domain . '_contact');
		$this->columns = &$this->template['column'];
	}

	/**
	 * 確認フォームの入力確認
	 *
	 */
	public function check_before_send() {
		// NONCEチェック
		if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
			$this->err_message = $this->_err_message('NONCE_ERROR');
			return false;
		}

		// 入力チェック(再入力メールのチェックをしない)
		$check_mail2 = false;
		if (!$this->_input_validation($check_mail2)) {
			$this->err_message = $this->_err_message('ERROR_BEFORE_SENDING');
			return false;
		}

		return true;
	}

	/**
	 * メールの送信エラーメッセージをセット
	 *
	 */
	public function error_send_mail() {
		$this->err_message = $this->_err_message('ERROR_SEND_MAIL');
	}

	/**
	 * お問い合わせフォーム処理
	 *
	 */
	public function contact_form($content) {

		// 問い合わせメール送信の後処理
		$action = isset($_POST['action']) ? $_POST['action'] : '';
		if ($action == 'confirm') {
			if (empty($this->err_message)) {
				return $this->_out_completed();
			}
			return $this->_out_errorbox();
		}

		// SUBMIT処理
		if ($action == 'validate') {
			// NONCEチェック
			if (!wp_verify_nonce($_POST['nonce'], "{$this->domain}_" . self::PAGE_NAME)) {
				$this->err_message = $this->_err_message('NONCE_ERROR');
				return $this->_out_errorbox();
			}

			// 入力チェック
			if ($this->_input_validation()) {
				return $content . $this->_confirmation_form();
			}
		} else {
			$this->contact = $this->_init_form();
		}

		$content = $content . $this->_input_form();

		return $content;
	}

	/**
	 * お問い合わせ入力フォームの表示
	 *
	 */
	protected function _input_form() {

		$url = get_permalink();

		ob_start();
?>

<div id="contact-form" class="content-form">

<form method="post" action="<?php echo $url ?>">
	<fieldset>
	<legend><?php echo apply_filters('contact_form_client_title', '入力項目') ?></legend>
	<?php echo apply_filters('contact_form_client_note', '') ?><br />
	<table>
		<?php if (0 < $this->columns['company']) : ?><tr>
			<th><label for="client-company">会社名<?php echo $this->columns['company'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-company" class="content-text medium" type="text" name="contact[company]" value="<?php echo esc_html($this->contact['company']) ?>" maxlength="100" />
			<?php if (isset($this->errmsg['company'])) : ?><div class="error-message"><?php echo $this->errmsg['company'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['name']) : ?><tr>
			<th><label for="client-name">お名前<?php echo $this->columns['name'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-name" class="content-text medium" type="text" name="contact[name]" value="<?php echo esc_html($this->contact['name']) ?>" maxlength="100" />
			<?php if (isset($this->errmsg['name'])) : ?><div class="error-message"><?php echo $this->errmsg['name'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['furigana']) : ?><tr>
			<th><label for="client-furigana">フリガナ<?php echo $this->columns['furigana'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-furigana" class="content-text medium" type="text" name="contact[furigana]" value="<?php echo esc_html($this->contact['furigana']) ?>" maxlength="100" />
			<?php if (isset($this->errmsg['furigana'])) : ?><div class="error-message"><?php echo $this->errmsg['furigana'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['email']) : ?><tr>
			<th><label for="client-email">E-Mail<?php echo $this->columns['email'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-email" class="content-text fat" type="text" name="contact[email]" value="<?php echo esc_html($this->contact['email']) ?>" maxlength="100" />
			<?php if (isset($this->errmsg['email'])) : ?><div class="error-message"><?php echo $this->errmsg['email'] ?></div><?php endif; ?></td>
		</tr>
		<?php if ($this->columns['email'] == 1) : ?><tr>
			<th><label for="client-email2">E-Mail(確認用)</label></th>
			<td>
				<input id="client-email2" class="content-text fat" type="text" name="contact[email2]" value="" maxlength="100" />
			</td>
		</tr><?php endif; endif; ?>
		<?php if (0 < $this->columns['postcode']) : ?><tr>
			<th><label for="client-postcode">郵便番号<?php echo $this->columns['postcode'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-postcode" class="content-text medium" type="text" name="contact[postcode]" value="<?php echo esc_html($this->contact['postcode']) ?>" maxlength="10" />
			<?php if (isset($this->errmsg['postcode'])) : ?><div class="error-message"><?php echo $this->errmsg['postcode'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['address']) : ?><tr>
			<th><label for="client-address1">住所<?php echo $this->columns['address'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-address1" class="content-text fat" type="text" name="contact[address1]" value="<?php echo esc_html($this->contact['address1']) ?>" maxlength="100" /><br />
				<input id="client-address2" class="content-text fat" type="text" name="contact[address2]" value="<?php echo esc_html($this->contact['address2']) ?>" maxlength="100" />
			<?php if (isset($this->errmsg['address'])) : ?><div class="error-message"><?php echo $this->errmsg['address'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['tel']) : ?><tr>
			<th><label for="client-tel">電話番号<?php echo $this->columns['tel'] == 1 ? '(<span class="required">※</span>)' : '' ?></label></th>
			<td>
				<input id="client-tel" class="content-text medium" type="text" name="contact[tel]" value="<?php echo esc_html($this->contact['tel']) ?>" maxlength="20" />
			<?php if (isset($this->errmsg['tel'])) : ?><div class="error-message"><?php echo $this->errmsg['tel'] ?></div><?php endif; ?></td>
		</tr><?php endif; ?>

		<tr>
			<th><label for="booking-note">ご連絡事項(<span class="required">※</span>)</label></th>
			<td>
				<textarea id="booking-note" class="content-text fat" name="contact[message]" rows="8" cols="200"><?php echo esc_textarea($this->contact['message']) ?></textarea>
			<?php if (isset($this->errmsg['message'])) : ?><div class="error-message"><?php echo $this->errmsg['message'] ?></div><?php endif; ?></td>
		</tr>
	</table>
	</fieldset>

	<div id="action-button" style="text-align: center">
		<?php echo apply_filters('contact_form_send_button', '<button type="submit" name="reserve_action" value="validate">内容確認</button>'); ?>
	</div>
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
	<input type="hidden" name="action" value="validate" />
</form>
</div>

<?php
		return ob_get_clean();
	}

	/**
	 * 入力チェック
	 *
	 */
	protected function _input_validation($check_mail2=true) {

		$this->errmsg = array();

		// 入力データセット
		$contact = array_merge($this->_init_form(), $_POST['contact']);

		// 入力データの正規化
		$this->_normalize($contact);

		// 連絡先項目の確認
		foreach ($this->columns as $key => $val) {
            if ($val == 1) {
                if ($key == 'address') {
                    if (empty($this->contact['address1'])) {
                        $this->errmsg[$key] = $this->_err_message('REQUIRED');
                    }
                } elseif (empty($this->contact[$key])) {
                    $this->errmsg[$key] = $this->_err_message('REQUIRED');
                }
            }
		}

		if (empty($this->contact['message'])) {
			$this->errmsg['message'] = $this->_err_message('REQUIRED');
		}

		// E-Mailの確認
		if (0 < $this->columns['email'] && !empty($this->contact['email'])) {
			if (!preg_match("/^[0-9a-z_\.\-]+@[0-9a-z_\-\.]+$/i", $this->contact['email'])) {
				$this->errmsg['email'] = $this->_err_message('INVALID_EMAIL');
			} else if ($this->columns['email'] == 1 && $check_mail2
			 && $this->contact['email'] != $this->contact['email2']) {
				$this->errmsg['email'] = $this->_err_message('UNMATCH_EMAIL');
			}
		}

		// 郵便番号の確認
		if (0 < $this->columns['postcode']) {
			if (!preg_match("/^[0-9\-]*$/", $this->contact['postcode'])) {
				$this->errmsg['postcode'] = $this->_err_message('NOT_NUMERIC');
			}
		}

		// 電話番号の確認
		if (0 < $this->columns['tel']) {
			if (!preg_match("/^[0-9_\-\(\)]*$/", $this->contact['tel'])) {
				$this->errmsg['tel'] = $this->_err_message('NOT_NUMERIC');
			}
		}

		if (!empty($this->errmsg)) {
			return false;
		}

		return true;
	}

	/**
	 * お問い合わせ確認フォーム生成
	 *
	 */
	private function _confirmation_form() {

		$url = get_permalink();

		ob_start();
?>

<div id="contact-form" class="content-form">

<form method="post" action="<?php echo $url ?>">
	<fieldset>
	<legend><?php echo apply_filters('contact_form_confirm_title', '入力の確認') ?></legend>
	<table>
		<?php if (0 < $this->columns['company']) : ?><tr>
			<th>会社名</th>
			<td>
				<?php echo esc_html($this->contact['company']) ?>
				<input type="hidden" name="contact[company]" value="<?php echo esc_html($this->contact['company']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['name']) : ?><tr>
			<th>お名前</th>
			<td>
				<?php echo esc_html($this->contact['name']) ?>
				<input type="hidden" name="contact[name]" value="<?php echo esc_html($this->contact['name']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['furigana']) : ?><tr>
			<th>フリガナ</th>
			<td>
				<?php echo esc_html($this->contact['furigana']) ?>
				<input type="hidden" name="contact[furigana]" value="<?php echo esc_html($this->contact['furigana']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['email']) : ?><tr>
			<th>E-Mail</th>
			<td>
				<?php echo esc_html($this->contact['email']) ?>
				<input type="hidden" name="contact[email]" value="<?php echo esc_html($this->contact['email']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['postcode']) : ?><tr>
			<th>郵便番号</th>
			<td>
				<?php echo esc_html($this->contact['postcode']) ?>
				<input type="hidden" name="contact[postcode]" value="<?php echo esc_html($this->contact['postcode']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['address']) : ?><tr>
			<th>住所</th>
			<td>
				<?php echo esc_html($this->contact['address1']) . '<br />' . esc_html($this->contact['address2']) ?>
				<input type="hidden" name="contact[address1]" value="<?php echo esc_html($this->contact['address1']) ?>" />
				<input type="hidden" name="contact[address2]" value="<?php echo esc_html($this->contact['address2']) ?>" />
		</tr><?php endif; ?>
		<?php if (0 < $this->columns['tel']) : ?><tr>
			<th>電話番号</th>
			<td>
				<?php echo esc_html($this->contact['tel']) ?>
				<input type="hidden" name="contact[tel]" value="<?php echo esc_html($this->contact['tel']) ?>" />
		</tr><?php endif; ?>

		<tr>
			<th>ご連絡事項</th>
			<td>
				<?php echo nl2br(esc_html($this->contact['message'])) ?>
				<input type="hidden" name="contact[message]" value="<?php echo esc_textarea($this->contact['message']) ?>" />
			</td>
		</tr>
	</table>
	</fieldset>

	<div id="action-button" style="text-align: center">
		<?php echo apply_filters('contact_form_submit_button', '<button type="submit" name="contact_action" value="validate">送信する</button>'); ?>
	</div>
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("{$this->domain}_" . self::PAGE_NAME) ?>" />
	<input type="hidden" name="action" value="confirm" />
</form>
</div>

<?php

		return ob_get_clean();
	}

	/**
	 * エラーエレメントの出力
	 *
	 */
	protected function _out_errorbox() {
		ob_start();
?>
		<div class="error-message error-box">
			<?php echo $this->err_message ?>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * 送信完了エレメントの出力
	 *
	 */
	protected function _out_completed() {
		ob_start();
?>
		<div class="info-message send-completed">
			<?php echo apply_filters('contact_form_thanks', '送信を完了しました。ありがとうございました。') ?>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * 入力データの正規化
	 *
	 */
	private function _normalize($contact) {
		if (get_magic_quotes_gpc()) {
			$contact = stripslashes_deep($contact);
		}

		$this->contact['company'] = trim(mb_convert_kana($contact['company'], 'as'));
		$this->contact['name'] = trim(mb_convert_kana($contact['name'], 's'));
		$this->contact['furigana'] = trim(mb_convert_kana($contact['furigana'], 'asKCV'));
		$this->contact['email'] = trim(mb_convert_kana($contact['email'], 'as'));
		$this->contact['email2'] = isset($contact['email2']) ? trim($contact['email2']) : '';
		$this->contact['postcode'] = trim(mb_convert_kana($contact['postcode'], 'as'));
		$this->contact['address1'] = trim(mb_convert_kana($contact['address1'], 'as'));
		$this->contact['address2'] = trim(mb_convert_kana($contact['address2'], 'as'));
		$this->contact['tel'] = trim(mb_convert_kana($contact['tel'], 'as'));
		$this->contact['message'] = mb_substr(trim($contact['message']), 0, 1000);
	}

	/**
	 * エラーメッセージ
	 *
	 */
	protected function _err_message($err_name) {
		switch ($err_name) {
			case 'NONCE_ERROR':
				return 'Nonce Check Fault.';
			case 'REQUIRED':
				return 'この項目は省略できません。';
			case 'INVALID_EMAIL':
				return 'メールアドレスの指定が正しくありません。';
			case 'UNMATCH_EMAIL':
				return 'メールアドレスが確認用と一致しませんでした。';
			case 'NOT_NUMERIC':
				return '数字以外の文字が見つかりました。';

			case 'ERROR_BEFORE_SENDING':
				return '入力チェックエラーが送信前に見つかりました。';
			case 'ERROR_ADD_BOOKING':
				return '予約のデータ登録を失敗しました。';
			case 'ERROR_SEND_MAIL':
				return 'メールの送信を失敗しました。';

			default :
				return '入力エラーです。';
		}
	}

	/**
	 * メールフォームデータ初期化
	 *
	 */
	 private function _init_form() {
	 	return array(
		'company' => '',
		'name' => '',
		'furigana' => '',
		'email' => '',
		'postcode' => '',
		'address1' => '',
		'address2' => '',
		'tel' => '',
		'message' => '',
		);
	}

}
