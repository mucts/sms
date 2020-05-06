<?php
/**
 * This file is part of the mucts/sms.
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

class AliYunRest extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'http://gw.api.taobao.com/router/rest';
    /** @var string 短信版本 */
    protected const ENDPOINT_VERSION = '2.0';
    /** @var string 响应格式 */
    protected const ENDPOINT_FORMAT = 'json';
    /** @var string 短信端口 */
    protected const ENDPOINT_METHOD = 'alibaba.aliqin.fc.sms.num.send';
    /** @var string 加密方式 */
    protected const ENDPOINT_SIGNATURE_METHOD = 'md5';
    /** @var string */
    protected const ENDPOINT_PARTNER_ID = 'EasySms';
    /** @var Config */
    private Config $config;

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        $data = $message->getData($this);
        $signName = $data->pull('sign_name', $this->getConfig()->get('sign_name'));
        if ($config instanceof Config) {
            $this->setConfig($config);
        }
        $params = [
            'extend' => '',
            'sms_type' => 'normal',
            'sms_free_sign_name' => $signName,
            'sms_param' => json_encode($message->getData($this)),
            'rec_num' => !is_null($mobile->getIDDCode()) ? strval($mobile->getZeroPrefixedNumber()) : $mobile->getNumber(),
            'sms_template_code' => $message->getTemplate($this),
        ];
        $urlParams = $this->getCommon();
        $urlParams['sign'] = $this->sign(array_merge($params, $urlParams));
        $result = $this->post($this->getEndpointUrl($urlParams), $params);

        if (isset($result['error_response']) && 0 != $result['error_response']['code']) {
            throw new Exception($result['error_response']['msg'], $result['error_response']['code'], $result);
        }

        return $result;
    }

    public function getCommon(): array
    {
        return [
            'app_key' => $this->getConfig()->get('app_key'),
            'v' => self::ENDPOINT_VERSION,
            'format' => self::ENDPOINT_FORMAT,
            'sign_method' => self::ENDPOINT_SIGNATURE_METHOD,
            'method' => self::ENDPOINT_METHOD,
            'timestamp' => date('Y-m-d H:i:s'),
            'partner_id' => self::ENDPOINT_PARTNER_ID,
        ];
    }


    /**
     * @param array $params
     *
     * @return string
     */
    protected function getEndpointUrl($params): string
    {
        return self::ENDPOINT_URL . '?' . http_build_query($params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function sign($params): string
    {
        ksort($params);
        $stringToBeSigned = $this->getConfig()->get('app_secret_key');
        foreach ($params as $k => $v) {
            if (!is_array($v) && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->getConfig()->get('app_secret_key');
        return strtoupper(md5($stringToBeSigned));
    }
}