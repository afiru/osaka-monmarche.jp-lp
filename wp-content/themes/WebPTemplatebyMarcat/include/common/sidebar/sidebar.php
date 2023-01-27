
<!--ウィジェットの呼び込み(ここから）-->
<?php $read_widget_name = 'ウィジェット名';//要変更 ?>
<?php if ( is_active_sidebar( $read_widget_name ) ): dynamic_sidebar($read_widget_name);  endif; ?>
<!--ウィジェットの呼び込み(ここまで）-->