<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\Cart;
use Orchestra\Testbench\TestCase;

trait CartAsserts
{
    public function assertItemsInCart($items, Cart $cart)
    {
        $actual = $cart->count();

        self::assertEquals($items, $cart->count(), "Expected the cart to contain {$items} items, but got {$actual}.");
    }

    public function assertRowsInCart($rows, Cart $cart)
    {
        $actual = $cart->content()->count();

        self::assertCount($rows, $cart->content(), "Expected the cart to contain {$rows} rows, but got {$actual}.");
    }
}
