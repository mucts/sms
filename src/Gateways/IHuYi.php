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
 * @see https://www.ihuyi.com/api/sms.html
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class IHuYi extends Gateway
{
    use HasHttpRequest;

    /** @var string */
    protected const ENDPOINT_URL = 'http://106.ihuyi.com/webservice/sms.php?method=Submit';
    /** @var string */
    protected const ENDPOINT_FORMAT = 'json';
    /** @var int */
    protected const SUCCESS_CODE = 2;

    /**
     * Generate Sign.
     *
     * @param array $params
     *
     * @return string
     */
    protected function generateSign($params)
    {
        return md5($params['account'] . $this->getConfig()->get('api_key') . $params['mobile'] . $params['content'] . $params['time']);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $params = [
            'account' => $this->getConfig()->get('api_id'),
            'mobile' => $mobile->getIDDCode() ? sprintf('%s %s', $mobile->getIDDCode(), $mobile->getNumber()) : $mobile->getNumber(),
            'content' => $message->getContent($this),
            'time' => time(),
            'format' => self::ENDPOINT_FORMAT,
            'sign' => $this->getConfig()->get('signature'),
        ];
        $params['password'] = $this->generateSign($params);
        $result = $this->post(self::ENDPOINT_URL, $params);

        if (self::SUCCESS_CODE != $result['code']) {
            throw new Exception($result['msg'], $result['code'], $result);
        }
        return $result;
    }
}