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
 * @see http://sms.langma.cn
 */

namespace MuCTS\SMS\Gateways;

use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class LangMa extends Gateway
{
    use HasHttpRequest;

    protected const ENDPOINT_URL = 'http://smsapi.langma.cn/';
    protected const SUCCESS_CODE = '0';
    protected const OP_TYPE = 1001;

    private function sign(array $params): string
    {
        ksort($params);
        $params['app_key'] = $this->getConfig()->get('key');
        $string = http_build_query($params);
        return md5($string);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $params = [
            "op_type" => self::OP_TYPE,
            "app_id" => $this->getConfig()->get('app_id'),
            "phone" => $mobile->getNumber(),
            "sms" => $message->getContent()
        ];
        $params['sign'] = $this->sign($params);
        $result = $this->post(self::ENDPOINT_URL, $params);
        if (self::SUCCESS_CODE != $result['code']) {
            throw new Exception($result['msg'], $result['code'], $result);
        }
        return $result;
    }
}