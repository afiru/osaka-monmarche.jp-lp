<?php
/**
 * MTS Simple Booking Option module オプションオブジェクトモジュール
 *
 * @Filename	mtssb-options.php
 * @Date		2012-10-08
 * @Author		S.Hayashi
 *
 * Updated to 1.19.0 on 2014-10-30
 * Updated to 1.13.0 on 2014-01-03
 * Updated to 1.12.0 on 2013-12-12
 * Updated to 1.9.0 on 2013-07-11
 * Updated to 1.8.5 on 2013-07-02
 * Updated to 1.7.0 on 2013-05-12
 * Updated to 1.6.0 on 2013-03-18
 * Updated to 1.4.0 on 2013-01-29
 * Updated to 1.3.0 on 2013-01-06
 */
class MTSSB_Option
{
	protected $domain;

	/**
	 * オプション設定のデータ
	 *
	 */
	protected $option_name = '';
	private $options = array();

	/**
	 * Constructor
	 *
	 */
	public function __construct() {

		$this->domain = MTS_Simple_Booking::DOMAIN;
	}

	/**
	 * オプションオブジェクトを生成する
	 *
	 * @
	 */
	public function loadOption($name)
	{
		$this->options = array();
		$this->setOptionName($name);

		$data = get_option($this->option_name);

		if (!empty($data)) {
			// データフィールドをオブジェクト配列にする
			foreach ($data as $optionid => $item) {
				$item['optionid'] = $optionid;
				$this->options[] = $this->getInstance($item);
			}
		}

		return $this->options;
	}

	/**
	 * オプションデータオブジェクトを生成する
	 *
	 */
	public function optionSet() {

		$newset = array();

		foreach ($this->options as $option) {
			$newset[] = clone $option;
		}

		return $newset;
	}

	/**
	 * オプションデータオブジェクトからデータ保存用の配列を戻す
	 *
	 */
	public static function recordSet($option_array) {
		$optionArray = array();

		foreach ($option_array as $option) {
			$optionArray[$option->getKeyname()] = $option->getValue();
		}

		return $optionArray;
	}

	/**
	 * オプションデータに初期値をセットする
	 *
	 */
	public function newValues() {
		foreach ($this->options as $option) {
			$option->newValue();
		}
	}

	/**
	 * オプションタイプ別にオブジェクト生成
	 *
	 * @item	登録データ
	 */
	protected function getInstance($item = array())
	{
		// 定義済みタイプなら当該タイプオブジェクトのインスタンス、それ以外は数値入力
		if (MTSSB_OPT_Field::is_option_type($item['type'])) {
			$class = 'MTSSB_OPT_' . $item['type'];
			$option = new $class($item);
		} else {
			$option = new MTSSB_OPT_Number($item);
		}

		return $option;
	}

	/**
	 * オプションIDの重複を確認する
	 *
	 */
	public function checkDuplicated($optionid) {
		if ($this->get_option($optionid)) {
			return $optionid;
		}

		return false;
	}

	/**
	 * 新しいオプションを加えて並び変える
	 *
	 */
	public function addOption($newo)
	{
		// オプションIDが0でないか確認する
		if ($newo->optionid == 0) {
			throw new Exception('OPTIONID_UNAVAILABLE');
		}

		// オプションIDの重複を確認する
		if ($this->checkDuplicated($newo->optionid)) {
			throw new Exception('OPTIONID_DUPLICATED');
		}

		// 新しいオプションを最後尾に追加
		$this->options[] = $newo;

		// 並び変える
		$this->_sortOption();

		return 'ADDED';
	}

	/**
	 * oオプション配列の編集オプションを置き替えて並び変える
	 *
	 */
	public function replaceOption($edo)
	{
		// オプションIDが0でないか確認する
		if ($edo->optionid == 0) {
			throw new Exception('OPTIONID_UNAVAILABLE');
		}

		// OptionIDが同じオブジェクトを置き替える
		foreach ($this->options as $key => $option) {
			if ($edo->optionid == $option->optionid) {
				$this->options[$key] = $edo;
				return 'REPLACED';
			}
		}

		// 同じOptionIDがなければ新規で登録する
		$this->addOption($edo);
		return 'ADDED';
	}

