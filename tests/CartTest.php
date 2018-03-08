<?php

namespace DarthSoup\Cart\Tests;

use DarthSoup\Cart\Cart;
use Orchestra\Testbench\TestCase;
use DarthSoup\Cart\CartServiceProvider;

/**
 * Class CartTest.
 *
 * @author Kevin Krummnacker <kk@dogado.de>
 */
class CartTest extends TestCase
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

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('session.driver', 'array');
    }

    /** @test */
    public function it_has_a_default_instance()
    {
        $cart = $this->buildCart();

        $this->assertEquals(Cart::DEFAULT_INSTANCE, $cart->getCurrentInstance());
    }

    /**
     * Get an cart instance.
     *
     * @return \DarthSoup\Cart\Cart
     */
    private function buildCart()
    {
        return new Cart(
            $this->app->make('session'),
            $this->app->make('events'),
            $this->app->make('cart.hash')
        );
    }
}
