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
 * @see https://www.yunpian.com/doc/zh_CN/intl/single_send.html
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class YunPian extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://%s.yunpian.com/%s/%s/%s.%s';
    /** @var string 接口版本 */
    protected const ENDPOINT_VERSION = 'v2';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $resource
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint(string $type, string $resource, string $function): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $resource, $function, self::ENDPOINT_FORMAT);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint('sms', 'sms', 'single_send');
        $signature = $this->getConfig()->get('signature', '');
        $content = $message->getContent($this);

        $result = $this->request('post', $endpoint, [
            'form_params' => [
                'apikey' => $this->getConfig()->get('api_key'),
                'mobile' => $mobile->getUniversalNumber(),
                'text' => 0 === stripos($content, '【') ? $content : $signature . $content,
            ],
            'exceptions' => false,
        ]);

        if ($result['code']) {
            throw new Exception($result['msg'], $result['code'], $result);
        }
        return $result;
    }
}