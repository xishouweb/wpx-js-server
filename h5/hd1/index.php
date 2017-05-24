<?php
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL || ~E_NOTICE); //显示除去 E_NOTICE 之外的所有错误信息
$wxParams = curlGet("http://gm.wujiesheying.com/h5/weixinjs.php?url=" . base64_encode('http://gm.wujiesheying.com' . $_SERVER["REQUEST_URI"]));
function curlGet($url, $method = 'get', $data = '')
{
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $temp = curl_exec($ch);
    return $temp;
}

?>
<html>
<head>
    <meta name="viewport"
          content="width=device-width, initial-scale=1,initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>十天超越计划</title>
    <script type="text/javascript" src="../common/zepto.min.js"></script>
    <script type="text/javascript" src="../common/common.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .img1 {
            width: 100%;
        }
    </style>
</head>
<body>
<script>
    
</script>
<div class="con">
    <img class="img1" src="img/12.jpg">
</div>
<button>领取免费体验卡</button>

<script type="text/javascript" src="../common/jweixin-1.0.0.js"></script>
<script type="text/javascript">
    wx.config(
        <?php echo $wxParams;?>
    );
    wx.ready(function () {
        wx.onMenuShareTimeline({
            title: '十天超越计划', // 分享标题
            link: 'http://gm.wujiesheying.com/h5/hd1/index.php', // 分享链接
            imgUrl: 'http://gm.wujiesheying.com/h5/hd1/img/hd1.jpg', // 分享图标
            success: function () {
            },
            cancel: function () {
            }
        });
        wx.onMenuShareAppMessage({
            title: '十天超越计划', // 分享标题
            desc: '坚持这十天,你就赢了!', // 分享描述
            link: 'http://gm.wujiesheying.com/h5/hd1/index.php', // 分享链接
            imgUrl: 'http://gm.wujiesheying.com/h5/hd1/img/hd1.jpg', // 分享图标
            type: 'link', // 分享类型,music、video或link，不填默认为link
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
    });
</script>
</body>

</html>
