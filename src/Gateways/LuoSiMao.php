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
 * @see https://luosimao.com/docs/api/
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class LuoSiMao extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://%s.luosimao.com/%s/%s.%s';
    /** @var string 版本 */
    protected const ENDPOINT_VERSION = 'v1';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';


    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $function
     * @return string
     */
    protected function buildEndpoint(string $type, string $function): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $function, self::ENDPOINT_FORMAT);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint('sms-api', 'send');
        $result = $this->post($endpoint, [
            'mobile' => $mobile->getNumber(),
            'message' => $message->getContent($this),
        ], [
            'Authorization' => 'Basic ' . base64_encode('api:key-' . $this->getConfig()->get('api_key')),
        ]);
        if ($result['error']) {
            throw new Exception($result['msg'], $result['error'], $result);
        }
        return $result;
    }
}