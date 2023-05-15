$(function(){
    $('.js_title_li_ul_index_mian').on('click',function(){
        $(this).next('.js_sub_cats_ul_index_mian').slideToggle();
    });
    $('.js_title_li_sub_cats_ul_index_mian').on('click',function(){
        $(this).next('.js_cat_posts_list').slideToggle();
    });
});

var now_slider = getParam('date_slide');
now_slider = parseFloat(now_slider);
var top = 0;

$(window).scroll(function() {
    var flex_start = $('.position_ab').outerHeight() + $('.header_logo').height();
    var flex_top =$(window).scrollTop();
    if($(window).scrollTop()>=flex_start) {
        $('.position_ab').css('top',flex_top);
    }
    else {
        $('.position_ab').css('top',0);
    }
});

function getParam(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
//if (isNaN(now_slider)){
//    $(function () {
//        var slider = $('.bx_slider_wapper').bxSlider({
//            auto: false,
//            infiniteLoop: false,
//            hideControlOnEnd: true,
//            pager: true,
//            controls:true,
//            nextText:'<span class="slider_next_arrow">次へ</span>',
//            prevText:'<span class="slider_prev_arrow">前へ</span>',
//            nextSelector:'.slider_next',
//            prevSelector:'.slider_prev',
//            pause:6000,
//            slideMargin:10,
//            touchEnabled:false,
//            pagerCustom: '.next_prev_type_box',
//            onSlideAfter: function($slideElement, oldIndex, newIndex){
//                $('body,html').animate({
//                    scrollTop: 0
//                }, 50);
//            }
//        });
//    });
//}
//else {
//    $(function () {
//        var slider = $('.bx_slider_wapper').bxSlider({
//            auto: false,
//            infiniteLoop: false,
//            hideControlOnEnd: true,
//            pager: true,
//            controls:true,
//            pagerType: 'full',
//            startSlide:now_slider,
//           nextText:'<span class="slider_next_arrow">次へ</span>',
//            prevText:'<span class="slider_prev_arrow">前へ</span>',
//            nextSelector:'.slider_next',
//            prevSelector:'.slider_prev',
//            pause:6000,
//            slideMargin:10,
//            touchEnabled:false,
//            pagerCustom: '.next_prev_type_box',
//            onSlideAfter: function($slideElement, oldIndex, newIndex){
//                $('body,html').animate({
//                    scrollTop: 0
//                }, 50);
//            }
//        });
//    });
//}

var now_contentsid = rest_url + post_id;
if (isNaN(now_slider)){
    now_slider = 0;
}else{
    now_slider = now_slider;
}
output_all(now_slider);

function output_all(now_slider) {
    $.getJSON(now_contentsid, function (item) {
        var count_items = item['post_meta']['new_page_header_date'].length;
        outputrest(item,now_slider,count_items);
        pager_prev(item,now_slider,count_items);
    });
}



function outputrest(item,key,count_items) {
    //$('.title_date_news').empty().append(item['post_meta']['new_page_header_date'][key]).fadeIn(500);
    $('.lp_body_contents').empty().append('<img src="'+item['post_meta']['new_page_body_img'][key]+'">').fadeIn(500);
    $('.lp_footer_contents').empty().append('<img src="'+item['post_meta']['new_page_footer_img'][key]+'">').fadeIn(500);
}

function pager_prev(item,key,count_items) {
    console.log(count_items);
    prev_count = key-1;
    next_count = key+1;
    if(prev_count>=0) {
        $('.slider_prev').empty().append('<span class="slider_prev_arrow">前日</span>').fadeIn(500);
        $('.slider_prev_arrow').on('click',function(){
            outputrest(item,prev_count,count_items);
            pager_prev(item,prev_count,count_items);
            $('body, html').animate({ scrollTop: 0 }, 500);     return false;
        });
    }else {
        $('.slider_prev').empty();
    }
    if(next_count < count_items) {
        $('.slider_next').empty().append('<span class="slider_next_arrow">翌日</span>').fadeIn(500);
        $('.slider_next_arrow').on('click',function(){
            outputrest(item,next_count,count_items);
            pager_prev(item,next_count,count_items);
            $('body, html').animate({ scrollTop: 0 }, 500);     return false;
        });
    }else {
        $('.slider_next').empty();
    }    
}

$(function(){
    $('.linew03footerTab').on('click',function(){
        let tabClass = $(this).data('openclass');
        console.log(tabClass);
        $('.linew03footerTab').removeClass('active').addClass('nonactive');
        $(this).removeClass('nonactive').addClass('active');
        $('.actionNew03').removeClass('active').addClass('nonactive');
        $(tabClass).removeClass('nonactive').addClass('active');
           $('body, html').animate({ scrollTop: 0 }, 500);
           return false;
    });
});

