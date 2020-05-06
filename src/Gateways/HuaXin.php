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
 * @see http://www.ipyy.com/help/
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class HuaXin extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'http://%s/smsJson.aspx';

    /**
     * Build endpoint url.
     * @param string $ip
     * @return string
     */
    protected function buildEndpoint(string $ip): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $ip);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint($this->getConfig()->get('ip'));
        $result = $this->post($endpoint, [
            'userid' => $this->getConfig()->get('user_id'),
            'account' => $this->getConfig()->get('account'),
            'password' => $this->getConfig()->get('password'),
            'mobile' => $mobile->getNumber(),
            'content' => $message->getContent($this),
            'sendTime' => '',
            'action' => 'send',
            'extno' => $this->getConfig()->get('ext_no'),
        ]);
        if ('Success' !== $result['returnstatus']) {
            throw new Exception($result['message'], 400, $result);
        }
        return $result;
    }
}