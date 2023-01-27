<?php
/**
 * MTS Simple Booking フォーム出力共通処理
 *
 * @Filename    MtssbFormCommon.php
 * @Date		2014-12-09
 * @Implemented Ver.1.20.0
 * @Author		S.Hayashi
 *
 * Updated to 1.21.0 on 2014-12-27
 */
class MtssbFormCommon
{
    // 都道府県入力セレクトボックス
    public function selectPref($id, $class, $name, $val)
    {
        static $prefs = array(' ', '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県',
            '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
            '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県',
            '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県');

        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name) . "\n";

        foreach ($prefs as $pref) {
            echo sprintf('<option value="%s"%s>%s</option>',
                    $pref, $this->setSelected($pref, $val), $pref) . "\n";
        }

        echo "</select>\n";
        return ob_get_clean();
    }

    // 年セレクトボックス
    public function selectYear($id, $class, $name, $val, $startYear, $lastYear)
    {
        $increment = $startYear < $lastYear ? 1 : -1;


        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name);
        echo '<option value="">----</option>';
        for ($i = $startYear; $i != $lastYear; $i += $increment) {
            echo sprintf('<option value="%d"%s>%d</option>', $i, $this->setSelected($val, $i), $i);
            if (1000 <= abs($i - $lastYear)) {
                break;
            }
        }
        echo "</select>\n";
        return ob_get_clean();
    }

    // 月セレクトボックス
    public function selectMonth($id, $class, $name, $val)
    {
        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name);
        echo '<option value="">--</option>';
        for ($i = 1; $i <= 12; $i++) {
            echo sprintf('<option value="%d"%s>%d</option>', $i, $this->setSelected($val, $i), $i);
        }
        echo "</select>\n";
        return ob_get_clean();
    }

    // 日セレクトボックス
    public function selectDay($id, $class, $name, $val)
    {
        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name);
        echo '<option value="">--</option>';
        for ($i = 1; $i <= 31; $i++) {
            echo sprintf('<option value="%d"%s>%d</option>', $i, $this->setSelected($val, $i), $i);
        }
        echo "</select>\n";
        return ob_get_clean();
    }

    // 時セレクト
    public function selectHour($id, $class, $name, $val)
    {
        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name);
        for ($hour = 0; $hour <= 23; $hour++) {
            echo sprintf('<option value="%s"%s>%02d</option>', $hour, $this->setSelected($val, $hour), $hour);
        }
        echo "</select>\n";
        return ob_get_clean();
    }

    // 分セレクト
    public function selectMinute($id, $class, $name, $val)
    {
        ob_start();
        echo sprintf('<select id="%s" class="%s" name="%s">', $id, $class, $name);
        for ($minute = 0; $minute <= 59; $minute++) {
            echo sprintf('<option value="%s"%s>%02d</option>', $minute, $this->setSelected($val, $minute), $minute);
        }
        echo "</select>\n";
        return ob_get_clean();
    }

    // オプション選択の設定
    public function setSelected($val1, $val2)
    {
        return $val1 == $val2 ? ' selected="selected"' : '';
    }

    // ラジオボタン・チェックボックスの設定
    public function setChecked($val1, $val2)
    {
        return $val1 == $val2 ? ' checked="checked"' : '';
    }

}
