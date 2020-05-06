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

namespace MuCTS\SMS;

use Exception;
use Throwable;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;

class Messenger
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    /** @var  SMS */
    protected SMS $sms;

    /**
     * Messenger constructor.
     * @param SMS $sms
     */
    public function __construct(SMS $sms)
    {
        $this->sms = $sms;
    }

    /**
     * 消息发送
     *
     * @param Mobile $mobile
     * @param Message $message
     * @param array $gateways
     * @return array
     * @throws Exception
     */
    public function send(Message $message, Mobile $mobile, array $gateways = []): array
    {
        $results = [];
        $isSuccessful = false;

        foreach ($gateways as $gateway => $config) {
            try {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_SUCCESS,
                    'result' => $this->sms->gateway($gateway)->send($message, $mobile, $config),
                ];
                $isSuccessful = true;

                break;
            } catch (Exception $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_FAILURE,
                    'exception' => $e,
                ];
            } catch (Throwable $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_FAILURE,
                    'exception' => $e,
                ];
            }
        }

        if (!$isSuccessful) {
            throw new Exception($results);
        }

        return $results;
    }
}