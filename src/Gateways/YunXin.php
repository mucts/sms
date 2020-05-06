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
 * @see https://dev.yunxin.163.com/docs/product/%E7%9F%AD%E4%BF%A1/%E7%9F%AD%E4%BF%A1%E6%8E%A5%E5%8F%A3%E6%8C%87%E5%8D%97
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class YunXin extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_TEMPLATE = 'https://api.netease.im/%s/%s.action';
    /** @var string 请求接口或端口 */
    protected const ENDPOINT_ACTION = 'sendCode';
    /** @var int 成功状态码 */
    protected const SUCCESS_CODE = 200;

    /**
     * @param $resource
     * @param $function
     *
     * @return string
     */
    protected function buildEndpoint(string $resource, string $function): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $resource, strtolower($function));
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    protected function buildHeaders()
    {
        $headers = [
            'AppKey' => $this->getConfig()->get('app_key'),
            'Nonce' => md5(uniqid('easysms')),
            'CurTime' => (string)time(),
            'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
        ];
        $headers['CheckSum'] = sha1("{$this->getConfig()->get('app_secret')}{$headers['Nonce']}{$headers['CurTime']}");
        return $headers;
    }

    public function buildSendCodeParams(Mobile $mobile, Message $message): array
    {
        $data = $message->getData($this);
        $template = $message->getTemplate($this);
        return [
            'mobile' => $mobile->getUniversalNumber(),
            'authCode' => $data->get('code', ''),
            'deviceId' => $data->get('device_id', ''),
            'templateid' => is_string($template) ? $template : '',
            'codeLen' => $this->getConfig()->get('code_length', 4),
            'needUp' => $this->getConfig()->get('need_up', false),
        ];
    }

    /**
     * @param Mobile $mobile
     * @param Message $message
     * @return array
     * @throws Exception
     */
    public function buildVerifyCodeParams(Mobile $mobile, Message $message): array
    {
        $data = $message->getData($this);
        if (!$data->has('code')) {
            throw new Exception('"code" cannot be empty', 0);
        }
        return [
            'mobile' => $mobile->getUniversalNumber(),
            'code' => $data['code'],
        ];
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $data = $message->getData($this);
        $action = isset($data['action']) ? $data['action'] : self::ENDPOINT_ACTION;
        $endpoint = $this->buildEndpoint('sms', $action);
        switch ($action) {
            case 'sendCode':
                $params = $this->buildSendCodeParams($mobile, $message);
                break;
            case 'verifyCode':
                $params = $this->buildVerifyCodeParams($mobile, $message);
                break;
            default:
                throw new Exception(sprintf('action: %s not supported', $action), 0);
        }
        $headers = $this->buildHeaders();
        try {
            $result = $this->post($endpoint, $params, $headers);
            if (!isset($result['code']) || self::SUCCESS_CODE !== $result['code']) {
                $code = isset($result['code']) ? $result['code'] : 0;
                $error = isset($result['msg']) ? $result['msg'] : json_encode($result, JSON_UNESCAPED_UNICODE);
                throw new Exception($error, $code);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        return $result;
    }
}