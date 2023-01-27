var _ua = (function(u){
  return {
    Tablet:(u.indexOf("windows") != -1 && u.indexOf("touch") != -1 && u.indexOf("tablet pc") == -1) 
      || u.indexOf("ipad") != -1
      || (u.indexOf("android") != -1 && u.indexOf("mobile") == -1)
      || (u.indexOf("firefox") != -1 && u.indexOf("tablet") != -1)
      || u.indexOf("kindle") != -1
      || u.indexOf("silk") != -1
      || u.indexOf("playbook") != -1,
    Mobile:(u.indexOf("windows") != -1 && u.indexOf("phone") != -1)
      || u.indexOf("iphone") != -1
      || u.indexOf("ipod") != -1
      || (u.indexOf("android") != -1 && u.indexOf("mobile") != -1)
      || (u.indexOf("firefox") != -1 && u.indexOf("mobile") != -1)
      || u.indexOf("blackberry") != -1
  }
})(window.navigator.userAgent.toLowerCase());
if(_ua.Mobile){//驛｢�ｧ�ｽ�ｹ驛｢譎�ｽｧ�ｭ�ｽ�ｽ髫ｴ雜｣�ｿ�ｽ    
    $(function(){
        $('a img').bind('touchstart', function() {
            $(this).attr('src', $(this).attr('src').replace('_off', '_on')).stop().fadeTo("slow", 1);
        });
        $('a img').bind('touchend', function() {
            $(this).attr('src', $(this).attr('src').replace('_on', '_off')).stop().fadeTo("slow", 1);
        });    
    });
}else {
    $(function(){
	$(function(){
            $('a img').hover(function(){
                $(this).attr('src', $(this).attr('src').replace('_off', '_on'));
                    }, function(){
                    if (!$(this).hasClass('currentPage')) {
                    $(this).attr('src', $(this).attr('src').replace('_on', '_off'));
                }
            });
	});
    });
    $(function(){
	$(function(){
            $('a span img').hover(function(){
                $(this).attr('src', $(this).attr('src').replace('_off', '_on'));
                    }, function(){
                    if (!$(this).hasClass('currentPage')) {
                    $(this).attr('src', $(this).attr('src').replace('_on', '_off'));
                }
            });
	});
    });
}