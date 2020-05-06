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

class ErrorLog extends Gateway
{
    public function send(Message $message, Mobile $mobile, ?Config $config = null): array
    {
        if (is_array($mobile)) {
            $mobile = implode(',', $mobile);
        }
        $message = sprintf(
            "[%s] to: %s | message: \"%s\"  | template: \"%s\" | data: %s\n",
            date('Y-m-d H:i:s'),
            $mobile,
            $message->getContent($this),
            $message->getTemplate($this),
            json_encode($message->getData($this))
        );
        $file = $this->config->get('file', ini_get('error_log'));
        $status = error_log($message, 3, $file);
        return compact('status', 'file');
    }
}