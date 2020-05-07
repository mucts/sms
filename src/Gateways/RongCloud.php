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
 * @see https://www.rongcloud.cn/docs/sms_service.html#send_sms_code
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class RongCloud extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'http://api.sms.ronghub.com/%s.%s';
    /** @var string 操作 */
    protected const ENDPOINT_ACTION = 'sendCode';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';
    /** @var string 国别 */
    protected const ENDPOINT_REGION = '86';  // 中国区，目前只支持此国别
    /** @var int 成功响应码 */
    protected const SUCCESS_CODE = 200;

    /**
     * Generate Sign.
     *
     * @param array $params
     * @return string
     */
    protected function generateSign(array $params): string
    {
        return sha1(sprintf('%s%s%s', $this->getConfig()->get('app_secret'), $params['Nonce'], $params['Timestamp']));
    }

    /**
     * Build endpoint url.
     *
     * @param string $action
     * @return string
     */
    protected function buildEndpoint(string $action): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $action, self::ENDPOINT_FORMAT);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $data = $message->getData();
        $action = $data->get('action', self::ENDPOINT_ACTION);
        $endpoint = $this->buildEndpoint($action);

        $headers = [
            'Nonce' => uniqid(),
            'App-Key' => $this->getConfig()->get('app_key'),
            'Timestamp' => time(),
        ];
        $headers['Signature'] = $this->generateSign($headers);

        switch ($action) {
            case 'sendCode':
                $params = [
                    'mobile' => $mobile->getNumber(),
                    'region' => self::ENDPOINT_REGION,
                    'templateId' => $message->getTemplate($this),
                ];

                break;
            case 'verifyCode':
                if (!$data->has('code') || !$data->has('sessionId')) {
                    throw new Exception('"code" or "sessionId" is not set', 0);
                }
                $params = [
                    'code' => $data['code'],
                    'sessionId' => $data['sessionId'],
                ];

                break;
            case 'sendNotify':
                $params = [
                    'mobile' => $mobile->getNumber(),
                    'region' => self::ENDPOINT_REGION,
                    'templateId' => $message->getTemplate($this),
                ];
                $params = array_merge($params, $data);

                break;
            default:
                throw new Exception(sprintf('action: %s not supported', $action));
        }

        try {
            $result = $this->post($endpoint, $params, $headers);

            if (self::SUCCESS_CODE !== $result['code']) {
                throw new Exception($result['errorMessage'], $result['code'], $result);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        return $result;
    }
}