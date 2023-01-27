<?php
if (!class_exists('MTSSB_Booking')) {
	require_once(dirname(__FILE__) . '/mtssb-booking.php');
}
/**
 * MTS Simple Booking ユーザーズページ
 *
 * @Filename	mtssb-users-page.php
 * @Date		2014-10-28
 * @Implemented Ver.1.19.0
 * @Author		S.Hayashi
 *
 * Update to 1.23.1 on 2016-05-16
 */
class MTSSB_Users_Page extends MTSSB_Booking
{
	const PAGE_NAME = MTS_Simple_Booking::PAGE_USERS;

    // 予約履歴ページ表示パラメータ
    private $pageParam = null;

    /**
     * wp_content
     *
     */
    public function content($content)
    {
        // ログインの確認
        $current_user = wp_get_current_user();

        if ($current_user->ID <= 0) {
            return $this->_out_message_box('UNAVAILABLE');
        }

        $content = $this->_out_booking_list($current_user->ID) . $content;

        return $content;
    }

	/**
	 * エラーメッセージ
	 *
	 */
	protected function _err_message($err_name) {
		switch ($err_name) {
            case 'UNAVAILABLE':
                return 'このページは利用できません。';
                break;
        }

		return $err_name;
	}

	/**
	 * エラーエレメントの出力
	 *
	 */
	protected function _out_message_box($errCode)
    {
		ob_start();
?>
        <div class="error-message error-box">
            <?php echo $this->_err_message($errCode) ?>
        </div>
<?php
		return ob_get_clean();
	}

    /**
     * 表示ページパラメータの初期化
     *
     */
    private function _initPageParam($userId)
    {
        $pageParam = $this->_getPageParam();

        // 予約総数を取得する
        $pageParam->total = $this->get_booking_users_count($userId);

        // 最終ページ
        $pageParam->last = (int) ($pageParam->total / $pageParam->perCount);

        // 表示ページ
        $pg = isset($_GET['pg']) ? (int) $_GET['pg'] : 0;
        if (0 <= $pg && $pg <= $pageParam->last) {
            $pageParam->page = $pg;
        }

        // 前ページ
        if (0 < $pageParam->page) {
            $pageParam->prev = $pageParam->page - 1;
        }

        // 次ページ
        if ($pageParam->page < $pageParam->last) {
            $pageParam->next = $pageParam->page + 1;
        }

        $this->pageParam = $pageParam;
    }

    /**
     * 表示ページパラメータ
     *
     */
    private function _getPageParam()
    {
        return (object) array(
            'perCount' => 10,   // ページ表示予約件数
            'total' => 0,       // 予約総数
            'last' => 0,        // 最終ページ番号
            'page' => 0,        // 表示ページ番号
            'prev' => -1,       // 前ページ番号
            'next' => -1,       // 次ページ番号
        );
    }


    /**
	 * 予約確認入力フォーム生成
	 *
	 */
	private function _out_booking_list($userId)
	{
        // 表示ページのパラメータを設定する
        $this->_initPageParam($userId);

        // 予約データの取得
        $offset = $this->pageParam->page * $this->pageParam->perCount;
        $bookings = $this->get_users_booking($userId, $offset, $this->pageParam->perCount);

        // 詳細表示(subscription)ページのURL
		$url = get_permalink(get_page_by_path(MTS_Simple_Booking::PAGE_SUBSCRIPTION));
        $pageUrl = add_query_arg(array('id' => '%id%', 'action' => 'show'), $url);

		ob_start();
?>
    <?php echo apply_filters('mtssb-users-title-history', '<h2 class="mtssb-users-history">予約の履歴</h2>') ?>

    <div class="mtssb-users">
        <div class="mtssb-pagination">
            <span class="pagination-nav"><?php echo sprintf('%s | %s',
                    $this->_pageLink($this->pageParam->prev, '最近'), $this->_pageLink($this->pageParam->next, '過去')) ?></span>
            <span class="pagination-current"><?php echo sprintf(' 予約 %d / %d ページ',
                    $this->pageParam->page + 1, $this->pageParam->last + 1) ?></span>
        </div>
	<table>
		<tr>
			<th class="users-date">予約日</th>
            <th class="users-time">予約時間</th>
            <th class="users-article">予約</th>
            <th class="users-apply">申込日時</th>
            <th class="users-action">処理</th>
		</tr>
        <?php foreach ($bookings as $booking_id => $booking) : ?><tr>
            <td><?php echo date_i18n('Y年n月j日', $booking['booking_time']) ?></td>
            <td><?php echo date_i18n('H:i', $booking['booking_time']);
                if (!empty($booking['series'])) {
                    $this->_outBookingSeriesTime($booking['series']);
                }?></td>
            <td><?php echo esc_html($booking['article_name']) ?></td>
            <td><?php echo substr($booking['created'], 0, 16) ?></td>
            <td><a href="<?php echo str_replace('%id%', $booking_id, $pageUrl) ?>" title="予約詳細">詳細</a></td>
        </tr><?php endforeach; ?>
	</table>
    </div>

<?php
        return ob_get_clean();
	}

    // 連続予約時間帯の出力
    private function _outBookingSeriesTime($seriesBooking)
    {
        foreach ($seriesBooking as $series) {
            echo sprintf('<br>%s', date_i18n('H:i', $series['booking_time']));
        }
    }

    // 予約ページリンクの出力
    private function _pageLink($page, $title)
    {
        if (0 <= $page) {
            $pageUrl = get_permalink();
            $link = sprintf('<a href="%s">%s</a>', add_query_arg(array('pg' => $page), $pageUrl), $title);
        } else {
            $link = sprintf('<sapn class="link-disabled">%s</sapn>', $title);
        }

        return $link;
    }

}