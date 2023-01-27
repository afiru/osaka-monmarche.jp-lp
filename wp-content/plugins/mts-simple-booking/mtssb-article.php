<?php
/**
 * MTS Simple Booking Articles module 予約品目モジュール
 *
 * @Filename	mtssb-article.php
 * @Date		2012-04-19
 * @Author		S.Hayashi
 *
 * Updated to 1.31.0 on 2019-05-18
 * Updated to 1.29.0 on 2019-01-08
 * Updated to 1.24.0 on 2016-07-26
 * Updated to 1.21.0 on 2014-12-26
 * Updated to 1.19.0 on 2014-12-05
 * Updated to 1.15.0 on 2014-04-10
 * Updated to 1.14.0 on 2014-01-15
 * Updated to 1.9.5 on 2013-09-04
 * Updated to 1.9.0 on 2013-07-17
 * Updated to 1.8.0 on 2013-05-28
 * Updated to 1.7.0 on 2013-05-11
 * Updated to 1.6.0 on 2013-03-20
 * Updated to 1.5.0 on 2013-03-11
 * Updated to 1.3.0 on 2012-12-27
 */

class MTSSB_Article
{
	const POST_TYPE = 'mtssb_article';
	const PST_VERSION = '1.2';

	/**
	 * Protected valiable
	 */
	protected $domain;			// mts_simple_booking

	/**
	 * Constructor
	 *
	 */
	public function __construct() {

		$this->domain = MTS_Simple_Booking::DOMAIN;

		// Register Custom Post Type
		if (!post_type_exists(self::POST_TYPE)) {
			$this->_register_post_type();
		}
	}

	/**
	 * Register Custom Post Type
	 *
	 */
	protected function _register_post_type() {
		$labels = array(
			'name' => __('Booking Articles', $this->domain),
			'singular_name' => __('Booking Article', $this->domain),
			'add_new' => __('New Booking Article', $this->domain),
			'add_new_item' => __('Add New Booking Article', $this->domain),
			'edit_item' => __('Edit Booking Article', $this->domain),
		);

		$args = array(
			'label' => 'Articles',
			'labels' => $labels,
			'public' => true,
			//'publicly_queryable' => false,
			'rewrite' => array('slug' => 'article'),
			'hierarchical' => false,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
			//'taxonomies' => array('category', 'post_tag'),
			'register_meta_box_cb' => array($this, 'register_meta_box'),
		);
		register_post_type(self::POST_TYPE, $args);

		// Do flush_rewrite_rules once when the plugin is activated.
		if (get_option($this->domain . '_activation')) {
			flush_rewrite_rules(false);
			delete_option($this->domain . '_activation');
		}
	}

	/**
	 * Meta Box for admin edit page
	 *	Override function for admin class
	 *
	 */
	public function register_meta_box() {
	}

	/**
	 * 予約品目初期データ
	 */
	static public function get_new_article($data=false) {
		$article = array(
			'article_id' => 0,          // 予約品目ID
			'name' => '',               // 予約品目名
			'timetable' => array(),		// 予約時間割
			'restriction' => 'capacity',// 予約制限(capacity or quantity)
			'capacity' => 0,			// 収容定員数
			'quantity' => 0,			// 予約最大件数
			'minimum' => 0,				// 予約受付最小人数
			'maximum' => 0,				// 予約受付最大人数
			'count' => array(			// 人数カウントレート(子供は半分0.5など可能)
				'adult' => 1,
				'child' => 0,
				'baby' => 0,
			),
			'price' => new MTS_Value,	// 料金設定
			'addition' => new MTSSB_Article_Addition,
		);

		if ($data) {
			$article = array_merge($article, $data);
		}

		return $article;
	}

