<?php
/**
 * MTS Simple Booking PayPal決済処理モジュール
 *
 * @Filename	mtssb-pp-manager.php
 * @Date		2012-12-27
 * @Author		S.Hayashi
 *
 * Updated to 1.26.0 on 2017-04-24
 * Updated to 1.23.1 on 2016-02-09
 * Updated to 1.23.0 on 2015-11-24
 * Updated to 1.15.3 on 2014-07-14
 * Updated to 1.14.0 on 2014-01-16
 * Updated to 1.12.0 on 2013-11-18
 */

use PayPal\CoreComponentTypes\BasicAmountType;
/*
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
*/
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
/*
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
*/
class MTSSB_PPManager {
	const SESSION_TABLE = 'mtssb_session';
	const TABLE_VERSION = '1.0';

	// MTSSB_Booking_Formのページと同じ
	const FORM_PAGE = 'booking-form';

	const BEFORE_DAYS = 20;

	/**
	 * Module Availability
	 */
	protected $available = false;

	// Domain
	protected $domain;			// mts_simple_booking

	// Table names
	protected $tblSession;

	// SDK include and logger
//	protected $ppl_loaded = false;
	protected $ppl_logger = null;

	// PayPal credentials and settings
	private $pp_username;
	private $pp_password;
	private $pp_signature;
	private $return_url;
	private $logo_url;
    private $use_sandbox;

	// PayPalリターンデータ
	private $token;
	private $payerId;
	private $sess_name;		// SID
	private $sess_data;		// array('booking', 'response')

	// 処理ページURL
	private $page_url;

	// 種別
	public $type = array('adult', 'child', 'baby');

	/**
	 * IV
	 */
	//private $iv = 'abcdefghijklmnopqrstuvwxyz012345';

	/**
	 * Key
	 */
	//private $key = "MTS_Simple_Booking\0\0\0\0\0\0";

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{

		// ドメイン名セット
		$this->domain = MTS_Simple_Booking::DOMAIN;

		// セッションテーブルのインストール
		$this->_install_table();

        // PayPal SDK のオートロード設定
        require 'pp/PPAutoloader.php';
        PPAutoloader::register();
/*
		if (extension_loaded('mcrypt')) {
			// wp-configからIV及びKEYの値を借りる
			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
			if (defined('AUTH_SALT')) {
				$this->iv = substr(AUTH_SALT . $this->iv, 0, $iv_size);
			}
			if (defined('AUTH_KEY')) {
				$this->key = substr(AUTH_KEY . $this->key, 0, 32);
			}

			// モジュール利用可
			$this->available = true;
		}
*/
		// PayPal APIアクセスのための認証データを読み出す
		$paypal = get_option($this->domain . '_paypal', true);
		$this->logo_url = $paypal['logo_url'];
        $this->use_sandbox = $paypal['use_sandbox'];

        // 暗号化(または未暗号化)したPayPalAPI設定値をセットする
		if ($this->available) {
			$this->pp_username = $this->mts_decode($paypal['pp_username']);
			$this->pp_password = $this->mts_decode($paypal['pp_password']);
			$this->pp_signature = $this->mts_decode($paypal['pp_signature']);
		} else {
			$this->pp_username = $paypal['pp_username'];
			$this->pp_password = $paypal['pp_password'];
			$this->pp_signature = $paypal['pp_signature'];
		}

		// https:// PayPalからのリダイレクトURL
		$this->page_url = MTS_Simple_Booking::get_permalink_by_slug(self::FORM_PAGE);
		if (empty($paypal['https_url'])) {
			$this->return_url = $this->page_url;
		} else {
			// 各種設定で「https://」が記述されていない場合は追加する
			$https_url = (preg_match('/^https:\/\//', $paypal['https_url']) ? '' : 'https://') . $paypal['https_url'];
			// URLからトップURL(例：http://example.com)を消去する
			$query = preg_replace('/' . preg_quote(home_url(), '/') . '/', '', $this->page_url);
			// トップURLをhttps://にしたURLを設定する($queryは/で始まるので削除しておく
			$this->return_url = preg_replace('/\/$/', '', $https_url) . $query;
		}
	}

