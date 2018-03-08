<?php

namespace DarthSoup\Cart\Contracts;

interface HashContract
{
    /**
     * @param $id
     * @param array $parameters
     * @return string
     */
    public function hash($id, array $parameters): string;

    /**
     * @return string
     */
    public function getName(): string;
}
