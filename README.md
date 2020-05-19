# SMS
> 一款满足你的多种发送需求的短信发送组件

[![Latest Stable Version](https://poser.pugx.org/mucts/sms/v/stable.svg)](https://packagist.org/packages/mucts/sms) 
[![Total Downloads](https://poser.pugx.org/mucts/sms/downloads.svg)](https://packagist.org/packages/mucts/sms) 
[![Latest Unstable Version](https://poser.pugx.org/mucts/sms/v/unstable.svg)](https://packagist.org/packages/mucts/sms) 
[![License](https://poser.pugx.org/mucts/sms/license.svg)](https://packagist.org/packages/mucts/sms)



## 特点

1. 支持目前市面多家服务商
1. 一套写法兼容所有平台
1. 简单配置即可灵活增减服务商
1. 内置多种服务商轮询策略、支持自定义轮询策略
1. 统一的返回值格式，便于日志与监控
1. 自动轮询选择可用的服务商
1. 更多等你去发现与改进...

## 平台支持

- [阿里云](https://www.aliyun.com/)
- [云片](https://www.yunpian.com)
- [Submail](https://www.mysubmail.com)
- [螺丝帽](https://luosimao.com/)
- [容联云通讯](http://www.yuntongxun.com)
- [互亿无线](http://www.ihuyi.com)
- [聚合数据](https://www.juhe.cn)
- [SendCloud](http://www.sendcloud.net/)
- [百度云](https://cloud.baidu.com/)
- [华信短信平台](http://www.ipyy.com/)
- [253云通讯（创蓝）](https://www.253.com/)
- [融云](http://www.rongcloud.cn)
- [天毅无线](http://www.85hu.com/)
- [腾讯云 SMS](https://cloud.tencent.com/product/sms)
- [阿凡达数据](http://www.avatardata.cn/)
- [华为云](https://www.huaweicloud.com/product/msgsms.html)
- [网易云信](https://yunxin.163.com/sms)
- [云之讯](https://www.ucpaas.com/index.html)
- [凯信通](http://www.kingtto.cn/)
- [七牛云](https://www.qiniu.com/)
- [UE35.net](http://uesms.ue35.cn/)
- [Ucloud](https://www.ucloud.cn)

## 环境需求

- PHP ^7.1

## 安装

```shell
$ composer require mucts/sms
```

**For Laravel notification**

如果你喜欢使用 [Laravel Notification](https://laravel.com/docs/5.8/notifications), 可以考虑直接使用朋友封装的拓展包：

https://github.com/mucts/laravel-sms

## 使用

```php
use MuCTS\SMS\SMS;

$config = [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => MuCTS\SMS\Strategies\Order::class,

        // 默认可用的发送网关
        'gateways' => [
            'yunpian', 'aliyun',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'yunpian' => [
            'api_key' => '824f0ff2f71cab52936axxxxxxxxxx',
        ],
        'aliyun' => [
            'access_key_id' => '',
            'access_key_secret' => '',
            'sign_name' => '',
        ],
        //...
    ],
];

$sms = new SMS($config);

$sms->send(13188888888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

## 短信内容

由于使用多网关发送，所以一条短信要支持多平台发送，每家的发送方式不一样，但是我们抽象定义了以下公用属性：

- `content` 文字内容，使用在像云片类似的以文字内容发送的平台
- `template` 模板 ID，使用在以模板ID来发送短信的平台
- `data`  模板变量，使用在以模板ID来发送短信的平台

所以，在使用过程中你可以根据所要使用的平台定义发送的内容。

```php
$sms->send(17508598888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

你也可以使用闭包来返回对应的值：

```php
$sms->send(17508598888, [
    'content'  => function($gateway){
        return '您的验证码为: 6379';
    },
    'template' => function($gateway){
        return 'SMS_001';
    },
    'data' => function($gateway){
        return [
            'code' => 6379
        ];
    },
]);
```