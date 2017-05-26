<?php
/**
 * 入口文件
 * Some rights reserved：www.冠美奇迹.com
 */
if (ini_get('magic_quotes_gpc')) {
    function stripslashesRecursive(array $array)
    {
        foreach ($array as $k => $v) {
            if (is_string($v)) {
                $array[$k] = stripslashes($v);
            } else if (is_array($v)) {
                $array[$k] = stripslashesRecursive($v);
            }
        }
        return $array;
    }

    $_GET = stripslashesRecursive($_GET);
    $_POST = stripslashesRecursive($_POST);
}
//开启调试模式
define("APP_DEBUG", false);
//网站当前路径
define('SITE_PATH', dirname(__FILE__) . "/");
//项目路径，不可更改
define('APP_PATH', SITE_PATH . 'application/');
//项目相对路径，不可更改
define('SPAPP_PATH', SITE_PATH . 'simplewind/');
//
define('SPAPP', './application/');
//项目资源目录，不可更改
define('SPSTATIC', SITE_PATH . 'statics/');
//定义缓存存放路径
define("RUNTIME_PATH", SITE_PATH . "data/runtime/");
//静态缓存目录
define("HTML_PATH", SITE_PATH . "data/runtime/Html/");
//版本号
define("ThinkCMF_VERSION", 'X2.2.3');

define("ThinkCMF_CORE_TAGLIBS", 'cx,Common\Lib\Taglib\TagLibSpadmin,Common\Lib\Taglib\TagLibHome');

if (function_exists('saeAutoLoader') || isset($_SERVER['HTTP_BAE_ENV_APPID'])) {

} else {
    if (!file_exists("data/install.lock")) {
        if (strtolower($_GET['g']) != "install") {
            header("Location:./index.php?g=install");
            exit();
        }
    }
}
//uc client root
define("UC_CLIENT_ROOT", './api/uc_client/');

if (file_exists(UC_CLIENT_ROOT . "config.inc.php")) {
    include UC_CLIENT_ROOT . "config.inc.php";
}

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

$allow_origin = array(
    'http://gm.wujiesheying.com:8080',
    'http://gm.wujiesheying.com:8000',
    'http://www.gm-fitness.com:8080',
    'http://localhost:3000'
);

if (in_array($origin, $allow_origin)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT');
}
//载入框架核心文件
require SPAPP_PATH . 'Core/ThinkPHP.php';