	/**
	 * PayPalからのリダイレクトを確認する
	 *
	 */
	public function checkPaypalReturn()
	{
		// セッションデータ、NONCEデータの確認
		if (!isset($_GET['sid']) || !isset($_GET['nonce'])) {
			return false;
		}

		// セッションデータを取得
		$this->sess_name = urlencode($_GET['sid']);
		$this->sess_data = $this->_get_sessData(urlencode($_GET['sid']));
		if (empty($this->sess_data)) {
			return false;
		}

		// NONCEチェック
		if ($this->sess_data['nonce'] != urlencode($_GET['nonce'])) {
			return false;
		}

		return true;
	}

	/**
	 * セッションデータに保存されたデータを取得する
	 *
	 * @$keyname
	 */
	public function getSessionData($keyname)
	{
		if (isset($this->sess_data[$keyname])) {
			if ($keyname == 'setECResponse' || $keyname == 'doECResponse') {
                $response = unserialize($this->sess_data[$keyname]);
				return $response;
			}
			return $this->sess_data[$keyname];
		}
		return false;
	}

	/**
	 * SetExpressCheckout後のリダイレクト
	 *
	 */
	public function do_redirect($pp)
	{
		// リダイレクト準備
		$params = array(
			'pp' => $pp,
			'sid' => $this->sess_name,
			'nonce' => $this->sess_data['nonce'],
		);
		$returnUrl = add_query_arg($params, $_GET['page_url']);

		header("Location: {$returnUrl}");
		exit(); 
	}

	/**
	 * DoExpressCheckout実行
	 *
	 */
	public function doExpressCheckout() {
		global $mts_simple_booking;

		// トークンと買い手IDをセット
		$this->token = urlencode($_GET['token']);
		$this->payerId = urlencode($_GET['PayerID']);
		$this->_add_sessData($this->sess_name, array('token' => $this->token, 'payerId' => $this->payerId));

		// bookingデータをセットする
		$mts_simple_booking->oBooking_form->setBooking($this->sess_data['booking']);

		// 計算書を取得する
		$bill = $mts_simple_booking->oBooking_form->make_bill();

		$currencyCode = $bill->currency_code;

		// PayPalアクセス準備
		//$this->_init_paypal_sdk('DoExpressCheckout');

		// 請求合計額を計算する
		$itemTotal = $bill->get_total();
		$taxTotal = $bill->tax_type == 2 ? $bill->get_amount_tax() : 0;
		//$orderTotal = new BasicAmountType($currencyCode, $itemTotal + $taxTotal);
		$orderTotal = new BasicAmountType();
		$orderTotal->currencyID = $currencyCode;
		$orderTotal->value = $itemTotal + $taxTotal;

		// 支払明細設定
		$paymentDetails = new PaymentDetailsType();
		$paymentDetails->OrderTotal = $orderTotal;


		// API設定
		$DoECRequestDetails = new DoExpressCheckoutPaymentRequestDetailsType();
		$DoECRequestDetails->PayerID = $this->payerId;
		$DoECRequestDetails->Token = $this->token;
		$DoECRequestDetails->PaymentAction = 'Sale';
		$DoECRequestDetails->PaymentDetails[0] = $paymentDetails;

		$DoECRequest = new DoExpressCheckoutPaymentRequestType();
		$DoECRequest->DoExpressCheckoutPaymentRequestDetails = $DoECRequestDetails;

		$DoECReq = new DoExpressCheckoutPaymentReq();
		$DoECReq->DoExpressCheckoutPaymentRequest = $DoECRequest;

		$paypalService = new PayPalAPIInterfaceServiceService();
		try {
			/* wrap API method calls on the service object with a try catch */
			$doECResponse = $paypalService->DoExpressCheckoutPayment($DoECReq);
		} catch (Exception $ex) {
			// セッションデータに例外を追加保存する
			$this->_add_sessData($this->sess_name, array('doECException' => $ex));
			throw $ex;
		}

		// セッションデータにDoExpressCheckoutの実行結果を追加する
		$this->_add_sessData($this->sess_name, array('doECResponse' => serialize($doECResponse)));
		if ($doECResponse->Ack == 'Success') {
			return true;
		}

		return false;
	}

