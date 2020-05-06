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
 * @see http://docs.ucpaas.com/doku.php?id=%E7%9F%AD%E4%BF%A1:sendsms
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class YunZhiXun extends Gateway
{
    use HasHttpRequest;

    /** @var string 成功状态 */
    protected const SUCCESS_CODE = '000000';
    /** @var string */
    protected const FUNCTION_SEND_SMS = 'sendsms';
    /** @var string */
    protected const FUNCTION_BATCH_SEND_SMS = 'sendsms_batch';
    /** @var string */
    protected const ENDPOINT_TEMPLATE = 'https://open.ucpaas.com/ol/%s/%s';

    protected function buildEndpoint(string $resource, string $function): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $resource, $function);
    }

    protected function buildParams(Mobile $to, Message $message): array
    {
        $data = $message->getData($this);
        return [
            'sid' => $this->getConfig()->get('sid'),
            'token' => $this->getConfig()->get('token'),
            'appid' => $this->getConfig()->get('app_id'),
            'templateid' => $message->getTemplate($this),
            'uid' => isset($data['uid']) ? $data['uid'] : '',
            'param' => isset($data['params']) ? $data['params'] : '',
            'mobile' => isset($data['mobiles']) ? $data['mobiles'] : $to->getNumber(),
        ];
    }

    /**
     * @param $endpoint
     * @param $params
     * @return array
     * @throws Exception
     */
    protected function execute(string $endpoint, array $params): array
    {
        try {
            $result = $this->postJson($endpoint, $params);
            if (!isset($result['code']) || self::SUCCESS_CODE !== $result['code']) {
                $code = isset($result['code']) ? $result['code'] : 0;
                $error = isset($result['msg']) ? $result['msg'] : json_encode($result, JSON_UNESCAPED_UNICODE);
                throw new Exception($error, $code);
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $data = $message->getData($this);
        $function = $data->has('mobiles') ? self::FUNCTION_BATCH_SEND_SMS : self::FUNCTION_SEND_SMS;
        $endpoint = $this->buildEndpoint('sms', $function);
        $params = $this->buildParams($mobile, $message);
        return $this->execute($endpoint, $params);
    }
}