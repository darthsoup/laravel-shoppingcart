<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

/**
 * @package DarthSoup\Cart
 */
class CartItemOptions extends Collection
{
    public function __get($arg)
    {
        return $this->get($arg);
    }
}