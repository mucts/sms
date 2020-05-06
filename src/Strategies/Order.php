<?php
/**
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\SMS\Strategies;


use MuCTS\SMS\Interfaces\Strategy;

class Order implements Strategy
{

    public function apply(array $gateways): array
    {
        return array_keys($gateways);
    }
}