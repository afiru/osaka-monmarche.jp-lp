<?php
if (!class_exists('MTSSB_Option')) {
	require_once('mtssb-options.php');
}
/**
 * MTS Simple Booking オプション設定管理モジュール
 *
 * @Filename	mtssb-option-admin.php
 * @Date		2012-04-24
 * @Author		S.Hayashi
 *
 * Updated to 1.28.0 on 2017-12-13
 * Updated to 1.12.1 on 2014-01-06
 * Updated to 1.12.0 on 2013-12-24
 * Updated to 1.9.0 on 2013-07-10
 * Updated to 1.6.0 on 2013-03-19
 * Updated to 1.4.0 on 2013-01-29
 * Updated to 1.3.0 on 2013-01-06
 * Updated to 1.1.0 on 2012-10-03
 */

class MTSSB_Option_Admin extends MTSSB_Option {
	const VERSION = '1.12.1';
	const PAGE_NAME = 'simple-booking-option';

	/**
	 * Instance of this object module
	 */
	static private $iOption = null;

	/**
	 * オプションデータ構造(配列)
	 *
	 * array('optionid' => array('stock', 'name', 'note')
	 */
	private $catalog_name = '';	// オプションカタログ名
	private $catalog = array();
	public $group_name = '';	// 操作対象オプショングループ名(カタログ定義名)

	private $data;

	public $object = '';		// 操作対象(group or option)
	private $action = '';
	private $procedure = '';	// add:新規追加 edit:編集
	private $message = '';
	private $errflg = false;

	public $nonce_word;

	/**
	 * インスタンス化
	 *
	 */
	static function get_instance() {
		if (!isset(self::$iOption)) {
			self::$iOption = new MTSSB_Option_Admin();
		}

		return self::$iOption;
	}

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		global $mts_simple_booking;

		parent::__construct();

		$this->nonce_word = "{$this->domain}_option";
		$this->catalog_name = $this->domain . MTS_Simple_Booking::CATALOG_NAME;

		// リストテーブルモジュールのロード
		if (!class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
		}

		// CSSロード
		$mts_simple_booking->enqueue_style();

		// Javascriptロード
		wp_enqueue_script("mtssb_option_admin_js", $mts_simple_booking->plugin_url . "js/mtssb-option-admin.js", array('jquery'));

		// オプションカタログを取得する(Ver.1.9追加)
		$this->catalog = get_option($this->catalog_name);

