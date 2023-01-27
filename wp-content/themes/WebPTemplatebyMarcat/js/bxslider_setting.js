call("//cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js", function(){
    $(document).ready(function(){
        $('.クラス名').bxSlider({ //bxsliderを使用しているulクラスを指定
            pager: false,
            minSlides: 3,
            maxSlides: 3,
            moveSlides: 1,
            slideWidth: 280,
            controls:true,
            auto: true,
            slideMargin: 20
        });
    });
});