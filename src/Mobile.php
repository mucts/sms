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

use MuCTS\SMS\Interfaces\Mobile as MobileInterface;

class Mobile implements MobileInterface
{
    /** @var int */
    protected $number;

    /** @var int|null */
    protected $IDDCode;

    /**
     * PhoneNumberInterface constructor.
     *
     * @param int $numberWithoutIDDCode
     * @param string $IDDCode
     */
    public function __construct(int $numberWithoutIDDCode, string $IDDCode = null)
    {
        $this->number = $numberWithoutIDDCode;
        $this->IDDCode = $IDDCode ? intval(ltrim($IDDCode, '+0')) : null;
    }

    /**
     * 86.
     *
     * @return int
     */
    public function getIDDCode(): int
    {
        return $this->IDDCode;
    }

    /**
     * 18888888888.
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * +8618888888888.
     *
     * @return string
     */
    public function getUniversalNumber(): string
    {
        return $this->getPrefixedIDDCode('+') . $this->number;
    }

    /**
     * 008618888888888.
     *
     * @return string
     */
    public function getZeroPrefixedNumber(): string
    {
        return $this->getPrefixedIDDCode('00') . $this->number;
    }

    /**
     * @param string $prefix
     *
     * @return string|null
     */
    public function getPrefixedIDDCode(string $prefix): ?string
    {
        return $this->IDDCode ? $prefix . $this->IDDCode : null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUniversalNumber();
    }

    /**
     * Specify data which should be serialized to JSON.
     * @return string
     */
    public function jsonSerialize(): ?string
    {
        return $this->getUniversalNumber();
    }
}