	/**
	 * 予約品目の時間割取得
	 *
	 */
	static public function get_the_timetable($article_id=0) {
		global $wpdb;

		$data = $wpdb->get_row($wpdb->prepare("
			SELECT ID AS article_id,m1.meta_value AS timetable
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta AS m1 ON m1.post_id=ID AND m1.meta_key='timetable'
			WHERE ID=%d", intval($article_id)), ARRAY_A);

		if (empty($data['timetable'])) {
			return array();
		}

		return unserialize($data['timetable']);
	}

	/**
	 * 予約品目の取得
	 *
     * @article_id
     * @publish     true or false
	 */
	static public function get_the_article($article_id=0, $publish='')
	{
		global $wpdb;

        // 検索条件
        $condition = '';
        if ($publish) {
            $status = explode(',', $publish);
            $condition = " AND post_status in ('" . implode("','", $status) . "')";
        }

		$sql = $wpdb->prepare("
			SELECT ID AS article_id,post_title AS name,post_type,post_status
			FROM {$wpdb->posts}
			WHERE ID=%d AND post_type=%s" . $condition, intval($article_id), self::POST_TYPE);

		$article = $wpdb->get_row($sql, ARRAY_A);

        if ($article) {
            $field = self::get_all_fields($article['article_id']);
            return array_merge($article, $field);
        }

		return array();
	}

	/**
	 * 全予約品目の取得
	 *
	 * @ids		0:number 1:ID
	 */
	static public function get_all_articles($ids='0')
	{
		global $wpdb;

        // 時間割・カウントフラグデータのアンシリアライズとIDのインデックス化
        $articleids = array();

		// 予約品目IDを指定しない場合
		if (empty($ids)) {
            $sql = $wpdb->prepare("
                SELECT ID AS article_id,post_title AS name,post_name AS slug,post_type,post_status
                FROM {$wpdb->posts}
                WHERE post_type=%s AND post_status in ('publish', 'private')
                ORDER BY menu_order ASC, ID DESC", self::POST_TYPE);

            $articles = $wpdb->get_results($sql, ARRAY_A);

            foreach ($articles as $article) {
                $field = self::get_all_fields($article['article_id']);
                $articleids[$article['article_id']] = array_merge($article, $field);
            }

            return $articleids;
        }

        // 予約品目IDが指定された場合
        $aids = explode(',', $ids);

        foreach ($aids as $article_id) {
            $article = self::get_the_article($article_id, 'publish');
            if ($article) {
                $articleids[$article_id] = $article;
            }
        }

		return $articleids;
	}

    /**
     * 予約品目の設定値(postmeta)を取得して戻す
     *
     */
    static protected function get_all_fields($aid=0)
    {
        global $wpdb;

        // 全てのカスタムフィールドを取得する
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key in (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
            $aid, 'timetable', 'restriction', 'capacity', 'quantity', 'minimum', 'maximum', 'count', 'price', 'addition');
        $fields = $wpdb->get_results($sql, ARRAY_A);

        // データ初期値
        $data = self::get_new_article();
        unset($data['article_id']);
        unset($data['name']);

        // 読み込んだデータのアンシリアライズ
        foreach ($fields as $field) {
            $column = $field['meta_key'];
            switch ($column) {
                case 'timetable':
                case 'count':
                case 'price':
                    $data[$column] = unserialize($field['meta_value']);
                    break;
                case 'addition':
                    if (is_numeric($field['meta_value'])) {
                        $data[$column]->option = $field['meta_value'];
                    } else {
                        $data[$column] = unserialize($field['meta_value']);
                        $data[$column]->tracking = stripslashes($data[$column]->tracking);
                    }
                    break;
                default:
                    $data[$column] = $field['meta_value'];
                    break;
            }
        }

        // １日の合計受入枠を計算する
        $number = count($data['timetable']);
        $data['total_capacity'] = $data['capacity'] * $number;
        $data['total_quantity'] = $data['quantity'] * $number;

        return $data;
    }

}

/**
 * オプション設定オブジェクト
 * Ver.1.9.5 on 2013-09-04
 *
 * Updated to 1.20.0 on 2014-12-26
 * Updated to 1.14.0 on 2014-01-15
 */
class MTSSB_Article_Addition
{
	private static $_properties = array(
		'option',
		'option_name',
		'position',
		'tracking',
		'cancel_limit',
		'booking_mail',
		'template',
		'awaking_time',
		'awaking_hour',
		'awaking_minute',
		'awaking_mail',
		'check_name',
		'check_email',
		'check_tel',
	);

	private $option = 0;
	private $option_name = '';
	private $cancel_limit = 0;
	private $position = 0;
	private $booking_mail = array();
    private $template = '';
	private $tracking = '';
	private $awaking_time = 0;
	private $awaking_hour = 0;
	private $awaking_minute = 0;
	private $awaking_mail = '';
	private $check_name = 0;
	private $check_email = 0;
	private $check_tel = 0;

	/**
	 * オプション設定の有無を戻す
	 *
	 */
	public function isOption() {
		if ($this->option == 1) {
			return true;
		}

		return false;
	}

	/**
	 * プロパティに代入
	 *
	 */
	public function __set($key, $value)
	{
		if (in_array($key, self::$_properties)) {
			if (is_int($this->$key)) {
				$this->$key = intval($value);
			} else if (is_float($this->$key)) {
				$this->$key = floatval($value);
			} else {
				$this->$key = $value;
			}
		} else {
			throw new Exception("Error:Set undefined propertie Sales->{$key}.");
		}
	}

	public function __get($key)
	{
		if (in_array($key, self::$_properties)) {
			return $this->$key;
		}
		return false;
	}

	public function __isset($key) {
		if (in_array($key, self::$_properties)) {
			return $this->$key;
		}

		return false;
	}
}


/**
 * 料金データオブジェクト
 *
 */
class MTS_Value {
	const VERSION = "1.7.0";

	private $adult = 0;		// 大人料金
	private $child = 0;		// 小人料金
	private $baby = 0;		// 幼児料金
	private $booking = 0;	// 予約料金

	/**
	 * コンストラクタ
	 *
	 */
	public function __construct()
	{
	}

	/**
	 * プロパティに代入
	 *
	 */
	public function __set($key, $value)
	{
		if (isset($this->$key)) {
			$this->$key = $value;
		} else {
			throw new Exception("Error:Set undefined property {$key}.");
		}
	}

	public function __get($key)
	{
		if (isset($this->$key)) {
			return $this->$key;
		}

		return false;
	}
}
