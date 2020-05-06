<?php
/**
 * This file is part of the mucts.com.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 * @see http://www.yuntongxun.com/doc/rest/sms/3_2_2_2.html
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class YunTongXun extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://%s:%s/%s/%s/%s/%s/%s?sig=%s';
    /** @var string 服务器IP */
    protected const SERVER_IP = 'app.cloopen.com';
    /** @var string 测试IP */
    protected const DEBUG_SERVER_IP = 'sandboxapp.cloopen.com';
    /** @var int 测试末班ID */
    protected const DEBUG_TEMPLATE_ID = 1;
    /** @var string 端口 */
    protected const SERVER_PORT = '8883';
    /** @var string SDK版本 */
    protected const SDK_VERSION = '2013-12-26';
    /** @var string 成功响应码 */
    protected const SUCCESS_CODE = '000000';

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $resource
     * @param string $datetime
     * @return string
     */
    protected function buildEndpoint(string $type, string $resource, string $datetime): string
    {
        $serverIp = $this->config->get('debug') ? self::DEBUG_SERVER_IP : self::SERVER_IP;
        $accountType = $this->config->get('is_sub_account') ? 'SubAccounts' : 'Accounts';
        $sig = strtoupper(md5($this->getConfig()->get('account_sid') . $this->getConfig()->get('account_token') . $datetime));
        return sprintf(self::ENDPOINT_TEMPLATE, $serverIp, self::SERVER_PORT, self::SDK_VERSION, $accountType, $this->getConfig()->get('account_sid'), $type, $resource, $sig);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $datetime = date('YmdHis');
        $endpoint = $this->buildEndpoint('SMS', 'TemplateSMS', $datetime);
        $result = $this->request('post', $endpoint, [
            'json' => [
                'to' => $mobile->getNumber(),
                'templateId' => (int)($this->config->get('debug') ? self::DEBUG_TEMPLATE_ID : $message->getTemplate($this)),
                'appId' => $this->getConfig()->get('app_id'),
                'datas' => $message->getData($this),
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
                'Authorization' => base64_encode($this->getConfig()->get('account_sid') . ':' . $datetime),
            ],
        ]);

        if (self::SUCCESS_CODE != $result['statusCode']) {
            throw new Exception($result['statusCode'], $result['statusCode'], $result);
        }
        return $result;
    }
}