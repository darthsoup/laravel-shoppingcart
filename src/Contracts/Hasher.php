<?php

namespace DarthSoup\Cart\Contracts;

interface Hasher
{
    /**
     * @param $id
     * @param array $parameters
     * @return string
     */
    public function make($id, array $parameters): string;

    /**
     * @return string
     */
    public function getName(): string;
}
