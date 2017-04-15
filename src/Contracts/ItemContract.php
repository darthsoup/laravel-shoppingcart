<?php

namespace DarthSoup\Cart\Contracts;

/**
 * Interface ItemContract
 * @package DarthSoup\Cart\Contracts
 */
interface ItemContract
{
    /**
     * Return item identifier
     *
     * @return string
     */
    public function getRowId();
}