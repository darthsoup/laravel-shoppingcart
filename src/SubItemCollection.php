<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

class SubItemCollection extends Collection
{
    public function __get($arg)
    {
        return $this->get($arg);
    }
}
