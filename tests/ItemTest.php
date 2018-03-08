<?php

namespace DarthSoup\Cart\Tests;

use DarthSoup\Cart\Item;
use Orchestra\Testbench\TestCase;
use DarthSoup\Cart\CartServiceProvider;

/**
 * Class CartTest.
 *
 * @author Kevin Krummnacker <kk@dogado.de>
 */
class ItemTest extends TestCase
{
    /**
     * Set the package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [CartServiceProvider::class];
    }

    /** @test */
    public function item_cast_to_an_array()
    {
        $item = new Item(1, 'Hamburger', 10.00, ['extra' => 'Onions']);
        $item->setQuantity(2);

        $created_at = $item->created_at->getTimestamp();
        $updated_at = $item->updated_at->getTimestamp();

        $this->assertEquals([
            'rowId' => '3bd1c331bfc795907057ba8bbf064034',
            'id' => 1,
            'name' => 'Hamburger',
            'quantity' => 2,
            'price' => 10.0,
            'options' => [
                'extra' => 'Onions',
            ],
            'tax' => 0.0,
            'subtotal' => 20.0,
            'subItems' => [],
            'model' => null,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ], $item->toArray());
    }

    /** @test */
    public function item_print_the_price_with_tax()
    {
        $item = new Item(1, 'Hamburger', 10.00, ['extra' => 'Onions']);
        $item->setQuantity(1);
        $item->setTaxRate(19);

        $this->assertEquals($item->priceTax, 11.9);
    }
}
