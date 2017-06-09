<?php
// +----------------------------------------------------------------------
// | Alidayu [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\mobile_code_dayu\model;
use think\Model;
require_once 'plugins/mobile_code_dayu/Alidayu/TopSdk.class.php';

class PluginMobileCodeDayuModel extends Model
{

    function Sendsms($appkey,$secret,$SignName,$Smsparam,$tomobile,$Template)
    {
        if(!empty($SignName) && !empty($Smsparam) && !empty($tomobile) && !empty($Template)){
            $Send = $Send = new \TopClient();
            $Templates = $Template;
            $Send->appkey = $appkey;
            $Send->secretKey = $secret;
            $req = new \AlibabaAliqinFcSmsNumSendRequest();
            $req->setSmsType("normal");
            $req->setSmsFreeSignName("$SignName");
            $req->setSmsParam("$Smsparam");
            $req->setRecNum("$tomobile");
            $req->setSmsTemplateCode("$Templates");
            $resp = $Send->execute($req); 
            $resp = '发送成功';
        }else{
            $resp = '任何一项都不能为空';
        }
        return $resp;
    }
}