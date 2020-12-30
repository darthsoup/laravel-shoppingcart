<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\CartServiceProvider;
use DarthSoup\Cart\Item;
use DarthSoup\Cart\ItemOptions;
use Orchestra\Testbench\TestCase;

class ItemOptionsTest extends TestCase
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

    public function testPrintAnValueFromOptionWithGet()
    {
        $extras = ['Onions', 'Bacon'];

        $item = new Item(1, 'Hamburger', 10.00, [
            'extra' => $extras,
        ]);

        $this->assertEquals($item->options->extra, $extras);
    }

    public function testInstanceOfOptions()
    {
        $item = new Item(1, 'Hamburger');

        $this->assertInstanceOf(ItemOptions::class, $item->options);
    }
}
