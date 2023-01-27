    <nav class="MarcatGiaSettingGnavi">
        <ul>
            <?php $page = filter_input(INPUT_GET, "page"); ?>
            <li <?php if($page==="MarcatGiaSettingTop"): ?>class="cheackd"<?php endif;?>>
                <a href="<?php echo home_url('/wp-admin/admin.php'); ?>?page=MarcatGiaSettingTop">MarcatGiaトップ</a>
            </li>
            <li <?php if($page==="MarcatGiaMenuExpansion"): ?>class="cheackd"<?php endif;?>>
                <a href="<?php echo home_url('/wp-admin/admin.php'); ?>?page=MarcatGiaMenuExpansion">メニューの拡張設定</a>
            </li>
            <li <?php if($page==="MarcatGiaWidgetExpansion"): ?>class="cheackd"<?php endif;?>>
                <a href="<?php echo home_url('/wp-admin/admin.php'); ?>?page=MarcatGiaWidgetExpansion">ウィジェットの拡張設定</a>
            </li>
            <li <?php if($page==="MarcatGiaCustomizerExpansion"): ?>class="cheackd"<?php endif;?>>
                <a href="<?php echo home_url('/wp-admin/admin.php'); ?>?page=MarcatGiaCustomizerExpansion">カスタマイザーの拡張設定</a>
            </li>
            <li <?php if($page==="MarcatGiaCategoryExpansion"): ?>class="cheackd"<?php endif;?>>
                <a href="<?php echo home_url('/wp-admin/admin.php'); ?>?page=MarcatGiaCategoryExpansion">カスタマイザーの拡張設定</a>
            </li>
        </ul>
    </nav>