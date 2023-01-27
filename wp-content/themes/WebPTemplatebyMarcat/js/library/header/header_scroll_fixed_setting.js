jQuery(document).ready(function($){
    $('#scroll_off #pc_main_header').clone().appendTo('#scroll_on');
    $('#scroll_off #sp_main_header').clone().appendTo('#scroll_on');
    $(window).scroll(function() {
        if( $(this).scrollTop() >= $("#scroll_off").height()) {
            $("#scroll_on").fadeIn(500);
            $("#scroll_off").fadeOut(500);
        }else {
            $("#scroll_off").fadeIn(500);
            $("#scroll_on").fadeOut(500); 
        }
    });
});