	/**
	 * 指定のオプションをオプション配列から削除する
	 *
	 */
	public function removeOption($optionid) {
		// オプション配列から同じOptionIDを持つ配列インデックスを求める
		$index = -1;
		foreach ($this->options as $key => $option) {
			if ($option->getOptionid() == $optionid) {
				$index = $key;
				break;
			}
		}

		// オプション配列からオプションデータを削除する
		if (0 <= $index) {
			unset($this->options[$index]);
			// 並び変える
			$this->_sortOption();
		}

		return $index;
	}


	/**
	 * オプションを並び変える
	 *
	 */
	protected function _sortOption() {
		// optionid をキーとしたオブジェクトを示す配列を作る
		$optionids = array();
		foreach ($this->options as $key => $option) {
			$optionids[$option->getOptionid()] = $key;
		}

		// 配列のキーを昇順にソートする
		ksort($optionids);

		// オプションオブジェクトをソート順にする
		$new_options = array();
		foreach ($optionids as $key) {
			$new_options[] = $this->options[$key];
		}

		// ソートしたオブジェクトに入れ替える
		$this->options = $new_options;
	} 

	/**
	 * オプション設定の保存
	 *
	 */
	public function writeOptions() {
		// WPのオプションデータ保存形式にする
		$records = array();
		foreach ($this->options as $option) {
			$data = $option->getDataArray();
			$optionid = $data['optionid'];
			unset($data['optionid']);
			$records[$optionid] = $data;
		}

		// WPオプションデータとして保存する
		update_option($this->option_name, $records);
	}

	/**
	 * オプション定義の登録数を戻す
	 *
	 */
	public function count_option() {
		return count($this->options);
	}

	/**
	 * 指定されたoptionidのオプションを戻す
	 *
	 */
	public function get_option($optionid) {
		foreach ($this->options as $option) {
			if ($option->optionid == $optionid) {
				return $option;
			}
		}

		return null;
	}

	/**
	 * オプション並びの番号でオブジェクトを戻す
	 *
	 */
	public function get_option_no($no) {
		if (isset($this->options[$no])) {
			return $this->options[$no];
		}

		return null;
	}

	/**
	 * オプショングループ名をオプション名をセットする
	 *
	 */
	public function setOptionName($name)
	{
		$this->option_name = $this->domain . '_option' . (empty($name) ? '' : "_{$name}");

		return $this->option_name;
	}

	/**
	 * フィールドタイプ
	 *
	 */
	public function field_type_label()
	{
		return array(
			'number' => __('Number input', $this->domain),
			'text' => __('Text input', $this->domain),
			'radio' => __('Radio button', $this->domain),
			'select' => __('Select box', $this->domain),
			'check' => __('Check box', $this->domain),
			'date' => __('Date input', $this->domain),
			'textarea' => __('Textarea input', $this->domain),
			'time' => __('Time input', $this->domain),
		);
	}

	/**
	 * 料金対象者
	 *
	 */
	public function whose_cost_label()
	{
		return array(
			'',
			'all' => __('Everybody', $this->domain),
			'adult' => __('Adult', $this->domain),
			'child' => __('Child', $this->domain),
			'baby' => __('Baby', $this->domain),
			'booking' => __('Booking', $this->domain),
		);
	}
}


/**
 * 数量オプションクラス
 *
 */
class MTSSB_OPT_Number extends MTSSB_OPT_Field
{
	public function __construct($item=array()) {
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue(intval($item['val']));
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 */
	public function normalize($val)
	{
		$str = trim(mb_convert_kana($val, 'as'));
		if (is_numeric($str)) {
			return intval($str);
		}
	
		return '';
	}
}

/**
 * テキスト入力オプションクラス
 *
 */
class MTSSB_OPT_Text extends MTSSB_OPT_Field
{
	public function __construct($item=array())
	{
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 */
	public function normalize($val)
	{
		// 制御文字の削除、128文字以内
		$str = preg_replace('/[\x00-\x1f\x7f]/', '', mb_substr(trim(mb_convert_kana($val, 's')), 0, 128));
		return apply_filters('mtssb_options_normalize_text', $str, $val);
	}
}

/**
 * テキストエリア入力オプションクラス
 *
 */
class MTSSB_OPT_Textarea extends MTSSB_OPT_Field
{

