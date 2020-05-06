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
 * @see http://www.kingtto.cn/
 */

namespace MuCTS\SMS\Gateways;


use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Message;
use MuCTS\SMS\Interfaces\Mobile;
use MuCTS\SMS\Traits\HasHttpRequest;

class KingTTO extends Gateway
{
    use HasHttpRequest;

    /** @var string 请求地址 */
    protected const ENDPOINT_URL = 'http://101.201.41.194:9999/sms.aspx';
    /** @var string 请求端口 */
    protected const ENDPOINT_METHOD = 'send';

    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if ($config instanceof Config) $this->setConfig($config);
        $params = [
            'action' => self::ENDPOINT_METHOD,
            'userid' => $this->getConfig()->get('userid'),
            'account' => $this->getConfig()->get('account'),
            'password' => $this->getConfig()->get('password'),
            'mobile' => $mobile->getNumber(),
            'content' => $message->getContent(),
        ];

        $result = $this->post(self::ENDPOINT_URL, $params);
        if ('Success' != $result['returnstatus']) {
            throw new Exception($result['message'], $result['remainpoint'], $result);
        }
        return $result;
    }
}