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


interface Mobile
{
    /**
     * 86.
     *
     * @return int
     */
    public function getIDDCode(): int;

    /**
     * 18888888888.
     *
     * @return int
     */
    public function getNumber(): int;

    /**
     * +8618888888888.
     *
     * @return string
     */
    public function getUniversalNumber(): string;

    /**
     * 008618888888888.
     *
     * @return string
     */
    public function getZeroPrefixedNumber(): string;

    /**
     * @return string
     */
    public function __toString(): string;
}