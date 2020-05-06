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
 * @see https://developer.qiniu.com/sms/api/5897/sms-api-send-message
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class QiNiu extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://%s.qiniuapi.com/%s/%s';
    /** @var string 接口版本 */
    protected const ENDPOINT_VERSION = 'v1';

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint(string $type, string $function): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $function);
    }

    /**
     * Build endpoint url.
     *
     * @param string $url
     * @param string $method
     * @param string $body
     * @param string $contentType
     * @return string
     */
    protected function sign(string $url, string $method, ?string $body = null, ?string $contentType = null): string
    {
        $urlItems = parse_url($url);
        $host = $urlItems['host'];
        if (isset($urlItems['port'])) {
            $port = $urlItems['port'];
        } else {
            $port = '';
        }
        $path = $urlItems['path'];
        if (isset($urlItems['query'])) {
            $query = $urlItems['query'];
        } else {
            $query = '';
        }
        $toSignStr = $method . ' ' . $path;
        if (!empty($query)) {
            $toSignStr .= '?' . $query;
        }
        $toSignStr .= "\nHost: " . $host;
        if (!empty($port)) {
            $toSignStr .= ':' . $port;
        }
        if (!empty($contentType)) {
            $toSignStr .= "\nContent-Type: " . $contentType;
        }
        $toSignStr .= "\n\n";
        if (!empty($body)) {
            $toSignStr .= $body;
        }
        $hmac = hash_hmac('sha1', $toSignStr, $this->getConfig()->get('secret_key'), true);
        return 'Qiniu ' . $this->getConfig()->get('access_key') . ':' . $this->base64UrlSafeEncode($hmac);
    }

    /**
     * @param string $data
     * @return string
     */
    protected function base64UrlSafeEncode($data): string
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint('sms', 'message/single');
        $data = $message->getData($this);
        $params = [
            'template_id' => $message->getTemplate($this),
            'mobile' => $mobile->getNumber(),
        ];
        if (!empty($data)) {
            $params['parameters'] = $data;
        }
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $headers['Authorization'] = $this->sign($endpoint, 'POST', json_encode($params), $headers['Content-Type']);
        $result = $this->postJson($endpoint, $params, $headers);

        if (isset($result['error'])) {
            throw new Exception($result['message'], $result['error'], $result);
        }
        return $result;
    }
}