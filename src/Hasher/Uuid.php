<?php

namespace DarthSoup\Cart\Hasher;

use DarthSoup\Cart\Contracts\Hasher as HashContract;
use Ramsey\Uuid\Uuid as UuidFactory;

class Uuid implements HashContract
{
    /**
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function make($id, array $parameters): string
    {
        return UuidFactory::uuid4();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'uuid';
    }
}
