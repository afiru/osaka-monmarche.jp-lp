<?php
/**
 * MTS Simple Booking カレンダー表示処理クラスモジュール
 *
 * @Filename    MtssbCalendarView.php
 * @Author      S.Hayashi
 * @Date        2014-11-05 at Ver.1.19.0
 *
 * Updated to 1.32.0 on 2019-11-25
 * Updated to 1.30.0 on 2019-02-07
 * Updated to 1.29.0 on 2018-08-08
 * Updated to 1.28.0 on 2017-10-26
 */
class MtssbCalendarView
{
    // 月リンク出力データキャッシュ
    private $pagination = '';

    // 予約フォームリンク
    private $formUrl = '';

    public function __construct()
    {
        // 予約フォームページのURL
        $this->formUrl = get_permalink(get_page_by_path(MTS_Simple_Booking::PAGE_BOOKING_FORM));
    }

    /**
     * カレンダータイトル表示
     *
     */
    public function calendarTitle($params)
    {
        // Widgetかタイトルがなければ何も出力しない
        if ($params['widget'] || empty($params['title'])) {
            return '';
        }

        // カレンダータイトル
        return apply_filters('mtssb_calendar_title',
            sprintf('<h3 class="calendar-title">%s</h3>', $params['title']), $params['calendar_id']);
    }

    /**
     * カレンダーのキャプション表示
     */
    public function captionPagination($params, MtssbCalendar $calendar)
    {
        // キャプション非表示(キャプション、月リンク共に非表示)
        if ($params['caption'] != '1' && $params['pagination'] < 2) {
            return '';
        }

        // キャプション
        $capstr = '';
        if ($params['caption'] == '1') {
            $capstr = apply_filters('mtssb_caption',
                date_i18n(__('F, Y'), $calendar->calendarTime),
                array('month' => $calendar->calendarTime, 'cid' => $params['calendar_id']));
        }

        // 翌月のページリンク
        $pagstr = '';
        if ($params['pagination'] == 2 || $params['pagination'] == 3) {
            $pagstr = $this->pagination($params, $calendar);
        }

        // キャプション(ページリンク)表示
        return sprintf('<caption class="calendar-caption">%s%s</caption>', $capstr, $pagstr) . "\n";
    }

    /**
     * 予約カレンダーのページリンク表示
     */
    public function pagination($params, MtssbCalendar $calendar)
    {
        // リンク
        
        $cid = $params['calendar_id'];
        $prev_title = esc_html(
            apply_filters('mtssb_prev_pagination', date_i18n(__('F, Y'), $calendar->prevTime), array('prev' => $calendar->prevTime, 'cid' => $cid),$params['id'])
        );
        $prev_arg = array('ym' => date_i18n('Y-n', $calendar->prevTime)) + (empty($cid) ? array() : array('cid' => $cid));

        $next_title = esc_html(
            apply_filters('mtssb_next_pagination', date_i18n(__('F, Y'), $calendar->nextTime), array('next' => $calendar->nextTime, 'cid' => $cid),$params['id'])
        );
        $next_arg = array('ym' => date_i18n('Y-n', $calendar->nextTime)) + (empty($cid) ? array() : array('cid' => $cid));

        $anchor = $params['anchor'] ? "#{$params['anchor']}" : '';

        ob_start();
?>
    <div class="monthly-prev-next">
            <div class="monthly-prev"><?php if ($calendar->monthTime <= $calendar->prevTime) {
                    echo sprintf('<a href="%s%s">%s</a>', htmlspecialchars(add_query_arg($prev_arg)), $anchor, $prev_title);
                } else {
                    echo sprintf('<span class="no-link">%s</span>', $prev_title);
                } ?></div>
            <div class="monthly-next"><?php if ($calendar->nextTime < $calendar->openTime) {
                    echo sprintf('<a href="%s%s">%s</a>', htmlspecialchars(add_query_arg($next_arg)), $anchor, $next_title);
                } else {
                    echo sprintf('<span class="no-link">%s</span>', $next_title);
                } ?></div>
            <br style="clear:both" />
        </div>

<?php
        return ob_get_clean();
    }

    /**
     * 月カレンダーの曜日ヘッダー表示
     *
     * @calTime     カレンダー先頭のUnix Time
     * @paramWeek   ショートコード曜日列引数
     */
    public function weekHeader($calTime, $paramWeek)
    {
        $weeks = explode(',', $paramWeek);

        for ($i = 0; $i < 7; $i++, $calTime += 86400) {
            $weekClass[$i] = strtolower(date('D', $calTime));
            $weekNo = date_i18n('w', $calTime);
            $weekNames[$i] = !empty($weeks[$weekNo]) ? $weeks[$weekNo] : date_i18n('D', $calTime);
        }

        ob_start();
?>
        <thead>
        <tr class="header-row"><?php for ($i = 0; $i < 7; $i++) {
                echo sprintf('<th class="week-title %s">%s</th>', $weekClass[$i], $weekNames[$i]);
            } ?>
        </tr>
        </thead>

<?php
        return ob_get_clean();
    }

