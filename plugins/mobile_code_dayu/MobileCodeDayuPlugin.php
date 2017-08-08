<?php
// +----------------------------------------------------------------------
// | Alidayu [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\mobile_code_dayu;
use cmf\lib\Plugin;
use plugins\mobile_code_dayu\model\PluginMobileCodeDayuModel;

/**
 * MobileCodeDayuPlugin
 */
class MobileCodeDayuPlugin extends Plugin
{

    public $info = [
        'name'        => 'mobile_code_dayu',
        'title'       => '阿里大于短信接口',
        'description' => '阿里大于短信接口',
        'status'      => 1,
        'author'      => 'Tangchao',
        'version'     => '1.0'
    ];

    public $has_admin = 0;

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function sendMobileVerificationCode($param)
    {        
        $mobile        = $param['mobile'];
        $code          = $param['code'];
        $config        = $this->getConfig();
        $expire_minute = intval($config['expire_minute']);
        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
        $expire_time   = time() + $expire_minute * 60;
        $result        = false;
        $appkey        = $config['AppKey'];
        $secret        = $config['AppSecret'];
        $signname      = $config['autograph'];
        $template      = $config['Template'];
        $delete = new PluginMobileCodeDayuModel();
        $resp = $delete->Sendsms($appkey,$secret,$signname,'{"validation":"'.$code.'"}',$mobile,$template);
        $result = [
            'error'     => 0,
            'message' => $resp
        ];
        return $result;
    }
}