	public function __construct($item=array())
	{
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 */
	public function normalize($val)
	{
		// 空白文字だけの場合は未入力とする
		if (!preg_match('/[^ 　]/', $val)) {
			$str = '';

		// 制御文字の削除、1000文字以内
		} else {
			$str = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', mb_substr(trim($val), 0, 1000));
		}

		return apply_filters('mtssb_options_normalize_textarea', $str, $val);
	}
}

/**
 * ラジオボタン択一オプション
 *
 */
class MTSSB_OPT_Radio extends MTSSB_OPT_Field
{
	public function __construct($item=array())
	{
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * 値を設定する
	 *
	 */
	public function setValue($val)
	{
		$fields = $this->getField();

		if (array_key_exists($val, $fields)) {
			$this->val = $val;
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 */
	public function normalize($val)
	{
		$fields = $this->getField();

		if (array_key_exists($val, $fields)) {
			return $val;
		}

		return '';
	}

	/**
	 * 設定値をテキストで戻す
	 *
	 */
	public function getText()
	{
		$fields = $this->getField();
		$fieldname = $this->val;

		if ($fieldname && array_key_exists($fieldname, $fields)) {
			return $fields[$fieldname]['label'];
		}

		return '';
	}
}

/**
 * セレクトボックス択一オプション
 *
 */
class MTSSB_OPT_Select extends MTSSB_OPT_Field
{
	public function __construct($item=array())
	{
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * 値を設定する
	 *
	 */
	public function setValue($val)
	{
		$fields = $this->getField();

		if (array_key_exists($val, $fields)) {
			$this->val = $val;
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 */
	public function normalize($val)
	{
		$fields = $this->getField();

		if (array_key_exists($val, $fields)) {
			return $val;
		}

		return '';
	}

	/**
	 * 設定値をテキストで戻す
	 *
	 */
	public function getText()
	{
		$fields = $this->getField();
		$fieldname = $this->val;

		if ($fieldname && array_key_exists($fieldname, $fields)) {
			return $fields[$fieldname]['label'];
		}

		return '';
	}
}

/**
 * チェックボックスオプション
 *
 */
class MTSSB_OPT_Check extends MTSSB_OPT_Field
{
	public function __construct($item=array())
	{
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * チェックフィールドを追加する
	 *
	 * @keyary	array(field name => 0 or 1 ...)
	 */
	public function setValue($keyary)
	{
		$newval = array();

		// DBデータから読み込まれた文字列なら,区切りの配列に変換する
		if ($keyary != '') {
			if (!is_array($keyary)) {
				$keyary = explode(',', $keyary);
			}

			// キー並びがフィールドにあれば選択値として取り込む
			$fields = $this->getField();
			foreach ($keyary as $val) {
				if (array_key_exists($val, $fields)) {
					$newval[] = $val;
				}
			}
		}

		$this->val = $newval;
	}

	/**
	 * データをカンマ区切りで戻す
	 *
	 */
	public function getValue($raw=false)
	{
		$val = '';

		// オブジェクト内配列データをそのまま戻す
		if ($raw) {
			$val = $this->val;
		}
		// それ以外はフィールド名をカンマ区切りで戻す
		else {
            if (is_array($this->val)) {
                foreach ($this->val as $value) {
                    $val .= empty($val) ? $value : ",{$value}";
                }
            } else {
                $val = $this->val;
            }
		}

		return $val;
	}

	/**
	 * データがチェックされたデータか確認する
	 *
	 */
	public function isChecked($fieldname)
	{
		if (!empty($this->val) && in_array($fieldname, $this->val)) {
			return true;
		}

		return false;
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 * @val		array('fieldname' => 0 or 1);
	 */
	public function normalize($val) {
		$newval = array();
		$fields = $this->getField();

		foreach ($val as $checkname => $bit) {
			if (array_key_exists($checkname, $fields) && $bit) {
				$newval[] = $checkname;
			}
		}

		return $newval;
	}

	/**
	 * 設定値をテキストで戻す
	 *
	 */
	public function getText() {
		$str = '';
		$fields = $this->getField();

        if (is_array($this->val)) {
            foreach ($this->val as $value) {
                if (array_key_exists($value, $fields)) {
                    $str .= (empty($str) ? '' : ',') . $fields[$value]['label'];
                }
            }
        }

		return $str;
	}
}

/**
 * 日付オプション
 *
 */
class MTSSB_OPT_Date extends MTSSB_OPT_Field
{
	public function __construct($item=array()) {
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 * @date	array('year'=>, 'month'=>, 'day'=> )
	 */
	public function normalize($val) {
		if (is_numeric($val['year']) && is_numeric($val['month']) && is_numeric($val['day'])) {
			return strtotime("{$val['year']}-{$val['month']}-{$val['day']}");
		}
		return '';
	}

	/**
	 * 設定値をテキストで戻す
	 *
	 */
	public function getText() {
		$str = empty($this->val) ? '' : date_i18n('Y年m月d日', $this->val);

		return apply_filters('mtssb_options_date_text', $str, $this->val);
	}
}

/**
 * 時刻オプション
 *
 */
class MTSSB_OPT_Time extends MTSSB_OPT_Field {

	public function __construct($item=array()) {
		parent::__construct($item);

		// 初期値
		if (isset($item['val'])) {
			$this->setValue($item['val']);
		}
	}

	/**
	 * データをオブジェクトタイプに合わせて正規化する
	 *
	 * @date	array('hour'=>, 'minute'=>)
	 */
	public function normalize($val) {
		if (is_numeric($val['hour']) && is_numeric($val['minute'])) {
			return strtotime("{$val['hour']}:{$val['minute']}");
		}
		return '';
	}

	/**
	 * 設定値をテキストで戻す
	 *
	 */
	public function getText()
	{
		$str = empty($this->val) ? '' : date_i18n('H時i分', $this->val);

		return apply_filters('mtssb_options_time_text', $str, $this->val);
	}
}

/**
 * オプション項目抽象モデル
 *
 */
class MTSSB_OPT_Field
{
	const VERSION = '1.8.5';

	private static $optType = array('number', 'text', 'radio', 'select', 'check', 'date', 'time', 'textarea');

	/**
	 * Data structure
	 */
	private $data = array(
		'optionid' => 0,	// No.
		'required' => 1,	// 必須入力設定項目(0:任意 1:必須)
		'keyname' => '',	// キー名
		'name' => '',		// ラベル名
		'type' => '',		// オプションタイプ(number,radio,select,check,date,text,textarea,time)
		'field' => array(),	// array('itemname', 'itemlabel', 'time', 'price')
		'note' => '',		// オプションの説明
		'price' => 0,		// 料金
		'whose' => '',		// 料金の対象(all,adult,child,baby,booking)
	);

	protected $val = '';

	/**
	 * itemデータからオブジェクトを生成
	 *
	 */
	public function __construct($item = array()) {

		// 初期設定データがある場合
		if (!empty($item)) {
			// オプションID
			if (isset($item['optionid'])) {
				$this->optionid = $item['optionid'];
				if ($this->optionid != $item['optionid']) {
					throw new Exception('OPTIONID_UNAVAILABLE');
				}
			}

			// 必須入力設定項目
			if (isset($item['required'])) {
				$this->required = ($item['required']);
			}

			// オプションキー名
			if (isset($item['keyname'])) {
				$this->keyname = $item['keyname'];
				if ($this->keyname != $item['keyname']) {
					throw new Exception('KEYNAME_UNAVAILABLE');
				}
			}

			// オプションラベル名
			if (isset($item['name'])) {
				$this->name = $item['name'];
			}

			// オプションタイプ
			if (isset($item['type'])) {
				$this->type = $item['type'];
			}

			// オプションフィールドデータ
			if (!empty($item['field'])) {
				$this->field = $item['field'];
				if (empty($this->field)) {
					throw new Exception('FIELDNAME_UNAVAILABLE');
				}
			}

			// オプション説明文
			if (isset($item['note'])) {
				$this->note = $item['note'];
			}

			// 金額の設定
			if (isset($item['price'])) {
				$this->price = $item['price'];
			}

			// 料金属性の設定
			if (isset($item['whose'])) {
				$this->whose = $item['whose'];
			}
		}
	}

	/**
	 * 価格付けの確認
	 *
	 */
	public function isPriced()
	{
		// priceプロパティがセットされていればtrueを戻す
		if ($this->price != 0) {
			return true;
		}

		// fieldのpriceがセットされていればtrueを戻す
		if (!empty($this->field)) {
			foreach ($this->field as $field) {
				if (!empty($field['price'])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * 初期値をセットする
	 *
	 */
	public function newValue() {
		$this->val = 0;
	}

	/**
	 * 値を取得する
	 *
	 */
	public function getValue() {
		return $this->val;
	}

	/**
	 * 設定値をテキストで戻す(オーバーライド用)
	 *
	 */
	public function getText() {
		return $this->val;
	}

	/**
	 * 値を設定する
	 *
	 */
	public function setValue($val) {
		$this->val = $val;
	}

	/**
	 * オプション定義データを配列で戻す
	 *
	 */
	public function getDataArray() {
		return $this->data;
	}

	/**
	 * フィールドの設定
	 */
	protected function setField($fields) {
		$newf = array();

		foreach ($fields as $fkey => $fval) {
			if (empty($fkey) || preg_match('/^[0-9a-z][0-9a-z_\-]*$/i', $fkey)) {
				$newf[$fkey] = array(
					'label' => $fval['label'],
					'time' => $fval['time'],
					'price' => $fval['price'],
				);
			} else {
				return null;
			}
		}

		$this->data['field'] = $newf;
		return $newf;
	}

	/**
	 * 料金が掛る属性の設定
	 *
	 * '', all, adult, child, baby, booking
	 */
	protected function setWhose($whose) {
		$whose_type = array('', 'all', 'adult', 'child', 'baby', 'booking');

		if (in_array($whose, $whose_type)) {
			$this->data['whose'] = $whose;
		}

		return $this->data['whose'];
	}

	/**
	 * オプションIDの取得
	 */
	public function getOptionid() {
		return $this->data['optionid'];
	}

	/**
	 * 必須入力設定項目の取得
	 */
	public function getRequired() {
		return $this->data['required'];
	}

	/**
	 * キー名の取得
	 */
	public function getKeyname() {
		return $this->data['keyname'];
	}

	/**
	 * ラベル名の取得
	 */
	public function getLabel() {
		return $this->data['name'];
	}

	/**
	 * オプションタイプの取得
	 */
	public function getType() {
		return $this->data['type'];
	}

	/**
	 * フィールドの取得
	 */
	public function getField() {
		return $this->data['field'];
	}

	/**
	 * オプション説明の取得
	 */
	public function getNote() {
		return $this->data['note'];
	}

	/**
	 * 料金の取得
	 */
	public function getPrice($key = '')
	{
		if (empty($key) ) {
			return $this->data['price'];
		}
		if (isset($this->data['field'][$key]['price'])) {
			return $this->data['field'][$key]['price'];
		}
		return false;
	}

	/**
	 * 追加時間割の設定を取得する
	 *
	 */
	public function getTimetable($key) {
		if (isset($this->field[$key]['time'])) {
			return $this->field[$key]['time'];
		}
		return false;
	}

	/**
	 * 定義されたオプションタイプか調べる
	 */
	static public function is_option_type($key) {
		if (in_array($key, self::$optType)) {
			return true;
		}

		return false;
	}

	/**
	 * プロパティをセットする
	 *
	 */
	public function __set($key, $value)
	{
		if (array_key_exists($key, $this->data)) {
			switch ($key) {
				case 'optionid':
					$this->data['optionid'] = intval($value);
					break;
				case 'keyname':
					if (preg_match('/^[0-9a-z][0-9a-z_\-]*$/i', $value)) {
						$this->data['keyname'] = $value;
					}
					break;
				case 'required':
					$this->data[$key] = intval($value);
					break;
				case 'type':
					if (in_array($value, self::$optType)) {
						$this->data['type'] = $value;
					} else {
						$this->data['type'] = self::$optType[0];
					}
					break;
				case 'price':
					$this->data[$key] = floatval($value);
					break;
				case 'field':
					$this->setField($value);
					break;
				case 'whose':
					$this->setWhose($value);
					break;
				case 'type':
				default:
					$this->data[$key] = $value;
			}

			return $this->data[$key];
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
		trigger_error("Undefined property: '" . $key
		 . "' in " . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);

		return null;
	}

	/**
	 * isset(),empty()アクセス不能プロパティ
	 * プロパティの値がemptyかチェックされた時の情報を戻す(isset()は不可)
	 */
	public function __isset($key)
	{
		if (array_key_exists($key, $this->data)) {
			// empty()の場合__isset()の反転を戻す
			return !empty($this->data[$key]);
		}

		return false;
	}

}
