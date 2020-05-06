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
 * @see https://cloud.baidu.com/doc/SMS/API.html
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class Baidu extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_HOST = 'sms.bj.baidubce.com';
    /** @var string 请求路由 */
    protected const ENDPOINT_URI = '/bce/v2/message';
    /** @var string 安全校验方式 */
    protected const BCE_AUTH_VERSION = 'bce-auth-v1';
    /** @var int 签名有效时长 单位：秒 */
    protected const DEFAULT_EXPIRATION_IN_SECONDS = 1800; //签名有效期默认1800秒
    /** @var int 成功响应码 */
    protected const SUCCESS_CODE = 1000;

    /**
     * Build endpoint url.
     *
     * @return string
     */
    protected function buildEndpoint(): string
    {
        return 'http://' . $this->getConfig()->get('domain', self::ENDPOINT_HOST) . self::ENDPOINT_URI;
    }

    /**
     * Generate Authorization header.
     *
     * @param array $signHeaders
     * @param int $datetime
     * @return string
     */
    protected function sign(array $signHeaders, int $datetime): string
    {
        $authString = self::BCE_AUTH_VERSION . '/' . $this->getConfig()->get('ak') . '/' . $datetime . '/' . self::DEFAULT_EXPIRATION_IN_SECONDS;
        $signingKey = hash_hmac('sha256', $authString, $this->getConfig()->get('sk'));
        $canonicalURI = str_replace('%2F', '/', rawurlencode(self::ENDPOINT_URI));
        $canonicalQueryString = '';
        $signedHeaders = empty($signHeaders) ? '' : strtolower(trim(implode(';', array_keys($signHeaders))));
        $canonicalHeader = $this->getCanonicalHeaders($signHeaders);
        $canonicalRequest = "POST\n{$canonicalURI}\n{$canonicalQueryString}\n{$canonicalHeader}";
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);
        return "{$authString}/{$signedHeaders}/{$signature}";
    }

    /**
     * 生成标准化 http 请求头串.
     *
     * @param array $headers
     * @return string
     */
    protected function getCanonicalHeaders(array $headers): string
    {
        $headerStrings = [];
        foreach ($headers as $name => $value) {
            $headerStrings[] = rawurlencode(strtolower(trim($name))) . ':' . rawurlencode(trim($value));
        }
        sort($headerStrings);
        return implode("\n", $headerStrings);
    }

    /**
     * 根据 指定的 keys 过滤应该参与签名的 header.
     *
     * @param array $headers
     * @param array $keys
     * @return array
     */
    protected function getHeadersToSign(array $headers, array $keys): array
    {
        return array_intersect_key($headers, array_flip($keys));
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) {
            $this->setConfig($config);
        }
        $params = [
            'invokeId' => $this->getConfig()->get('invoke_id'),
            'phoneNumber' => $mobile->getNumber(),
            'templateCode' => $message->getTemplate($this),
            'contentVar' => $message->getData($this),
        ];
        $datetime = gmdate('Y-m-d\TH:i:s\Z');
        $headers = [
            'host' => self::ENDPOINT_HOST,
            'content-type' => 'application/json',
            'x-bce-date' => $datetime,
            'x-bce-content-sha256' => hash('sha256', json_encode($params)),
        ];
        //获得需要签名的数据
        $signHeaders = $this->getHeadersToSign($headers, ['host', 'x-bce-content-sha256']);
        $headers['Authorization'] = $this->sign($signHeaders, $datetime);
        $result = $this->request('post', self::buildEndpoint(), ['headers' => $headers, 'json' => $params]);
        if (self::SUCCESS_CODE != $result['code']) {
            throw new Exception($result['message'], $result['code'], $result);
        }
        return $result;
    }
}