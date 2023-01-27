<?php
function load_media_files() {
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'load_media_files' );

add_action( 'admin_print_footer_scripts', 'custom_add_images_plugin' );
function custom_add_images_plugin($id =0) { ?>
    <script type="text/javascript">
    (function ($) { 
        var custom_uploader; 
        $("[id=picker_img]").click(function(e) {
            var thisclass = '#'+ $(this).data('input_id');
            var thumbs_class = '.'+ $(this).data('thumbs_class');
            console.log(thumbs_class);
            $(thumbs_class).empty();
            e.preventDefault(); 
            if (custom_uploader) { 
                custom_uploader.open();
                return; 
            }
            custom_uploader = wp.media({ 
                title: "Choose Image", 
                /* ライブラリの一覧は画像のみにする */
                library: {
                    type: "image"
                }, 
                button: {
                    text: "Choose Image"
                }, 
                /* 画像のURLを調査するできる画像は 1 つだけにする */
                multiple: false 
            }); 
            custom_uploader.on("select", function() { 
                var images = custom_uploader.state().get("selection"); 
                /* file の中に画像のURLを調査するされた画像の各種情報が入っている */
                images.each(function(file){ 
                    /* テキストフォームと表示されたサムネイル画像があればクリア */
                    $(thisclass).val("");
                    $("#media").empty(); 
                    /* テキストフォームに画像の ID を表示 */
                    $(thisclass).val(file.toJSON().url); 
                    $(thumbs_class).append('<img src="'+ file.toJSON().url +'" style="max-width:100%;height: auto; ">');
                    /* プレビュー用に画像のURLを調査するされたサムネイル画像を表示 */
                    $("#media").append('<img src="'+file.toJSON().url+'" />'); 
                });
            }); 
            custom_uploader.open(); 
        }); 
        /* クリアボタンを押した時の処理 */
        $(".media-clear").click(function() {     
            $(thisclass).val("");
            $("#media").empty(); 
        }); 
    })(jQuery);

    (function ($) { 
        var custom_uploader; 
        $(".img_search_widget_02").click(function(e) { 
            var thisclass = '.'+ $(this).data('inputid');
            var thumbs_class = '.'+ $(this).data('thumbs_class');
            console.log(thumbs_class);
            $(thumbs_class).empty();
            e.preventDefault(); 
            if (custom_uploader) { 
                custom_uploader.open();
                return; 
            }

            custom_uploader = wp.media({ 
                title: "Choose Image", 
                /* ライブラリの一覧は画像のみにする */
                library: {
                    type: "image"
                }, 
                button: {
                    text: "Choose Image"
                }, 
                /* 画像のURLを調査するできる画像は 1 つだけにする */
                multiple: false 
            }); 
            custom_uploader.on("select", function() { 
                var images = custom_uploader.state().get("selection"); 
                /* file の中に画像のURLを調査するされた画像の各種情報が入っている */
                images.each(function(file){ 
                    /* テキストフォームと表示されたサムネイル画像があればクリア */
                    $(thisclass).val("");
                    $("#media").empty(); 
                    /* テキストフォームに画像の ID を表示 */
                    $(thisclass).val(file.toJSON().url); 
                    $(thumbs_class).append('<img src="'+ file.toJSON().url +'" style="max-width:100%;height: auto; ">');
                    /* プレビュー用に画像のURLを調査するされたサムネイル画像を表示 */
                    $("#media").append('<img src="'+file.toJSON().url+'" />'); 
                });
            }); 
            custom_uploader.open(); 
        }); 
        /* クリアボタンを押した時の処理 */
        $(".media-clear").click(function() {     
            $(thisclass).val("");
            $("#media").empty(); 
        }); 
    })(jQuery);
    </script>
<?php } ?>