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
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class Huawei extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_HOST = 'https://api.rtc.huaweicloud.com:10443';
    /** @var string 请求路由 */
    protected const ENDPOINT_URI = '/sms/batchSendSms/v1';
    /** @var string 成功验证码 */
    protected const SUCCESS_CODE = '000000';

    /**
     * 构造 Endpoint.
     * @return string
     */
    protected function getEndpoint(): string
    {
        $endpoint = rtrim($this->getConfig()->get('endpoint', self::ENDPOINT_HOST), '/');
        return $endpoint . self::ENDPOINT_URI;
    }

    /**
     * 获取请求 Headers 参数.
     *
     * @param string $appKey
     * @param string $appSecret
     * @return array
     */
    protected function getHeaders($appKey, $appSecret): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE' => $this->buildWsseHeader($appKey, $appSecret),
        ];
    }

    /**
     * 构造X-WSSE参数值
     *
     * @param string $appKey
     * @param string $appSecret
     * @return string
     */
    protected function buildWsseHeader($appKey, $appSecret): string
    {
        $now = date('Y-m-d\TH:i:s\Z');
        $nonce = uniqid();
        $passwordDigest = base64_encode(hash('sha256', ($nonce . $now . $appSecret)));
        return sprintf(
            'UsernameToken Username="%s",PasswordDigest="%s",Nonce="%s",Created="%s"',
            $appKey,
            $passwordDigest,
            $nonce,
            $now
        );
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $appKey = $this->getConfig()->get('app_key');
        $appSecret = $this->getConfig()->get('app_secret');
        $channels = $this->getConfig()->get('from');
        $statusCallback = $this->getConfig()->get('callback', '');
        $endpoint = $this->getEndpoint();
        $headers = $this->getHeaders($appKey, $appSecret);
        $templateId = $message->getTemplate($this);
        $messageData = $message->getData($this);
        // 短信签名通道号码
        $from = 'default';
        if (isset($messageData['from'])) {
            $from = $messageData['from'];
            unset($messageData['from']);
        }
        $channel = isset($channels[$from]) ? $channels[$from] : '';
        if (empty($channel)) {
            throw new Exception("From Channel [{$from}] Not Exist");
        }

        $params = [
            'from' => $channel,
            'to' => $mobile->getUniversalNumber(),
            'templateId' => $templateId,
            'templateParas' => json_encode($messageData),
            'statusCallback' => $statusCallback,
        ];

        try {
            $result = $this->request('post', $endpoint, [
                'headers' => $headers,
                'form_params' => $params,
                'verify' => false,//为防止因HTTPS证书认证失败造成API调用失败，需要先忽略证书信任问题
            ]);
        } catch (Exception $e) {
            $result = $this->unwrapResponse($e->getResponse());
        }
        if (self::SUCCESS_CODE != $result['code']) {
            throw new Exception($result['description'], ltrim($result['code'], 'E'), $result);
        }
        return $result;
    }
}