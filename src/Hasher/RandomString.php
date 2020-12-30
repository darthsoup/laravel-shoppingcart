<?php

namespace DarthSoup\Cart\Hasher;

use DarthSoup\Cart\Contracts\Hasher as HashContract;
use Illuminate\Support\Str;

class RandomString implements HashContract
{
    /**
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function make($id, array $parameters): string
    {
        return Str::random();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'randomstring';
    }
}
