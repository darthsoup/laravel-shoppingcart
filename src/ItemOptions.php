<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

class ItemOptions extends Collection
{
    /**
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->get($attribute);
    }
}
