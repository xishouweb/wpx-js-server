/**
 * Created by lihb on 5/8/17.
 */
var openid = getQueryString('openid');
if (openid == '' || openid == null) {
    if (getCookie('openid') == null || getCookie('openid') == '') {
        var sourceUrl = document.URL;
        window.location.href = "http://gm.wujiesheying.com/index.php?g=Restful&m=Oauth&a=login&sourceUrl=" + sourceUrl;
    } else {
        openid = getCookie('openid')
    }
} else {
    setCookie('openid', openid);
}
function setCookie(c_name, value) {
    var expiredays = 24 * 7 * 60 * 60;
    var exdate = new Date();
    exdate.setTime(Number(exdate) + expiredays);
    document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString());
}
function getCookie(c_name) {
    if (document.cookie.length > 0) {
        var c_start = document.cookie.indexOf(c_name + "=");//获取字符串的起点
        if (c_start != -1) {
            c_start = c_start + c_name.length + 1;//获取值的起点
            var c_end = document.cookie.indexOf(";", c_start);//获取结尾处
            if (c_end == -1) c_end = document.cookie.length;//如果是最后一个，结尾就是cookie字符串的结尾
            return decodeURI(document.cookie.substring(c_start, c_end));//截取字符串返回
        }
    }
    return "";
}

function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null)return unescape(r[2]);
    return null;
}