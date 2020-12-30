<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\Hasher as HashContract;
use InvalidArgumentException;

class HashFactory
{
    /**
     * @param string $hasher
     * @return HashContract
     */
    public function make(string $hasher): HashContract
    {
        switch ($hasher) {
            case 'md5':
                return new Hasher\Md5();
            case 'uuid':
                return new Hasher\Uuid();
            case 'randomstring':
                return new Hasher\RandomString();
        }

        throw new InvalidArgumentException("Unsupported hasher method [$hasher]");
    }
}
