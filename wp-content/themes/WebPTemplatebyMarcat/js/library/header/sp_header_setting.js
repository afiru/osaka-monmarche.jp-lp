if(typeof jQuery != "undefined"){
    $(function(){
        $('.sp_menu_button').on('click',function(){
            $('.header_sp_menu_nav').slideToggle();
            if($(this).hasClass('off')){
                $(this).removeClass('off').addClass('on');
            }else {
                $(this).removeClass('on').addClass('off');
            }
        });
    });
}