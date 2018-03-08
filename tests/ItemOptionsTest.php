<?php

namespace DarthSoup\Cart\Tests;

use DarthSoup\Cart\Item;
use Orchestra\Testbench\TestCase;
use DarthSoup\Cart\CartServiceProvider;

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

    /** @test */
    public function print_an_value_from_option_with__get()
    {
        $extras = ['Onions', 'Bacon'];

        $item = new Item(1, 'Hamburger', 10.00, [
            'extra' => $extras,
        ]);

        $this->assertEquals($item->options->extra, $extras);
    }
}