		// オプションカタログの登録(Ver.1.9追加 グループオプションへ変更)
		if ($this->catalog === false) {
			$this->_create_catalog();
		}
	}

	/**
	 * オプションカタログの登録(グループオプションに更新)
	 *
	 */
	private function _create_catalog()
	{
		$this->catalog = array();	// カタログデータ初期値

		// 旧オプションを読み込む
		$data = get_option($this->domain . '_option');

		// 旧オプションがあればそのカタログを作る
		if ($data) {
			$this->catalog['default'] = 'Default';
		}

		// カタログの新規登録
		update_option($this->catalog_name, $this->catalog);

		// 旧オプションを新オプションとして登録する
		if ($data) {
			// 新オプションデータが登録済みか確認する
			$newd = get_option($this->domain . '_option_default');

			if ($newd === false) {
				// 新旧オプションデータ名
				$old_name = '';
				$new_name = 'default';

				// オプション名を新しく書き換える
				$this->_rename_option($old_name, $new_name);
			}
		}

		return;
	}

	/**
	 * オプション名の書き換え
	 *
	 */
	private function _rename_option($old_name, $new_name)
	{
		global $wpdb;

		$old_opname = $this->setOptionName($old_name);
		$new_opname = $this->setOptionName($new_name);

		// 旧オプション名のデータを取得する
		$option = $wpdb->get_row($wpdb->prepare("
			SELECT option_id,option_name,autoload
			FROM $wpdb->options
			WHERE option_name=%s", $old_opname
		), ARRAY_A);

		// 新旧オプション名が異なれば書き換える
		if ($option && $option['option_name'] != $new_opname) {
			$data = array('option_name' => $new_opname);
			$key = array('option_id' => $option['option_id']);

			$wpdb->update($wpdb->options, $data, $key, array('%s'), array('%d'));
		}

		return;
	}

	/**
	 * Dispatcher
	 *
	 */
	public function option_page() {
		// Initialize
		$this->message = '';
		$this->errflg = false;

		// 操作対象
		$this->object = isset($_REQUEST['object']) ? $_REQUEST['object'] : '';
		$this->action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

		// オプショングループ選択画面
		if (empty($this->object) || $this->object != 'option') {
			$this->_select_option_group();
			return;
		}

		// オプションオブジェクトの生成
		$this->group_name = $_REQUEST['group']['name'];
		$this->loadOption($this->group_name);

		$this->procedure = $this->action;

		// 新規画面表示
		if ($this->action == 'add') {
			$option_data = $this->_get_default();
			return $this->_option_form($option_data);
		}
		// 編集画面表示
		else if ($this->action == 'edit') {
			$option = $this->get_option(intval($_GET['optionid']));
			if ($option) {
				$option_data = $option->getDataArray();
				return $this->_option_form($option_data);
			}
		}
		// 新規・編集保存
		else if ($this->action == 'save') {
			$this->procedure = $_POST['procedure'];
			if (wp_verify_nonce($_POST['nonce'], $this->nonce_word)) {
				$option_data = $this->_save_option(intval($_POST['orgid']));
				return $this->_option_form($option_data);
			} else {
				$this->_set_message('NONCE ERROR');
			}
		}
		// 削除
		else if (empty($_REQUEST['bulk'])) {
			if ($this->action == 'delete') {
				if (wp_verify_nonce($_GET['nonce'], $this->nonce_word)) {
					$optionids = $_GET['optionid'];
					$this->_delete_option($optionids);
				} else {
					$this->_set_message('NONCE_ERROR');
				}
			}
		}
		// バルク削除
		else if ($this->action == 'delete' || (isset($_REQUEST['action2']) && $_REQUEST['action2'] == 'delete')) {
			if (wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-options')) {
				$optionids = $_REQUEST['optionid'];
				$this->_delete_option($optionids);
			} else {
				$this->_set_message('NONCE_ERROR');
			}
		}

		// 操作対象オプションリスト
		$list_table = new MTSSB_Option_List($this);
		$list_table->prepare_items();

		$this->_option_list($list_table);
	}

	/**
	 * オプショングループの選択
	 *
	 */
	private function _select_option_group()
	{
		$group = array('name' => '', 'title' => '');

		// 入力の確認
		if (!empty($this->action)) {
			$group = $_REQUEST['group'];
			$group['name'] = trim($group['name']);
			$group['title'] = trim($group['title']);

			if (!wp_verify_nonce($_REQUEST['nonce'], $this->nonce_word)) {
				$this->_set_message('NONCE_ERROR');
				echo "<p>{$this->message}</p>";
				return;
			}

			// オプションキー名の入力が正しいか確認する
			if (empty($group['name']) || preg_match('/[^a-zA-Z0-9_\-]/', $group['name'])) {
				$this->_set_message(empty($group['name']) ? 'NAME_NEEDED' : 'KEYNAME_UNAVAILABLE');

			} else {
				switch ($this->action) {
					// 新規追加
					case 'add':
						// 既存グループか確認する
						if (array_key_exists($group['name'], $this->catalog)) {
							$this->_set_message('NAME_OVERLAPPED');
						// 新しくオプションを追加保存する
						} else {
							// オプショングループの追加更新
							$this->catalog[$group['name']] = $group['title'];
							ksort($this->catalog);
							update_option($this->catalog_name, $this->catalog);
							// オプションの追加
							$this->setOptionName($group['name']);
							$this->writeOptions();
							$this->_set_message('GROUP_ADDED');
						}
						break;

					// 編集
					case 'edit':
						// 元のグループ名の確認
						if (!array_key_exists($group['sel_name'], $this->catalog)) {
							$this->_set_message('NAME_NOTFOUND');
						} else {
							// グループ名が変更された場合はもとのグループ名を削除、オプション名を変更
							if ($group['sel_name'] != $group['name']) {
								unset($this->catalog[$group['sel_name']]);
								$this->_rename_option($group['sel_name'], $group['name']);
							}
							// 名称を変更して保存する
							$this->catalog[$group['name']] = $group['title'];
							ksort($this->catalog);
							update_option($this->catalog_name, $this->catalog);
							$this->_set_message('GROUP_REPLACED');
						}
						break;

					// 削除
					case 'delete':
						if (!array_key_exists($group['name'], $this->catalog)) {
							$this->_set_message('NAME_NOTFOUND');
						} else {
							unset($this->catalog[$group['name']]);
							ksort($this->catalog);
							update_option($this->catalog_name, $this->catalog);
							// オプションの削除
							$option_name = $this->setOptionName($group['name']);
							delete_option($option_name);
							$this->_set_message('GROUP_REMOVED');
						}
						break;
					default:
						break;
				}
			}
		}

?>
	<div class="wrap">
		<h2><?php _e('Select Option Group', $this->domain) ?>
			<a id="add-group-button" class="add-new-h2" href="#"><?php _e('Add New', $this->domain) ?></a>
		</h2>
		<?php if (!empty($this->message)) : ?>
			<div class="<?php echo ($this->errflg) ? 'error' : 'updated' ?>"><p><strong><?php echo $this->message; ?></strong></p></div>
		<?php endif; ?>

		<div id="select-option-group">
<?php if (empty($this->catalog)) : ?>
			<p><?php _e('Add a option group to click add button.', $this->domain) ?></p>
<?php else : ?>
			<form method="get" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<input type="hidden" name="page" value="<?php echo self::PAGE_NAME ?>" />

				<table class="form-table" style="width: 100%">
					<tr class="form-field">
						<th>
							<?php _e('Option Group', $this->domain) ?>
						</th>
						<td>
							<select id="select-group-name" name="group[name]">
								<?php foreach ($this->catalog as $groupname => $grouptitle) : ?>
								<option value="<?php echo $groupname . '"' . ($groupname == $group['name'] ? ' selected="selected"' : '') ?> ><?php echo $grouptitle ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
	
				<div id="select-group-ope">
					<p>
						<button id="edit-group-button" class="button-secondary"><?php _e('Edit Name', $this->domain) ?></button> 
						<button type="submit" class="button-secondary" name="action" value="list"><?php _e('List Option', $this->domain) ?></button>
					</P>
				</div>
				<input type="hidden" name="object" value="option" />
			</form>
<?php endif; ?>
		</div>

		<div id="edit-group-name-box" style="display:none">
			<p>
				<button id="return-group-button" class="button-secondary"><?php _e('Return', $this->domain) ?></button>
			</p>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<table class="form-table" style="width: 100%">
					<tr>
						<th>
							<?php _e('Group Key Name', $this->domain) ?>
						</th>
						<td>
							<input id="option-group-name" type="text" name="group[name]" value="<?php echo $group['name'] ?>" /><br />
							<?php _e("It's used for distinguishing each option groups.", $this->domain) ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Group Title', $this->domain) ?>
						</th>
						<td>
							<input id="option-group-title" type="text" name="group[title]" value="<?php echo $group['title'] ?>" /><br />
						</td>
					</tr>
				</table>
				<p id="select-ope-edit-box">
					<button type="submit" id="select-ope-save-btn" class="button-secondary" name="action" value="edit"><?php _e('Save') ?></button> 
					<button type="submit" id="select-ope-delete-btn" class="button-secondary" name="action" value="delete" onclick="return confirm('<?php _e("Delete this option group?", $this->domain) ?>')"><?php _e('Delete') ?></button>
				</p>
				<p id="select-ope-add-box">
					<button type="submit" id="select-ope-add-btn" class="button-secondary" name="action" value="add"><?php _e('Add') ?></button>
				</p>
				<input id="option-group-sel-name" type="hidden" name="group[sel_name]" value="" />
				<input type="hidden" name="object" value="group" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce($this->nonce_word) ?>" />
			</form>
		</div>
	</div>
<?php
	}

	/**
	 * 処理結果メッセージの設定
	 */
	private function _set_message($msgid) {
		$message = array(
			'ADDED' => __('Option data has been added.', $this->domain),
			'REPLACED' => __('Option data has been saved.', $this->domain),
			'REMOVED' => __('Option data has been deleted.', $this->domain),
			'OPTIONID_UNAVAILABLE' => __("Option ID shouldn't be 0.", $this->domain),
			'OPTIONID_DUPLICATED' => __("Option ID has been duplicated.", $this->domain),
			'KEYNAME_UNAVAILABLE' => __("Input only alphanumeric characters for key name.", $this->domain),
			'FIELDNAME_UNAVAILABLE' => __('Input only alphanumeric characters for the key name of field item.', $this->domain),

			'NAME_NEEDED' => __('Empty is unable to set.', $this->domain),
			'NAME_OVERLAPPED' => __('The option name has been already.', $this->domain),
			'NAME_NOTFOUND' => __('The option name has not been found.', $this->domain),
			'GROUP_ADDED' => __('A new option group has been added.', $this->domain),
			'GROUP_REPLACED' => __('The option group has been saved.', $this->domain),
			'GROUP_REMOVED' => __('The option group has been removed.', $this->domain),
		);

		// エラーフラグのセット
		if (!preg_match('/(ADDED|REPLACED|REMOVED)/', $msgid)) {
			$this->errflg = true;
		}

		// メッセージのセット
		if (array_key_exists($msgid, $message)) {
			$this->message = $message[$msgid];
		} else {
			$this->message = __('Unknow result has been occured.', $this->domain) . "($msgid)";
		}
	}

	/**
	 * オプションリスト
	 *
	 */
	private function _option_list($list_table)
	{
		$add_url = add_query_arg(array('action' => 'add'), $_SERVER['REQUEST_URI']);
		$ret_url = add_query_arg(array('page'=> self::PAGE_NAME), $_SERVER['SCRIPT_NAME']);
		$bulk_url = add_query_arg(array('bulk' => 1), $_SERVER['REQUEST_URI']);

		$group_title = $this->catalog[$_REQUEST['group']['name']];
?>
	<div class="wrap">
		<h2><?php _e('Option List', $this->domain) ?></h2>

		<?php if (!empty($this->message)) : ?><div class="<?php echo $this->errflg ? 'error' : 'updated' ?>">
			<p><?php echo $this->message ?></p>
		</div><?php endif; ?>

		<h3><?php _e('Option group', $this->domain) ?> : <?php echo $group_title ?></h3>
		<ul class="subsubsub">
			<li><a class="add-new-h2" href="<?php echo esc_url($add_url) ?>"><?php _e('Add New', $this->domain) ?></a> | </li>
			<li><a href="<?php echo esc_url($ret_url) ?>"><?php _e('Return', $this->domain) ?></a></li>
		</ul>

		<div id="option-list">
			<form id="movies-filter" method="post" action="<?php echo esc_url($bulk_url) ?>">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $list_table->display() ?>
			</form>
		</div>
	</div>

<?php
	}

	/**
	 * オプション編集フォーム
	 *
	 */
	private function _option_form($option)
	{
		$fieldtype = $this->field_type_label();
		$whosecost = $this->whose_cost_label();

		$add_url = add_query_arg(array('optionid' => false, 'action' => 'add'), $_SERVER['REQUEST_URI']);
		$ret_url = add_query_arg(array('optionid' => false, 'action' => 'list'), $_SERVER['REQUEST_URI']);

		// オプショングループ名
		$group_title = $this->catalog[$_REQUEST['group']['name']];

		// オプションデータのフィールドデータをフォームデータに変更する
		$fields = array();
		foreach ($option['field'] as $fieldkey => $val) {
			$fields[] = $this->_chg_field($fieldkey, $val);
		}
		$option['field'] = $fields;

?>
	<div class="wrap">

		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php echo __('Option Item', $this->domain) ?> <?php echo $this->procedure == 'edit' ? __('Edit') : __('Add') ?></h2>

		<?php if (!empty($this->message)) : ?><div class="<?php echo $this->errflg ? 'error' : 'updated' ?>">
			<p><?php echo $this->message ?></p>
		</div><?php endif; ?>

		<ul class="subsubsub">
			<li><a class="add-new-h2" href="<?php echo esc_url($add_url) ?>"><?php _e('Add New', $this->domain) ?></a> | </li>
			<li><a href="<?php echo esc_url($ret_url) ?>"><?php _e('Return', $this->domain) ?></a></li>
		</ul>
		<div class="clear"></div>

		<h3><?php _e('Option group', $this->domain) ?> : <?php echo $group_title ?></h3>

		<form id="mtssb-edit-option" class="validate" method="post">
			<?php wp_create_nonce(self::PAGE_NAME) ?>
			<table class="form-table">
			<tr>
				<th><label for="opt-optionid"><?php _e('Order of display', $this->domain) ?></label></th>
				<td>
					<input id="opt-optionid" type="text" class="small-text" name="mts_simple_booking_option[optionid]" value="<?php echo esc_html($option['optionid']) ?>" />
					<p><?php _e("It's not be able to use the same number.", $this->domain) ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="opt-required"><?php _e('Required', $this->domain) ?></label></th>
				<td>
					<input id="opt-required-" type="hidden" name="mts_simple_booking_option[required]" value="0" />
					<input id="opt-required" type="checkbox" name="mts_simple_booking_option[required]" value="1"<?php echo $option['required'] == 1 ? ' checked="checked"' : '' ?> />
					<p><?php _e("It's used for checking if this option is entered.", $this->domain) ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="opt-keyname"><?php _e('Key Name', $this->domain) ?></label></th>
				<td>
					<input id="opt-keyname" type="text" class="regular-text" name="mts_simple_booking_option[keyname]" value="<?php echo esc_html($option['keyname']) ?>" />
					<p><?php _e("It's used for distinguishing each options.", $this->domain) ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="opt-name"><?php _e('Name', $this->domain) ?></label></th>
				<td>
					<input id="opt-name" type="text" class="regular-text" name="mts_simple_booking_option[name]" value="<?php echo esc_html($option['name']) ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="opt-price"><?php _e('Price', $this->domain) ?></label></th>
				<td>
					<input id="opt-price" type="text" name="mts_simple_booking_option[price]" value="<?php echo esc_html(isset($option['price']) ? $option['price'] : 0) ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="opt-whose"><?php _e('Whose Cost', $this->domain) ?></label></th>
				<td>
					<select id="opt-whose" name="mts_simple_booking_option[whose]">
						<?php foreach ($whosecost as $whose => $title) : ?>
						<option value="<?php echo $whose ?>"<?php echo isset($option['whose']) && $option['whose'] == $whose ? ' selected="selected"' : '' ?>><?php echo $title ?></option>
						<?php endforeach; ?>
					</select>
					<p><?php _e('Select the someone to cost.', $this->domain) ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="opt-type"><?php _e('Field Type', $this->domain) ?></label></th>
				<td>
					<select id="opt-type" name="mts_simple_booking_option[type]">
						<?php foreach ($fieldtype as $fieldid => $fieldname) : ?>
						<option value="<?php echo $fieldid ?>"<?php echo $option['type'] == $fieldid ? ' selected="selected"' : '' ?>><?php echo $fieldname ?></option>
						<?php endforeach; ?>
					</select>
					<p><?php _e('Select this field type.', $this->domain) ?></p>

					<div id="field-item" class="form-field" style="display:none">
						<div id="field-item-action">
							<button id="add-field-button" class="field-button"><?php _e('Add a field', $this->domain) ?></button>
						</div>

						<table id="field-item-table">
							<thead>
								<tr>
									<th class="option-field-item"><?php _e('Key name', $this->domain) ?></th>
									<th class="option-field-item"><?php _e('Label name', $this->domain) ?></th>
									<th class="option-field-item"><?php _e('Price', $this->domain) ?></th>
									<th class="option-field-item"><?php _e('Time needed', $this->domain) ?></th>
									<th class="option-field-item item-subaction">&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php $itemno = 1; foreach ($option['field'] as $keyname => $val) : ?><tr id="field-item-row<?php echo $itemno ?>">
									<td class="option-field-item key"><input class="item-input" type="text" name="mts_simple_booking_option[field][<?php echo $itemno ?>][key]" value="<?php echo esc_html($val['key']) ?>" maxlength="128" /></td>
									<td class="option-field-item label"><input class="item-input" type="text" name="mts_simple_booking_option[field][<?php echo $itemno ?>][label]" value="<?php echo esc_html($val['label']) ?>" /></td>
									<td class="option-field-item price"><input class="item-input" type="text" name="mts_simple_booking_option[field][<?php echo $itemno ?>][price]" value="<?php echo $val['price'] ?>" /></td>
									<td class="option-filed-item time"><input class="item-input" type="text" name="mts_simple_booking_option[field][<?php echo $itemno ?>][time]" value="<?php echo intval($val['time']) ?>" /></td>
									<td class="option-field-item item-subaction"><button class="field-button" onclick="optionAdmin.del_item(<?php echo $itemno ?>); return false;"><?php _e('Delete') ?></button></td>
								</tr><?php $itemno++; endforeach; ?>
							</tbody>
						</table>
						<p><?php _e('Type alphanumeric into a keyname.', $this->domain); ?></p>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="opt-note"><?php _e('Note', $this->domain) ?></label></th>
				<td>
					<input id="opt-note" type="text" class="regular-text" name="mts_simple_booking_option[note]" value="<?php echo esc_html($option['note']) ?>" />
				</td>
			</tr>
			</table>

			<p class="submit">
				<button id="submit" class="button button-primary" type="submit" name="procedure" value="<?php echo esc_html($this->procedure) ?>"><?php echo $this->procedure == 'edit' ? _e('Save item', $this->domain) : _e('Add item', $this->domain) ?></button>
			</p>
			<input type="hidden" name="orgid" value="<?php echo $this->procedure == 'edit' ? $option['optionid'] : '' ?>" />
			<!-- input type="hidden" name="group[name]" value="<?php echo $this->group_name ?>" / -->
			<!-- input type="hidden" name="object" value="<?php echo $this->object ?>" / -->
			<input type="hidden" name="action" value="save" />
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce($this->nonce_word) ?>" />
		</form>

		<table id="option-field-frame" style="display: none">
			<tr id="option-field-frame-row">
				<td class="option-field-item key"><input class="item-input" type="text" value="" maxlength="128" /></td>
				<td class="option-field-item label"><input class="item-input" type="text" value="" /></td>
				<td class="option-field-item price"><input class="item-input" type="text" value="0" /></td>
				<td class="option-field-item time"><input class="item-input" type="text" value="0" /></td>
				<td class="option-field-item item-subaction"><button class="field-button" onclick="return false;"><?php _e('Delete') ?></button></td>
			</tr>
		</table>

	</div><!-- wrap -->

<?php
	}

	/**
	 * オブジェクトのフィールドデータからフォームのフィールドデータに変換する
	 *
	 */
	private function _chg_field($keyname, $field)
	{
		$forma = array(
			'key' => $keyname,
			'label' => '',
			'price' => 0,
			'time' => 0,
		);

		// Ver.1.4以降
		if (is_array($field)) {
			foreach ($field as $key => $val) {
				$forma[$key] = $val;
			}
		}
		// ver.1.3以前
		else {
			$forma['label'] = $field;
		}

		return $forma;
	}

	/**
	 * 新規・編集オプションを保存する
	 *
	 * @orgid		元のOptionID 新規は''
	 * @return 		OptionID 編集または新規追加したもの
	 */
	private function _save_option($orgid)
	{
		// 入力データをitemデータにする
		$item = $this->_into_item();

		// 新しいオブジェクトを生成する
		try {
			$newo = $this->getInstance($item);
			$optionid = $newo->getOptionid();

			// $orgidが''なら新規追加
			if (empty($orgid)) {
				$this->addOption($newo);

			// $orgid が optionidと等しければ上書き
			} elseif ($orgid == $optionid) {
				$this->replaceOption($newo);

			// それ以外なら置き換える
			} else {
				$this->addOption($newo);
				$this->removeOption($orgid);
			}

			// 保存する
			$this->writeOptions();

			// メッセージをセットする
			$this->_set_message(empty($orgid) ? 'ADDED' : 'REPLACED');

			// フォーム形式のデータを戻す
			$item = $this->get_option($optionid)->getDataArray();

		} catch (Exception $e) {
			// エラーをセットする
			$this->_set_message($e->getMessage());
		}

		return $item;
	}

	/**
	 * オプションデータの削除
	 *
	 */
	protected function _delete_option($optionids) {

		// 配列ならWP_Listのbulk要求で配列を削除
		if (is_array($optionids)) {
			$ret = -1;
			foreach ($optionids as $optionid) {
				$oid = $this->removeOption(intval($optionid));
				if (0 <= $oid) {
					$ret = $oid;
				}
			}
		// 配列でなければそのまま削除
		} else {
			$ret = $this->removeOption(intval($optionids));
		}

		// 保存する
		if (0 <= $ret) {
			$this->writeOptions();
			$this->_set_message('REMOVED');
		}
	}

	/**
	 * 入力データをitemデータにする
	 *
	 */
	private function _into_item() {
		if (get_magic_quotes_gpc()) {
			$data = stripslashes_deep($_POST['mts_simple_booking_option']);
		} else {
			$data = $_POST['mts_simple_booking_option'];
		}

		// フィールドデータをitem形式にする
		$field = array();
		if (isset($data['field'])) {
			foreach ($data['field'] as $infield) {
				$field[$infield['key']] = array(
					'label' => $infield['label'],
					'price' => $infield['price'],
					'time' => $infield['time'],
				);
			}
		}
		$data['field'] = $field;

		return $data;
	}

	/**
	 * 初期データ(item形式)
	 *
	 * field => array('key' => array('label' => '', 'time' => 0, 'price' => 0))
	 */
	protected function _get_default() {
		return array(
			'optionid' => '',
			'required' => 0,
			'keyname' => '',
			'name' => '',
			'type' => 'number',
			'field' => array(),
			'note' => '',
			'price' => 0,
			'whose' => '',
		);
	}

	/**
	 * オプションデータリストを戻す
	 *
	 */
	public function option_list($pos, $per) {
		$lists = array();

		$max = $this->count_option();
		for ($i = 0; $i + $pos < $max && $i < $per; $i++) {
			$idx = $i + $pos;
			$option = $this->get_option_no($idx);
			$lists[] = $option->getDataArray();
		}

		return $lists;
	}

}