	/**
	 * SetExpressCheckout実行
	 *
	 */
	public function setExpressCheckout()
	{
		global $mts_simple_booking;

		// 予約データと計算書データを取得する
		$booking = $mts_simple_booking->oBooking_form->getBooking();
		$bill = $mts_simple_booking->oBooking_form->make_bill();

		// Nonceデータを含める
		$this->sess_data =  array(
            'page_url' => $this->page_url,
			'nonce' => $this->_get_nonce(),
			'booking' => $booking,
		);

		// 予約データをセッションテーブルに保存
		$this->sess_name = $this->_save_sessData($this->sess_data);

		// リターンURLを求める
		$params = array(
			'sid' => $this->sess_name,
			'nonce' => $this->sess_data['nonce'],
			'page_url' => $this->page_url,
		);
		$returnUrl = add_query_arg(array('pp' => 'checkout') + $params, $this->return_url);
		$cancelUrl = add_query_arg(array('pp' => 'cancel') + $params, $this->return_url);

		$currencyCode = $bill->currency_code;

		// Checkout 明細オブジェクト
		$paymentDetails = new PaymentDetailsType();

        // 予約品目の基本料金明細設定
        if (0 < $bill->basic_charge) {
            $itemDetails = new PaymentDetailsItemType();
            $itemDetails->Name = $bill->article_name . ' ' . apply_filters('booking_form_bill_basic_label', '予約料金');
            $itemDetails->Amount = new BasicAmountType($currencyCode, $bill->basic_charge);
            $itemDetails->Quantity = 1;
            $itemDetails->ItemCategory = 'Physical';
            $paymentDetails->PaymentDetailsItem[] = $itemDetails;
        }

		// 予約品目の料金明細設定
		foreach ($this->type as $type) { //($i=0; $i<count($_REQUEST['itemAmount']); $i++) {
			// 人数または料金が0の場合は明細に掲載しない
			if ($bill->number->$type == 0 || $bill->amount->$type == 0) {
				continue;
			}

			// 明細データ
			$itemDetails = new PaymentDetailsItemType();
			$itemDetails->Name = $bill->article_name . ' ' . apply_filters('booking_form_bill_label', __(ucwords($type), $this->domain));
			$itemDetails->Amount = new BasicAmountType($currencyCode, $bill->amount->$type);
			$itemDetails->Quantity = $bill->number->$type;
			$itemDetails->ItemCategory = 'Physical';
			
			$paymentDetails->PaymentDetailsItem[] = $itemDetails;	
		}

		// オプションの料金明細設定
		$option_items = $bill->option_items;
		if (!empty($option_items)) {
			foreach ($option_items as $option_item) {
				$itemDetails = new PaymentDetailsItemType();
				$itemDetails->Name = $option_item['name'];
				$itemDetails->Amount = $option_item['price'];
				$itemDetails->Quantity = $option_item['number'];
				$itemDetails->ItemCategory = 'Physical';

				$paymentDetails->PaymentDetailsItem[] = $itemDetails;
			}
		}

		//$paymentDetails->ShipToAddress = $address;
		$itemTotal = $bill->get_total();
		$taxTotal = $bill->tax_type == 2 ? $bill->get_amount_tax() : 0;
		$paymentDetails->ItemTotal = new BasicAmountType($currencyCode, $itemTotal);  
		$paymentDetails->TaxTotal = new BasicAmountType($currencyCode, $taxTotal);
		$paymentDetails->OrderTotal = new BasicAmountType($currencyCode, $itemTotal + $taxTotal);

		$paymentDetails->PaymentAction = 'Sale';
		//$paymentDetails->HandlingTotal = new BasicAmountType($currencyCode, $bill->total_cost);
		//$paymentDetails->insuranceTotal = new BasicAmountType($currencyCode, $_REQUEST['insuranceTotal']);
		//$paymentDetails->ShippingTotal = new BasicAmountType($currencyCode, $itemTotal);

		// APIデータ設定
		$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
		$setECReqDetails->PaymentDetails[0] = $paymentDetails;
		$setECReqDetails->CancelURL = $cancelUrl;
		$setECReqDetails->ReturnURL = $returnUrl;
		$setECReqDetails->cppheaderimage = $this->logo_url;
		$setECReqDetails->BrandName = $bill->shop_name;
		$setECReqDetails->LocaleCode = 'ja_JP';
		$setECReqDetails->SolutionType = 'Sole';
		$setECReqDetails->LandingPage = 'Billing';
		$setECReqDetails->BuyerEmail = $booking['client']['email'];

		$setECReqType = new SetExpressCheckoutRequestType();
		$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
		$setECReq = new SetExpressCheckoutReq();
		$setECReq->SetExpressCheckoutRequest = $setECReqType;
		
		// セッションデータにリクエストを追加する
		$this->_add_sessData($this->sess_name, array('setECReq' => serialize($setECReq)));

		$paypalService = new PayPalAPIInterfaceServiceService();
		try {
			/* wrap API method calls on the service object with a try catch */
			$setECResponse = $paypalService->SetExpressCheckout($setECReq);
		} catch (Exception $ex) {
			throw $ex;
		}

		// API SetExpressCheckoutの結果を保存する
		$token = '';
		if (isset($setECResponse)) {
			// セッションデータに接続結果を追加する
			$this->_add_sessData($this->sess_name, array('setECResponse' => serialize($setECResponse)));

			// PayPal支払処理へリダイレクトする
			if ($setECResponse->Ack == 'Success') {
				// PayPalへのリダイレクト
                $sandbox = $this->use_sandbox ? '.sandbox' : '';
                $payPalURL = sprintf('https://www%s.paypal.com/webscr?cmd=_express-checkout&token=%s', $sandbox, $setECResponse->Token);
                header("Location: {$payPalURL}");
                exit();
			}
		}

		return $setECResponse;
	}

