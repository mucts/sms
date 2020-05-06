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
 * @see https://shimo.im/docs/380b42d8cba24521/read
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class UE35 extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求域名 */
    protected const ENDPOINT_HOST = 'sms.ue35.cn';
    /** @var string 请求路由 */
    protected const ENDPOINT_URI = '/sms/interface/sendmess.htm';
    /** @var int 成功状态码 */
    protected const SUCCESS_CODE = 1;

    public static function getEndpointUri()
    {
        return 'http://' . static::ENDPOINT_HOST . static::ENDPOINT_URI;
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $params = [
            'username' => $this->getConfig()->get('username'),
            'userpwd' => $this->getConfig()->get('userpwd'),
            'mobiles' => $mobile->getNumber(),
            'content' => $message->getContent($this),
        ];
        $headers = [
            'host' => static::ENDPOINT_HOST,
            'content-type' => 'application/json',
            'user-agent' => 'PHP EasySms Client',
        ];
        $result = $this->request('get', self::getEndpointUri() . '?' . http_build_query($params), ['headers' => $headers]);
        if (is_string($result)) {
            $result = json_decode(json_encode(simplexml_load_string($result)), true);
        }

        if (self::SUCCESS_CODE != $result['errorcode']) {
            throw new Exception($result['message'], $result['errorcode'], $result);
        }
        return $result;
    }
}