<?php

namespace DarthSoup\Cart\Hashes;

use Illuminate\Support\Arr;
use DarthSoup\Cart\Contracts\HashContract;

/**
 * Hashes Item with MD5.
 */
class Md5 implements HashContract
{
    /**
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function hash($id, array $parameters): string
    {
        return md5($id . serialize(Arr::sortRecursive($parameters)));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'md5';
    }
}
