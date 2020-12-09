<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Hashes\Md5;
use DarthSoup\Cart\Hashes\Uuid;
use DarthSoup\Cart\Hashes\RandomString;

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
                return new Md5();
            case 'uuid':
                return new Uuid();
            case 'randomstring':
                return new RandomString();
        }

        throw new \InvalidArgumentException('Unsupported Hasher ' . $config['hasher']);
    }
}
