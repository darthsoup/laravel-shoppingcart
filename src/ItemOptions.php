<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

/**
 * Class ItemOptions
 */
class ItemOptions extends Collection
{
    /**
     * @param string $arg
     * @return mixed
     */
    public function __get($arg)
    {
        return $this->get($arg);
    }
}
