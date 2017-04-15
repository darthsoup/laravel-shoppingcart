<?php

namespace DarthSoup\Cart\Contracts;


interface HashContract
{
    public function getName(): string;

    public function hash(): string;
}