	/**
	 * 接続エラーメッセージの生成
	 *
	 */
	public function exception_error($ex) {
		$ex_message = '';
		$ex_detailed_message = '';
		$ex_type = 'Unknown';

		if (!empty($ex)) {
			$ex_message = $ex->getMessage();
			$ex_type = get_class($ex);
			if ($ex instanceof PPConnectionException) {
				$ex_detailed_message = "Error connecting to " . $ex->getUrl();
			} else if ($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
				$ex_detailed_message = $ex->errorMessage();
			} else if ($ex instanceof PPConfigurationException) {
				$ex_detailed_message = "Invalid configuration. Please check your configuration file";
			}
		}

		ob_start();
?>
	<div id="error_title">
		<b>SDK Exception</b><br /> <br />
	</div>
	<table style="margin: auto;">
		<tr>
			<td><b>Type</b></td><td><?php echo $ex_type ?></td>
		</tr>
		<tr>
			<td><b>Message</b></td><td><?php echo $ex_message ?></td>
		</tr>
		<tr>
			<td><b>Detail</b></td><td><?php echo $ex_detailed_message ?></td>
		</tr>
	</table>

<?php
		return ob_get_clean();
	}

	/**
	 * 処理エラーメッセージの生成
	 *
	 * @er		setECResponce
	 */
	public function transaction_error($er) {

		$short_message = "Response was nothing";
		$long_message = $error_code = '';

		if (!empty($er)) {
			$short_message = $er->Errors[0]->ShortMessage;
			$long_message = $er->Errors[0]->LongMessage;
			$error_code = $er->Errors[0]->ErrorCode;
		}

		ob_start();
?>
	<div id="error_title">
		<b>Transaction Error</b><br /> <br />
	</div>
	<table style="margin: auto;">
		<tr>
			<td><b>Short Message</b></td><td><?php echo $short_message ?></td>
		</tr>
		<tr>
			<td><b>Long Message</b></td><td><?php echo $long_message ?></td>
		</tr>
		<tr>
			<td><b>Error Code</b></td><td><?php echo $error_code ?></td>
		</tr>
	</table>

<?php
		if (WP_DEBUG) {
			print_r($this->sess_data);
		}

		return ob_get_clean();
	}

	/**
	 * DoExpressCheckout正常終了時のtransaction_idを戻す
	 *
	 */
	public function get_transactionId()
	{
		$doECResponse = $this->getSessionData('doECResponse');

		if ($doECResponse) {
			if ($doECResponse->Ack == 'Success') {
				return $doECResponse->DoExpressCheckoutPaymentResponseDetails->PaymentInfo[0]->TransactionID;
			}
		}

		return '';
	}

    /**
     * SandBox利用設定値の取得
     *
     */
    public function getUseSandbox()
    {
        return $this->use_sandbox;
    }

