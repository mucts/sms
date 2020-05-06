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
 * @see https://www.twilio.com/docs/sms/send-messages
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class Twillio extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    protected array $errorStatus = [
        'failed',
        'undelivered',
    ];

    public function getName(): string
    {
        return 'twilio';
    }

    /**
     * build endpoint url.
     *
     * @param string $accountSid
     *
     * @return string
     */
    protected function buildEndPoint(string $accountSid): string
    {
        return sprintf(self::ENDPOINT_URL, $accountSid);
    }

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config) $this->setConfig($config);
        $accountSid = $this->getConfig()->get('account_sid');
        $endpoint = $this->buildEndPoint($accountSid);
        $params = [
            'To' => $mobile->getUniversalNumber(),
            'From' => $this->getConfig()->get('from'),
            'Body' => $message->getContent($this),
        ];
        try {
            $result = $this->request('post', $endpoint, [
                'auth' => [
                    $accountSid,
                    $this->getConfig()->get('token'),
                ],
                'form_params' => $params,
            ]);
            if (in_array($result['status'], $this->errorStatus) || !is_null($result['error_code'])) {
                throw new Exception($result['message'], $result['error_code'], $result);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        return $result;
    }
}