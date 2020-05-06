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
 * @see https://zz.253.com/v5.html#/api_doc
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class ChuangLan extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求接口 */
    protected const ENDPOINT_URL_TEMPLATE = 'https://%s.253.com/msg/send/json';
    /** @var string 国际短信 */
    protected const INT_URL = 'http://intapi.253.com/send/json';
    /** @var string 验证码渠道code */
    protected const CHANNEL_VALIDATE_CODE = 'smsbj1';
    /** @var string 会员营销渠道code. */
    protected const CHANNEL_PROMOTION_CODE = 'smssh1';

    /**
     * @param int $IDDCode
     * @return string
     * @throws Exception
     */
    protected function buildEndpoint(int $IDDCode = 86)
    {
        $channel = $this->getChannel($IDDCode);
        if (self::INT_URL === $channel) {
            return $channel;
        }
        return sprintf(self::ENDPOINT_URL_TEMPLATE, $channel);
    }

    /**
     * @param int $IDDCode
     * @return mixed
     * @throws Exception
     */
    protected function getChannel(int $IDDCode)
    {
        if (86 != $IDDCode) {
            return self::INT_URL;
        }
        $channel = $this->getConfig()->get('channel', self::CHANNEL_VALIDATE_CODE);
        if (!in_array($channel, [self::CHANNEL_VALIDATE_CODE, self::CHANNEL_PROMOTION_CODE])) {
            throw new Exception('Invalid channel for ChuangLan Gateway.');
        }
        return $channel;
    }

    /**
     * @param string $content
     * @param int $IDDCode
     * @return string
     * @throws Exception
     */
    protected function wrapChannelContent(string $content, int $IDDCode): string
    {
        $channel = $this->getChannel($IDDCode);
        if (self::CHANNEL_PROMOTION_CODE == $channel) {
            $sign = (string)$this->getConfig()->get('sign', '');
            if (empty($sign)) {
                throw new Exception('Invalid sign for ChuangLan Gateway when using promotion channel');
            }
            $unsubscribe = (string)$this->getConfig()->get('unsubscribe', '');
            if (empty($unsubscribe)) {
                throw new Exception('Invalid unsubscribe for ChuangLan Gateway when using promotion channel');
            }
            $content = $sign . $content . $unsubscribe;
        }
        return $content;
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) {
            $this->setConfig($config);
        }
        $IDDCode = $mobile->getIDDCode() ?? 86;
        $params = [
            'account' => $this->getConfig()->get('account'),
            'password' => $this->getConfig()->get('password'),
            'phone' => $mobile->getNumber(),
            'msg' => $this->wrapChannelContent($message->getContent($this), $IDDCode),
        ];
        if (86 != $IDDCode) {
            $params['mobile'] = $IDDCode . $mobile->getNumber();
            $params['account'] = $this->getConfig()->get('intel_account') ?: $this->getConfig()->get('account');
            $params['password'] = $this->getConfig()->get('intel_password') ?: $this->getConfig()->get('password');
        }
        $result = $this->postJson($this->buildEndpoint($IDDCode), $params);
        if (!isset($result['code']) || '0' != $result['code']) {
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE), isset($result['code']) ? $result['code'] : 0, $result);
        }
        return $result;
    }
}