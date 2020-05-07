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
 * @see https://help.aliyun.com/document_detail/55451.html
 */

namespace MuCTS\SMS\Gateways;


use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;
use Exception;

class AliYun extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求接口地址 */
    protected const ENDPOINT_URL = 'http://dysmsapi.aliyuncs.com';
    /** @var string API名称 */
    protected const ENDPOINT_METHOD = 'SendSms';
    /** @var string API 的版本号，格式为 YYYY-MM-DD。取值范围：2017-05-25 */
    protected const ENDPOINT_VERSION = '2017-05-25';
    /** @var string 返回参数的语言类型。取值范围：json | xml。默认值：json */
    protected const ENDPOINT_FORMAT = 'JSON';
    /** @var string API支持的RegionID，如短信API的值为：cn-hangzhou */
    protected const ENDPOINT_REGION_ID = 'cn-hangzhou';
    /** @var string 签名方式。取值范围：HMAC-SHA1 */
    protected const ENDPOINT_SIGNATURE_METHOD = 'HMAC-SHA1';
    /** @var string 签名算法版本。取值范围：1.0 */
    protected const ENDPOINT_SIGNATURE_VERSION = '1.0';

    /**
     * @inheritDoc
     */
    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if($config) $this->setConfig($config);
        $data = $message->getData($this);
        $signName = $data->pull('sign_name');
        $params = [
                'PhoneNumbers' => !is_null($mobile->getIDDCode()) ? strval($mobile->getZeroPrefixedNumber()) : $mobile->getNumber(),
                'TemplateCode' => $message->getTemplate($this),
                'TemplateParam' => json_encode($data->all(), JSON_FORCE_OBJECT),
            ] + $this->getCommon($signName);

        $params['Signature'] = $this->sign($params);

        $result = $this->get(self::ENDPOINT_URL, $params);

        if ('OK' != $result['Code']) {
            throw new Exception($result['Message'], $result['Code'], $result);
        }

        return $result;
    }

    protected function getCommon(?string $signName = null): array
    {
        return [
            'RegionId' => self::ENDPOINT_REGION_ID,
            'AccessKeyId' => $this->getConfig()->get('access_key_id'),
            'Format' => self::ENDPOINT_FORMAT,
            'SignatureMethod' => self::ENDPOINT_SIGNATURE_METHOD,
            'SignatureVersion' => self::ENDPOINT_SIGNATURE_VERSION,
            'SignatureNonce' => uniqid(),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Action' => self::ENDPOINT_METHOD,
            'Version' => self::ENDPOINT_VERSION,
            'SignName' => $signName ?? $this->getConfig()->get('sign_name')
        ];
    }

    /**
     * 生成签名
     *
     * @param array $params
     * @return string
     */
    protected function sign(array $params): string
    {
        ksort($params);
        $accessKeySecret = $this->getConfig()->get('access_key_secret');
        $stringToSign = 'GET&%2F&' . urlencode(http_build_query($params, null, '&', PHP_QUERY_RFC3986));
        return base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
    }
}