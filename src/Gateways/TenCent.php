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
 * @see https://cloud.tencent.com/document/product/382/13297
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class TenCent extends Gateway
{
    use HasHttpRequest;
    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'https://yun.tim.qq.com/v5/';
    /** @var string 方法端口 */
    protected const ENDPOINT_METHOD = 'tlssmssvr/sendsms';
    /** @var string 版本 */
    protected const ENDPOINT_VERSION = 'v5';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';

    /**
     * Generate Sign.
     *
     * @param array $params
     * @param string $random
     *
     * @return string
     */
    protected function sign(array $params, string $random)
    {
        ksort($params);
        return hash('sha256', sprintf(
            'appkey=%s&random=%s&time=%s&mobile=%s',
            $this->getConfig()->get('app_key'),
            $random,
            $params['time'],
            $params['tel']['mobile']
        ), false);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if($config) $this->setConfig($config);
        $data = $message->getData($this);
        $signName = $data->pull('sign_name', $this->getConfig()->get('sign_name'));
        $msg = $message->getContent($this);
        if (!empty($msg) && '【' != mb_substr($msg, 0, 1) && !empty($signName)) {
            $msg = '【' . $signName . '】' . $msg;
        }
        $params = [
            'tel' => [
                'nationcode' => $mobile->getIDDCode() ?: 86,
                'mobile' => $mobile->getNumber(),
            ],
            'type' => $data->get('type', 0),
            'msg' => $msg,
            'time' => time(),
            'extend' => '',
            'ext' => '',
        ];
        if (!is_null($message->getTemplate($this)) && $data) {
            unset($params['msg']);
            $params['params'] = $data->values()->all();
            $params['tpl_id'] = $message->getTemplate($this);
            $params['sign'] = $signName;
        }
        $random = substr(uniqid(), -10);
        $params['sig'] = $this->sign($params, $random);
        $url = self::ENDPOINT_URL . self::ENDPOINT_METHOD . '?sdkappid=' . $this->getConfig()->get('sdk_app_id') . '&random=' . $random;
        $result = $this->request('post', $url, [
            'headers' => ['Accept' => 'application/json'],
            'json' => $params,
        ]);
        if (0 != $result['result']) {
            throw new Exception($result['errmsg'], $result['result'], $result);
        }
        return $result;
    }
}