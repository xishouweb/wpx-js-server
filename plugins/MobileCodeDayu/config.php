<?php
// +----------------------------------------------------------------------
// | Alidayu [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
return array (
	'AppKey' => array (
		'title' => 'App Key',
		'type' => 'text',
		'value' => '',
		'tip' => '阿里大于短信接口'
	),
    'AppSecret' => array (
        'title' => 'App Secret',
        'type' => 'text',
        'value' => '',
        'tip' => '申请地址：http://www.alidayu.com'
    ),
    'autograph' => array (
        'title' => '签名',
        'type' => 'text',
        'value' => '',
        'tip' => '默认只能选其一：活动验证、变更验证、登录验证、注册验证、身份验证。如需添加，请自行申请'
    ),
    'Template' => array (
        'title' => '模板ID',
        'type' => 'text',
        'value' => '',
        'tip' => '默认格式为：SMS_6370459。如需添加，请自行申请。格式：(${validation})注册验证码10分钟内有效，请尽快完成验证。' //表单的帮助提示
    ),
    'expire_minute' => array (
        'title' => '有效期',
        'type' => 'text',
        'value' => '30',
        'tip' => '短信验证码过期时间，单位分钟'
    ),
);
					