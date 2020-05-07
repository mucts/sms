<?php
/**
 * This file is part of the mucts/sms.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 * @see http://www.avatardata.cn/Docs/Api/fd475e40-7809-4be7-936c-5926dd41b0fe
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class AvatarData extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'http://v1.avatardata.cn/Sms/Send';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if($config) $this->setConfig($config);
        $params = [
            'mobile' => $mobile->getNumber(),
            'templateId' => $message->getTemplate($this),
            'param' => implode(',', $message->getData($this)->all()),
            'dtype' => self::ENDPOINT_FORMAT,
            'key' => $this->getConfig()->get('app_key'),
        ];
        $result = $this->get(self::ENDPOINT_URL, $params);
        if ($result['error_code']) {
            throw new Exception($result['reason'], $result['error_code'], $result);
        }
        return $result;
    }
}