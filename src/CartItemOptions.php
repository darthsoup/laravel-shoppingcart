<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

class CartItemOptions extends Collection
{
    public function __get($arg)
    {
        return $this->get($arg);
    }
}
