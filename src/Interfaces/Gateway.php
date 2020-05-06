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

namespace MuCTS\SMS\Interfaces;


use Exception;
use MuCTS\SMS\Config\Config;

interface Gateway
{
    /**
     * 短信发送
     *
     * @param Message $message 消息内容
     * @param Mobile $mobile 手机号
     * @param Config|null $config 配置信息
     * @return array
     * @throws Exception
     */
    public function send(Message $message, Mobile $mobile, ?Config $config = null): array;

    /**
     * 设置网关配置
     *
     * @param Config $config
     * @return $this
     */
    public function setConfig(Config $config): self;
}