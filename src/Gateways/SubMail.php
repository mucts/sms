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
 * @see https://www.mysubmail.com/chs/documents/developer/index
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class SubMail extends Gateway
{
    use HasHttpRequest;
    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://api.mysubmail.com/%s.%s';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';

    /**
     * Build endpoint url.
     *
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint(string $function):string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $function, self::ENDPOINT_FORMAT);
    }

    /**
     * Check if the phone number belongs to chinese mainland.
     *
     * @param Mobile $mobile
     * @return bool
     */
    protected function inChineseMainland(Mobile $mobile)
    {
        $code = $mobile->getIDDCode();
        return empty($code) || 86 === $code;
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if($config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint($this->inChineseMainland($mobile) ? 'message/xsend' : 'internationalsms/xsend');
        $data = $message->getData($this);
        $result = $this->post($endpoint, [
            'appid' => $this->getConfig()->get('app_id'),
            'signature' => $this->getConfig()->get('app_key'),
            'project' => !empty($data['project']) ? $data['project'] : $this->getConfig()->get('project'),
            'to' => $mobile->getUniversalNumber(),
            'vars' => json_encode($data, JSON_FORCE_OBJECT),
        ]);

        if ('success' != $result['status']) {
            throw new Exception($result['msg'], $result['code'], $result);
        }
        return $result;
    }
}