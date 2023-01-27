<?php
/**
 * MTS Simple Booking 顧客管理管理モジュール
 *
 * @filename	mtssb-user-admin.php
 * @date		2012-05-25
 * @author		S.Hayashi
 *
 * Updated to 1.15.0 on 2014-04-18
 */
class MTSSB_User_Admin {
	const VERSION = '1.15.0';

	const USER_ROLE = MTS_Simple_Booking::USER_ROLE;

	/**
	 * Instance of this object module
	 */
	static private $iUser = null;

	/**
	 * Private valiable
	 */
	private $domain;

	/**
	 * インスタンス化
	 *
	 */
	static function get_instance() {
		if (!isset(self::$iUser)) {
			self::$iUser = new MTSSB_User_Admin();
		}

		return self::$iUser;
	}


	protected function __construct() {
		$this->domain = MTS_Simple_Booking::DOMAIN;
	}

	/**
	 * 管理画面プロフィール 連絡先情報置換
	 *
	 */
	public function extend_user_contactmethod($user_contactmethods) {

		return apply_filters('mtssb_extend_user_contactmethods', array(
			'mtscu_company' => __('Company', $this->domain),
			'mtscu_furigana' => __('Furigana', $this->domain),
			'mtscu_postcode' => __('Postcode', $this->domain),
			'mtscu_address1' => __('Address1', $this->domain),
			'mtscu_address2' => __('Address2', $this->domain),
			'mtscu_tel' => __('Tel', $this->domain),
		));
	}

}
