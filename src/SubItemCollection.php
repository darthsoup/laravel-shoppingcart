<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

/**
 * @package DarthSoup\Cart
 */
class SubItemCollection extends Collection
{
    public function __get($arg)
    {
        return $this->get($arg);
    }
}
