<?php

namespace DarthSoup\Cart\Contracts;

interface Purchasable
{
    public function getId(): int;

    public function getName(array $options = null): string;

    public function getDescription(array $options = null): string;

    public function getPrice(array $options = null): float;
}
