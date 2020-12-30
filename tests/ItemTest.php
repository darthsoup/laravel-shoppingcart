<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\CartServiceProvider;
use DarthSoup\Cart\Item;
use Orchestra\Testbench\TestCase;

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

    public function testItemCastToAnArray()
    {
        $item = new Item(1, 'Hamburger', 10.00, ['extra' => 'Onions']);
        $item->setQuantity(2);

        $createdAt = $item->created_at->getTimestamp();
        $updatedAt = $item->updated_at->getTimestamp();

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
            'priceTax' => 10.0,
            'subtotal' => 20.0,
            'subItems' => [],
            'model' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ], $item->toArray());
    }

    public function testItemPrintThePriceWithTax()
    {
        $item = new Item(1, 'Hamburger', 10.00, ['extra' => 'Onions']);
        $item->setQuantity(1);
        $item->setTaxRate(19);

        $this->assertEquals($item->priceTax, 11.9);
    }
}
