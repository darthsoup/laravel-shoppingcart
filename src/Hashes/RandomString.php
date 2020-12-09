<?php

namespace DarthSoup\Cart\Hashes;

use DarthSoup\Cart\Contracts\HashContract;
use Illuminate\Support\Str;

/**
 * Hashes Item Id with random strings.
 */
class RandomString implements HashContract
{
    /**
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function hash($id, array $parameters): string
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
