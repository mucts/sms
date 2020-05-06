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

class UCloud extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'https://api.ucloud.cn';
    /** @var string 请求操作 */
    protected const ENDPOINT_ACTION = 'SendUSMSMessage';
    /** @var int 成功状态码 */
    protected const SUCCESS_CODE = 0;

    /**
     * Build Params.
     *
     * @param Mobile $mobile
     * @param Message $message
     * @return array
     */
    protected function buildParams(Mobile $mobile, Message $message)
    {
        $data = $message->getData($this);
        $params = [
            'Action' => self::ENDPOINT_ACTION,
            'SigContent' => $this->getConfig()->get('sig_content'),
            'TemplateId' => $message->getTemplate($this),
            'PublicKey' => $this->getConfig()->get('public_key'),
        ];
        $code = isset($data['code']) ? $data['code'] : '';
        if (is_array($code) && !empty($code)) {
            foreach ($code as $key => $value) {
                $params['TemplateParams.' . $key] = $value;
            }
        } else {
            if (!empty($code) || !is_null($code)) {
                $params['TemplateParams.0'] = $code;
            }
        }

        $mobiles = isset($data['mobiles']) ? $data['mobiles'] : '';
        if (!empty($mobiles) && !is_null($mobiles)) {
            if (is_array($mobiles)) {
                foreach ($mobiles as $key => $value) {
                    $params['PhoneNumbers.' . $key] = $value;
                }
            } else {
                $params['PhoneNumbers.0'] = $mobiles;
            }
        } else {
            $params['PhoneNumbers.0'] = $mobile->getNumber();
        }
        if (!is_null($this->getConfig()->get('project_id')) && !empty($this->getConfig()->get('project_id'))) {
            $params['ProjectId'] = $this->getConfig()->get('project_id');
        }
        $signature = $this->getSignature($params, $this->getConfig()->get('private_key'));
        $params['Signature'] = $signature;
        return $params;
    }

    /**
     * Generate Sign.
     *
     * @param array $params
     * @param string $privateKey
     *
     * @return string
     */
    protected function getSignature($params, $privateKey)
    {
        ksort($params);
        $paramsData = '';
        foreach ($params as $key => $value) {
            $paramsData .= $key;
            $paramsData .= $value;
        }
        $paramsData .= $privateKey;
        return sha1($paramsData);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $params = $this->buildParams($mobile, $message);
        $result = $this->get(self::ENDPOINT_URL, $params);
        if (self::SUCCESS_CODE != $result['RetCode']) {
            throw new Exception($result['Message'], $result['RetCode'], $result);
        }
        return $result;
    }
}