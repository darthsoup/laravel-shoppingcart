<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\Cart;
use DarthSoup\Cart\CartCollection;
use DarthSoup\Cart\CartServiceProvider;
use DarthSoup\Cart\Exceptions\InvalidRowIdException;
use DarthSoup\Cart\Item;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use TypeError;

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
            $this->app->make('cart.hashfactory')
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

        $cart->add(['id' => '1', 'name' => 'foobar']);

        $this->assertEquals(1, $cart->count());
    }

    public function testAddItemByAttributes()
    {
        $cart = $this->buildCart();

        $cart->add('1', 'foobar');

        $this->assertEquals(1, $cart->count());
    }

    public function testCartContentInstance()
    {
        $cart = $this->buildCart();

        $content = $cart->content();

        $this->assertInstanceOf(CartCollection::class, $content);
    }

    public function testRemoveIfQuantityIsNull()
    {
        Event::fake();

        $cart = $this->buildCart();

        $item = new Item('1', 'Foo', 2.99, ['foo' => 'bar']);

        $cart->add($item);
        $cart->update($item->getRowId(), 0);

        $this->assertItemsInCart(0, $cart);

        Event::assertDispatched('cart.removed');
    }

    public function testExceptionNonExistentItem()
    {
        $this->expectException(InvalidRowIdException::class);

        $cart = $this->buildCart();

        $cart->add(new Item('1', 'Foo', 2.99, ['foo' => 'bar']));
        $cart->get('SomeFooBarRowId');
    }

    public function testAddSubitem()
    {
        Event::fake();
        $cart = $this->buildCart();

        [$item, $subitem] = $this->buildFakeWithSubitem($cart);

        $this->assertItemsInCart(2, $cart);
        $this->assertTrue($cart->get($item->getRowId())->hasSubItems());

        Event::assertDispatched('cart.subitem.added');
    }

    public function testDeleteSubitem()
    {
        $cart = $this->buildCart();

        [$item, $subitem] = $this->buildFakeWithSubitem($cart);
        $cart->remove($subitem->getRowId());

        $this->assertNotTrue($cart->get($item->getRowId())->hasSubItems());
    }

    public function testInvalidItemIdError()
    {
        $this->expectException(TypeError::class);

        $cart = $this->buildCart();
        $cart->add(new Item(null, null));
    }

    private function buildFakeWithSubitem($cart)
    {
        $item = $cart->add('15', 'Hamburger', 1, 1.99, ['onion' => false]);
        $subitem = $cart->addSubItem('99', 'Extra Bacon', 1, 0.99, [], $item->getRowId());

        return [$item, $subitem];
    }
}