    /**
	 * 指定されたSESSION名のデータを取得する
	 *
	 */
	protected function _get_sessData($sess_name)
	{
		global $wpdb;

		$data = $wpdb->get_results($wpdb->prepare("
			SELECT * FROM {$this->tblSession} WHERE session_name = %s",
			$sess_name), ARRAY_A);

		if (empty($data)) {
			return false;
		}

        return unserialize($data[0]['session_data']);
		//return stripslashes_deep(unserialize($data[0]['session_data']));
	}

	/**
	 * セッションデータを保存する
	 *
	 */
	protected function _save_sessData($sess_data)
	{
		global $wpdb;

		// ユニークなsession_nameを取得する
		$data = false;
		$sess_name = '';
		do {
			$sess_name = md5(mt_rand());
			$data = $this->_get_sessData($sess_name);
		} while (!empty($data)) ;

		// セッションデータを保存する
		$result = $wpdb->insert($this->tblSession, array(
			'session_name' => $sess_name,
			'session_data' => serialize($sess_data),
			'created' => current_time('mysql')),
			array('%s', '%s', '%s'));

		if (!$result) {
			return false;
		}

		return $sess_name;
	}

	/**
	 * セッションデータにSetExpressCheckoutレスポンスデータを追加する
	 *
	 * @sess_name
	 * @add_data	例：array('setECResponse' => $setECResponse)
	 */
	protected function _add_sessData($sess_name, $add_data)
	{
		global $wpdb;

		$sess_data = $this->_get_sessData($sess_name);
		$sess_data += $add_data;

		// 追加したセッションデータを保存する
		$result = $wpdb->update($this->tblSession,
			array('session_data' => serialize($sess_data)),
			array('session_name' => $sess_name),
			array('%s'),
			array('%s'));

		// 古いセッションデータを削除する
		if ($result) {
			$result = $this->_remove_sessData();
		}

		return $result;
	}

	/**
	 * 古くなったセッションデータを削除する
	 *
	 */
	protected function _remove_sessData()
	{
		global $wpdb;
	
		// 30日前の日付
		$before_date = date_i18n('Y-m-d',
		 mktime(0, 0, 0, date_i18n('n'), date_i18n('j') - self::BEFORE_DAYS, date_i18n('Y')));

		// 削除する
		$result = $wpdb->query($wpdb->prepare(
			"DELETE FROM " . $this->tblSession . " WHERE created < %s",
			$before_date));

		return $result;
	}

	/**
	 * Nonceデータを生成
	 *
	 */
	protected function _get_nonce() {
		return wp_create_nonce(get_class($this));
	}

	/**
	 * PayPal Credentials
	 *
	 */
	public function getCredentials()
	{
		return array(
			'pp_username' => $this->pp_username,
			'pp_password' => $this->pp_password,
			'pp_signature' => $this->pp_signature,
		);
	}

	/**
	 * mcrypt暗号化拡張モジュールがないと利用できない
	 *
	 */
/*
	public function isAvailable() {
		return $this->available;
	}
*/
	/**
	 * 暗号化してbase64エンコードした文字列で戻す
	 *
	 */
/*
	public function mts_encode($source) {
		$cryptdata = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $source, MCRYPT_MODE_CBC, $this->iv);
		return base64_encode($cryptdata);
	}
*/
	/**
	 * 復号した文字列を戻す
	 *
	 */
/*
	public function mts_decode($data) {
		$decryptdata = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($data), MCRYPT_MODE_CBC, $this->iv));
		return $decryptdata;
	}
*/

	/**
	 * Database table installation
	 *
	 */
	private function _install_table() {
		global $wpdb;

		// テーブル名をセット
		$this->tblSession = $wpdb->prefix . self::SESSION_TABLE;

		$option_name = $this->domain . '_' . self::SESSION_TABLE;
		$version = get_option($option_name);

		if (empty($version) || $version != self::TABLE_VERSION) {
			require_once(ABSPATH . "wp-admin/includes/upgrade.php");

			// Booking table
			$sql = "CREATE TABLE " . $this->tblSession . " (
				session_id int(11) unsigned NOT NULL AUTO_INCREMENT,
				session_name varchar(64) NOT NULL DEFAULT '',
				session_data text,
				created datetime DEFAULT NULL,
				PRIMARY KEY  (session_id),
				UNIQUE KEY (session_name)) DEFAULT CHARSET=utf8;";
			dbDelta($sql);

			// Update table version
			update_option($option_name, self::TABLE_VERSION);
		}
	}
}
