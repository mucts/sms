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


use MuCTS\SMS\Config\Config;

interface Message
{
    public const TEXT_MESSAGE = 'text';

    public const VOICE_MESSAGE = 'voice';

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getMessageType(): string;

    /**
     * Return message content.
     *
     * @param Gateway|null $gateway
     *
     * @return string
     */
    public function getContent(?Gateway $gateway = null): string;

    /**
     * Return the template id of message.
     *
     * @param Gateway|null $gateway
     *
     * @return string
     */
    public function getTemplate(?Gateway $gateway = null): ?string;

    /**
     * Return the template data of message.
     *
     * @param Gateway|null $gateway
     *
     * @return Config
     */
    public function getData(?Gateway $gateway = null): Config;

    /**
     * Return message supported gateways.
     *
     * @return array
     */
    public function getGateways(): array;
}