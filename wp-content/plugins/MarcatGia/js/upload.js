(function($) {
    $(function() {
        $('#upload_image_button').click(function() {
            formfield =$('#upload_image').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image2_button').click(function() {
            formfield =$('#upload_image2').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image3_button').click(function() {
            formfield =$('#upload_image2').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        
        $('#upload_image_01_button').click(function() {
            formfield =$('#upload_image_01').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_02_button').click(function() {
            formfield =$('#upload_image_02').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });

        $('#upload_image_03_button').click(function() {
            formfield =$('#upload_image_03').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_04_button').click(function() {
            formfield =$('#upload_image_04').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_05_button').click(function() {
            formfield =$('#upload_image_05').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_06_button').click(function() {
            formfield =$('#upload_image_06').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_07_button').click(function() {
            formfield =$('#upload_image_07').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_08_button').click(function() {
            formfield =$('#upload_image_08').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_09_button').click(function() {
            formfield =$('#upload_image_09').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        $('#upload_image_10_button').click(function() {
            formfield =$('#upload_image_10').attr('name');
            tb_show(null, 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
            return false;
        });
        
        window.send_to_editor = function(html) {
            imgurl = $('img',html).attr('src');
            console.log(imgurl);
            $('#upload_image').val(imgurl);
            tb_remove();
        }
        window.send_to_editor = function(html) {
            imgurl2 = $('img',html).attr('src');
            $('#upload_image2').val(imgurl2);
            tb_remove();
        }
        window.send_to_editor = function(html) {
            imgurl3 = $('img',html).attr('src');
            $('#upload_image3').val(imgurl3);
            tb_remove();
        }
        
        window.send_to_editor = function(html) {
            imgurl_01 = $('img',html).attr('src');
            $('#upload_image_01').val(imgurl_01);
            tb_remove();
        }
        window.send_to_editor = function(html) {
            imgurl_02 = $('img',html).attr('src');
            $('#upload_image_02').val(imgurl_02);
            tb_remove();
        }
        
        
        
        window.send_to_editor = function(html) {
            imgurl_03 = $('img',html).attr('src');
            $('#upload_image_03').val(imgurl_03);
            tb_remove();
        }
        window.send_to_editor = function(html) {
            imgurl_04 = $('img',html).attr('src');
            $('#upload_image_04').val(imgurl_04);
            tb_remove();
        }
        
    });
})(jQuery);