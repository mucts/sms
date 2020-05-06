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
 * @see http://sendcloud.sohu.com/doc/sms/
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class SendCloud extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'http://www.sendcloud.net/smsapi/%s';

    protected function formatTemplateVars(Config $vars): string
    {
        $formatted = [];
        foreach ($vars as $key => $value) {
            $formatted[sprintf('%%%s%%', trim($key, '%'))] = $value;
        }
        return json_encode($formatted, JSON_FORCE_OBJECT);
    }

    /**
     * @param array $params
     * @return string
     */
    protected function sign(array $params): string
    {
        $key = $this->getConfig()->get('key');
        ksort($params);
        return md5(sprintf('%s&%s&%s', $key, urldecode(http_build_query($params)), $key));
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $params = [
            'smsUser' => $this->getConfig()->get('sms_user'),
            'templateId' => $message->getTemplate($this),
            'msgType' => $mobile->getIDDCode() ? 2 : 0,
            'phone' => $mobile->getZeroPrefixedNumber(),
            'vars' => $this->formatTemplateVars($message->getData($this)),
        ];
        if ($this->getConfig()->get('timestamp', false)) {
            $params['timestamp'] = time() * 1000;
        }
        $params['signature'] = $this->sign($params);
        $result = $this->post(sprintf(self::ENDPOINT_TEMPLATE, 'send'), $params);

        if (!$result['result']) {
            throw new Exception($result['message'], $result['statusCode'], $result);
        }
        return $result;
    }
}