    /**
     * 指定日予約時間割カレンダー表示
     *
     * $daytime		unix time
     * $params		ショートコードパラメータ
     */
    public function timetableLink($dayTime, $params, $article, $timetableInfo)
    {
        $aParam = array('aid' => $article['article_id'], 'cid' => $params['calendar_id']);

        ob_start();
?>
        <table class="mtssb-timetable-link">
            <caption><?php echo $this->timetableCaption($dayTime, $article['name'], $aParam) ?></caption>
            <tr>
                <th class="day-left"><?php
                    echo apply_filters('mtssb_daily_time_title', __('Time', MTS_Simple_Booking::DOMAIN), $aParam);
                ?></th>
                <th class="day-right"><?php
                    echo apply_filters('mtssb_daily_booking_title', __('Booking', MTS_Simple_Booking::DOMAIN), $aParam);
                ?></th>
            </tr>
            <?php foreach ($timetableInfo as $theTime => $timeInfo) :
                $marking = $timeInfo->marking ? $timeInfo->marking : $timeInfo->remain;
                $param = $aParam + array('mark' => $timeInfo->mark, 'remain' => $timeInfo->remain);
                $linktext = apply_filters('mtssb_daily_mark', $marking, $param);
                if ($timeInfo->link && $timeInfo->mark != 'disable' && $timeInfo->mark != 'full' && $params['link']) {
                    $linktext = sprintf('<a class="booking-timelink" href="%s">%s</a>',
                        esc_url(add_query_arg(array('aid' => $article['article_id'], 'utm' => $dayTime + $theTime), $this->formUrl)),
                        $linktext);

                }
                $linktext .= $timeInfo->names ? " ({$timeInfo->names})" : '';
            ?><tr>
                <th class="day-left"><?php
                    $param = $aParam + array('time' => $theTime);
                    echo apply_filters('mtssb_time_header', date('H:i', $theTime), $param) ?></th>
                <td class="day-right"><div class="calendar-mark <?php echo $timeInfo->mark ?>"><?php echo $linktext ?></div></td>
            </tr><?php endforeach; ?>
        </table>

<?php
        return ob_get_clean();
    }

    /**
     * 予約カレンダーのタイトル表示
     *
     */
    public function timetableCaption($dayTime, $name, $aParam)
    {
        $title = apply_filters('mtssb_timetable_name', $name, $aParam);
        $aParam += array('day' => $dayTime);

        $date = apply_filters('mtssb_timetable_date', date_i18n('Y年n月j日 (D)', $dayTime), $aParam);

        return sprintf('<div class="mtssb-timetable-name">%s</div><div class="mtssb-timetable-date">%s</div>', $title, $date);
    }

    /**
     * 指定日予約時間割セレクトボックス表示
     *
     * $daytime		unix time
     * $params		ショートコードパラメータ
     */
    public function timetableSelect($dayTime, $params, $article, $timetableInfo)
    {
        $aParam = array('aid' => $article['article_id'], 'cid' => $params['calendar_id']);

        ob_start();
?>
        <form method="post" action="<?php echo $this->formUrl ?>" class="mtssb-timetable-form">
            <?php echo $this->timetableCaption($dayTime, $article['name'], $aParam); ?>

            <fieldset class="select-timetable">
                <select class="timetable-select" name="utm">
                    <?php foreach ($timetableInfo as $theTime => $timeInfo) {
                        echo $this->_selectOption($dayTime + $theTime, $timeInfo, $aParam);
                    } ?>
                </select>
                <button type="submit" class="timetable-submit">
                    <?php echo apply_filters('mtssb_daily_timetable_submit', '予約フォーム', $aParam) ?>
                </button>
            </fieldset>
            <input type="hidden" name="aid" value="<?php echo $article['article_id'] ?>" />
        </form>

<?php
        return ob_get_clean();
    }

    private function _selectOption($bookingTime, $timeInfo, $aParam)
    {
        // 表示マーキング(マーキング　または　残数)
        $marking = $timeInfo->marking ? $timeInfo->marking : $timeInfo->remain;

        // 予約者名
        $names = $timeInfo->names ? sprintf(' (%s)', $timeInfo->names) : '';

        // オプション表示文字列
        $param = $aParam + array('time' => $bookingTime, 'mark' => $timeInfo->mark, 'remain' => $timeInfo->remain);

        $string = sprintf('%s %s%s', date_i18n('H時i分', $bookingTime), $marking, $names);
        $string = apply_filters('mtssb_timetable_select_option', $string, $param);

        // 選択可否
        $disabled = ($timeInfo->mark == 'disable' || $timeInfo->mark == 'full') ? ' disabled="disabled"' : '';

        return sprintf('<option value="%s"%s>%s</option>', $bookingTime, $disabled, $string);
    }

}