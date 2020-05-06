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
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class TianYiWuXian extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'http://jk.106api.cn/sms%s.aspx';
    /** @var string 编码 */
    protected const ENDPOINT_ENCODE = 'UTF8';
    /** @var string */
    protected const ENDPOINT_TYPE = 'send';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';
    /** @var string 成功响应状态 */
    protected const SUCCESS_STATUS = 'success';
    /** @var string 成功响应状态码 */
    protected const SUCCESS_CODE = '0';

    /**
     * Build endpoint url.
     * @return string
     */
    protected function buildEndpoint(): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, self::ENDPOINT_ENCODE);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($this->config) $this->setConfig($config);
        $endpoint = $this->buildEndpoint();
        $params = [
            'gwid' => $this->getConfig()->get('gwid'),
            'type' => self::ENDPOINT_TYPE,
            'rece' => self::ENDPOINT_FORMAT,
            'mobile' => $mobile->getNumber(),
            'message' => $message->getContent($this),
            'username' => $this->getConfig()->get('username'),
            'password' => strtoupper(md5($config->get('password'))),
        ];
        $result = $this->post($endpoint, $params);
        $result = json_decode((string)$result, true);
        if (self::SUCCESS_STATUS !== $result['returnstatus'] || self::SUCCESS_CODE !== $result['code']) {
            throw new Exception($result['remark'], $result['code']);
        }
        return $result;
    }
}