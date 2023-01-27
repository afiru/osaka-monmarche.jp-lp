//ここから下はいじらない。
//日付取得
//関数設定用
function get_cat_id(slug) {
    var cat_id;
    $.ajaxSetup({async: false});//同期通信(json取ってくるまで待つ)
    get_catid_url = rest_api_base + "categories/?slug=" +slug;
    var cat_return_id = $.getJSON( get_catid_url, function(cats) {
        cat_id = cats[0]['id'];
        //console.log(cat_id);
    });
    $.ajaxSetup({async: true});
    return cat_id;
}

function img_output(thumbs_url) {
    return '<img src="'+ thumbs_url +'">';
}
var dateflug = 7;//何日前までを出すかの指定
var newicon = '<span class="new">NEW</span>';//画像のときは画像タグを挿入。
var new_not = '<span class="new">&nbsp;</span>';
//ここから下はいじらない。
//日付取得
var formatDate = function (date, format) {
  if (!format) format = 'YYYY-MM-DD hh:mm:ss.SSS';
  format = format.replace(/YYYY/g, date.getFullYear());
  format = format.replace(/MM/g, ('0' + (date.getMonth() + 1)).slice(-2));
  format = format.replace(/DD/g, ('0' + date.getDate()).slice(-2));
  format = format.replace(/hh/g, ('0' + date.getHours()).slice(-2));
  format = format.replace(/mm/g, ('0' + date.getMinutes()).slice(-2));
  format = format.replace(/ss/g, ('0' + date.getSeconds()).slice(-2));
  if (format.match(/S/g)) {
    var milliSeconds = ('00' + date.getMilliseconds()).slice(-3);
    var length = format.match(/S/g).length;
    for (var i = 0; i < length; i++) format = format.replace(/S/, milliSeconds.substring(i, i + 1));
  }
  return format;
};
//日付加算
var dateDiff = function (date1, date2, interval) {
  var diff = date2.getTime() - date1.getTime();
  switch (interval) {
    case 'YYYY':
      var d1 = new Date(date1.getTime());
      var d2 = new Date(date2.getTime());
      d1.setYear(0);
      d2.setYear(0);
      var i;
      if (diff >= 0) {
        i = d2.getTime() < d1.getTime() ? -1 : 0;
      } else {
        i = d2.getTime() <= d1.getTime() ? 0 : 1;
      }
      return date2.getYear() - date1.getYear() + i;
      break;
    case 'MM':
      var d1 = new Date(date1.getTime());
      var d2 = new Date(date2.getTime());
      d1.setYear(0);
      d1.setMonth(0);
      d2.setYear(0);
      d2.setMonth(0);
      var i;
      if (diff >= 0) {
        i = d2.getTime() < d1.getTime() ? -1 : 0;
      } else {
        i = d2.getTime() <= d1.getTime() ? 0 : 1;
      }
      return ((date2.getYear() * 12) + date2.getMonth()) - ((date1.getYear() * 12) + date1.getMonth()) + i;
      break;
    case 'hh':
      return ~~(diff / (60 * 60 * 1000));
      break;
    case 'mm':
      return ~~(diff / (60 * 1000));
      break;
    case 'ss':
      return ~~(diff / 1000);
      break;
    default:
      return ~~(diff / (24 * 60 * 60 * 1000));
  }
};
//曜日なしの出力（ドット形式）
function get_date_dotto(date) {
    var date = date;
    //本日の取得
    var dateObj = new Date();
    nowdate = formatDate(dateObj, 'YYYY/MM/DD'); 
    //投稿日の取得
    var post_date_dot = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2);
    post_date = date.substr(0, 4) + '/' + date.substr(5, 2) + '/' + date.substr(8, 2);
    myDay = new Array( "日","月","火","水","木","金","土" ); //配列オブジェクトを生成
    myDate = new Date( date.substr(0, 4),date.substr(5, 2),date.substr(8, 2)); //指定した時刻を表す日付オブジェクトを作成
    var today = new Date( post_date ) ;
    var weekday = [ "日", "月", "火", "水", "木", "金", "土" ] ;
    var	wday = "(" + weekday[ today.getDay() ] + ")" ;                     
    output_post_date = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2) + '';
    return output_post_date;
}
//曜日なしの出力（年月日）
function get_date_nengatu(date) {
    var date = date;
    //本日の取得
    var dateObj = new Date();
    nowdate = formatDate(dateObj, 'YYYY/MM/DD'); 
    //投稿日の取得
    var post_date_dot = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2);
    post_date = date.substr(0, 4) + '/' + date.substr(5, 2) + '/' + date.substr(8, 2);
    myDay = new Array( "日","月","火","水","木","金","土" ); //配列オブジェクトを生成
    myDate = new Date( date.substr(0, 4),date.substr(5, 2),date.substr(8, 2)); //指定した時刻を表す日付オブジェクトを作成
    var today = new Date( post_date ) ;
    var weekday = [ "日", "月", "火", "水", "木", "金", "土" ] ;
    var	wday = "(" + weekday[ today.getDay() ] + ")" ;                     
    output_post_date = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2) + '';
    return output_post_date;
}

//曜日付きの日付出力
function get_date_yobi(date) {
    var date = date;
    //本日の取得
    var dateObj = new Date();
    nowdate = formatDate(dateObj, 'YYYY/MM/DD'); 
    //投稿日の取得
    var post_date_dot = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2);
    post_date = date.substr(0, 4) + '/' + date.substr(5, 2) + '/' + date.substr(8, 2);
    myDay = new Array( "日","月","火","水","木","金","土" ); //配列オブジェクトを生成
    myDate = new Date( date.substr(0, 4),date.substr(5, 2),date.substr(8, 2)); //指定した時刻を表す日付オブジェクトを作成
    var today = new Date( post_date ) ;
    var weekday = [ "日", "月", "火", "水", "木", "金", "土" ] ;
    var	wday = "(" + weekday[ today.getDay() ] + ")" ;                     
    output_post_date = date.substr(0, 4) + '年' + date.substr(5, 2) + '月' + date.substr(8, 2) + '日' + wday;
    return output_post_date;
}
//NEWアイコンの設定
function get_new_icon(date) {
    var date = date;
    //本日の取得
    var dateObj = new Date();
    nowdate = formatDate(dateObj, 'YYYY/MM/DD'); 
    //投稿日の取得
    post_date = date.substr(0, 4) + '/' + date.substr(5, 2) + '/' + date.substr(8, 2);
    var post_date_dot = date.substr(0, 4) + '.' + date.substr(5, 2) + '.' + date.substr(8, 2);
    flugdate = dateDiff(new Date(post_date), new Date(nowdate));
    if(flugdate <= dateflug) {
        datelist = newicon;
    }
    else {
        datelist = new_not;
    }
    return datelist;
}

//改行挿入
function nl2br(strings) {
  var res = strings.replace(/\r\n/g, "<br>");
  res = res.replace(/(\n|\r)/g, "<br>");
  return res;
}

//タグ削除
function req_tag(storing){
    var storings = storing.replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'');
    return storings;
} 

//文字数チェック
  var cutFigure = '45'; // カットする文字数
  var afterTxt = ' …'; // 文字カット後に表示するテキスト
//文字数制限解除
function exstring(string) {
  string = req_tag(string);
  textLength = string.length;
  textTrim = string.substr(0,(cutFigure));
  textTrim = textTrim + '...';
  return textTrim;
}

//エージェント調査
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