/**
 * オプションのリスト表示モジュール
 *
 */
class MTSSB_Option_List extends WP_List_Table
{
	const VERSION = '1.9.0';
	const PAGE_NAME = 'simple-booking-option';

	/**
	 * Instance
	 */
	static private $iList = null;

	// データアクセスオブジェクト
	private $DAO = null;

	private $domain = '';
	private $option_name = 'option_list';
	private $per_page = 20;

	private $field_type = array();
	private $whose_cost = array();

	/**
	 * Constructor
	 *
	 */
	public function __construct($dao = null) {

		parent::__construct(array(
			'singular' => 'option',
			'plural' => 'options',
			'ajax' => false,
		));

		$this->domain = MTS_Simple_Booking::DOMAIN;
		$this->DAO = $dao;

		$this->field_type = $dao->field_type_label();
		$this->whose_cost = $dao->whose_cost_label();
	}

	/**
	 * リストカラム情報
	 *
	 */
	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'optionid' => __('Option ID', $this->domain),
			'keyname' => __('Key Name', $this->domain),
			'required' => __('Required', $this->domain),
			'name' => __('Option Name', $this->domain),
			'price' => __('Price', $this->domain),
			'whose' => __('Whose Cost', $this->domain),
			'type' => __('Field Type', $this->domain),
			'note' => __('Note', $this->domain),
		);
	}

	/**
	 * ソートカラム情報
	 *
	 */
	public function get_sortable_columns() {
		return array(
			'optionid' => array('optionid', true),
		);
	}

	/**
	 * カラムデータのデフォルト表示
	 *
	 */
	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'optionid' :
				if (999 < $item[$column_name]) {
					$group = $item[$column_name] / 1000;
					$order = $item[$column_name] % 1000;
				} else {
					$group = '';
					$serial = $item[$column_name];
				}
				return sprintf("%s %03d", $group, $serial);
				break;
			case 'required' :
			case 'keyname' :
			case 'name' :
			case 'price' :
			case 'note' :
				return esc_html($item[$column_name]);
			case 'type' :
				return isset($this->field_type[$item[$column_name]]) ? $this->field_type[$item[$column_name]] : esc_html($item[$column_name]);
			case 'whose' :
				return isset($this->whose_cost[$item[$column_name]]) ? $this->whose_cost[$item[$column_name]] : esc_html($item[$column_name]);
			default :
				return print_r($item, true);
		}
	}


	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'optionid', //$this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['optionid']                //The value of the checkbox should be the record's id
		);
	}

	/**
	 * カラムデータ name とアクションリンク表示
	 *
	 */
	public function column_optionid($item) {
	
		$nonce = wp_create_nonce($this->DAO->nonce_word);

		$edit_url = add_query_arg(
			array('action' => 'edit', 'optionid' => $item['optionid']),
			$_SERVER['REQUEST_URI']
		);

		$delete_url = add_query_arg(
			array('action' => 'delete', 'optionid' => $item['optionid'], 'nonce' => $nonce),
			$_SERVER['REQUEST_URI']
		);

		//$paramstr = sprintf('page=%s&amp;object=%s&amp;group[name]=%s&amp;optionid=%d',
		//	self::PAGE_NAME, $this->DAO->object, $this->DAO->group_name, $item['optionid']);

		// アクション
		$actions = array(
			'edit' => sprintf('<a href="%s">%s</a>', $edit_url, __('Edit')),
			'delete' => sprintf('<a href="%s">%s</a>', $delete_url, __('Delete')),
		);

		return $item['optionid'] . $this->row_actions($actions);
	}


	/**
	 * 一括操作のセレクト
	 *
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __('Delete'),
		);
		return $actions;
	}


	/**
	 * リスト表示準備
	 *
	 */
	public function prepare_items() {

		// カラムヘッダープロパティの設定
		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());


		// カレントページの取得
		$current_page = $this->get_pagenum() - 1;

		// オプション選択肢の登録数の取得
		$total_items = $this->DAO->count_option();

		// オプションの取得
		$data = $this->DAO->option_list($current_page * $this->per_page, $this->per_page);
		$this->items = $data;

		// ページネーション設定
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page' => $this->per_page,
			'total_pages' => ceil($total_items / $this->per_page),
		));

	}

}