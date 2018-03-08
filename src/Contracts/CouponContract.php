<?php

namespace DarthSoup\Cart\Contracts;

interface CouponContract
{
    /**
     * @param string $code
     * @param float $value
     */
    public function __construct(string $code, float $value);
}
