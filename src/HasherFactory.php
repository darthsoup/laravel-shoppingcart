<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\Hasher as HashContract;

class HasherFactory
{
    /**
     * @return HashContract
     */
    public function create(array $config)
    {
        if (!isset($config['hasher'])) {
            throw new \InvalidArgumentException('A hasher must be specified.');
        }

        switch ($config['hasher']) {
            case 'md5':
                return new Hasher\Md5();
            case 'uuid':
                return new Hasher\Uuid();
            case 'randomstring':
                return new Hasher\RandomString();
        }

        throw new \InvalidArgumentException('Unsupported Hasher ' . $config['hasher']);
    }
}
