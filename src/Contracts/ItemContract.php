<?php

namespace DarthSoup\Cart\Contracts;

/**
 * Interface ItemContract.
 */
interface ItemContract
{
    /**
     * Return item identifier.
     *
     * @return string
     */
    public function getRowId();
}
