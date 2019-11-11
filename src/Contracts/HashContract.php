<?php

namespace DarthSoup\Cart\Contracts;

/**
 * Interface HashContract
 */
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
