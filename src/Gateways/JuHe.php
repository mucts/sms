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
 * @see https://www.juhe.cn/docs/api/id/54
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;
use function foo\func;

class JuHe extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'http://v.juhe.cn/sms/send';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';


    /**
     * @param Config $vars
     *
     * @return string
     */
    protected function formatTemplateVars(Config $vars)
    {
        $formatted = new Config();
        $vars->each(function ($value, $key) use (&$formatted) {
            $formatted->put(sprintf('#%s#', trim($key, '#')), $value);
        });
        return http_build_query($formatted->all());
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $params = [
            'mobile' => $mobile->getNumber(),
            'tpl_id' => $message->getTemplate($this),
            'tpl_value' => $this->formatTemplateVars($message->getData($this)),
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