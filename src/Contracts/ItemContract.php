<?php

namespace DarthSoup\Cart\Contracts;

interface ItemContract
{
    /**
     * Return item identifier.
     */
    public function getRowId(): string;
}
