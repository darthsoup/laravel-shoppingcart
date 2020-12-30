<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\Cart;
use DarthSoup\Cart\CartServiceProvider;
use DarthSoup\Cart\Item;
use Orchestra\Testbench\TestCase;

class CartTest extends TestCase
{
    use CartAsserts;

    const SECOND_INSTANCE = 'other';

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

    public function testHasDefaultInstance()
    {
        $cart = $this->buildCart();

        $this->assertEquals(Cart::DEFAULT_INSTANCE, $cart->getCurrentInstance());
    }

    public function testCanHaveMultipleInstances()
    {
        $cart = $this->buildCart();

        $cart->add(new Item('1', 'Foo'));

        $cart->instance(self::SECOND_INSTANCE)
            ->add(new Item('2', 'Bar'));

        $this->assertItemsInCart(1, $cart->instance(Cart::DEFAULT_INSTANCE));
        $this->assertItemsInCart(1, $cart->instance(self::SECOND_INSTANCE));
    }

    public function testAddItemByClass()
    {
        $cart = $this->buildCart();

        $cart->add(new Item('1', 'Foo'));

        $this->assertEquals(1, $cart->count());
    }

    public function testAddItemByArray()
    {
        $cart = $this->buildCart();

        $cart->add([
            'id' => '1',
            'name' => 'foobar'
        ]);

        $this->assertEquals(1, $cart->count());
    }

    public function testAddItemByAttributes()
    {
        $cart = $this->buildCart();

        $cart->add('1', 'foobar');

        $this->assertEquals(1, $cart->count());
    }
}
