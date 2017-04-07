<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\CartContract;
use DarthSoup\Cart\Exceptions\InstanceException;
use DarthSoup\Cart\Exceptions\InvalidRowIDException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

class Cart implements CartContract
{
    /**
     * Session class instance.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * Event class instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $event;

    /**
     * Current cart instance.
     *
     * @var string
     */
    protected $instance;

    /**
     * Constructor.
     *
     * @param \Illuminate\Session\SessionManager $session
     * @param \Illuminate\Contracts\Events\Dispatcher $event
     */
    public function __construct(SessionManager $session, Dispatcher $event)
    {
        $this->session = $session;
        $this->event = $event;

        $this->instance = 'main';
    }

    /**
     * Get the current cart instance.
     *
     * @return string
     */
    protected function getInstance()
    {
        return 'cart.' . $this->instance;
    }

    /**
     * Set the current cart instance.
     *
     * @param string $instance Cart instance name
     *
     * @return \DarthSoup\Cart\Cart
     * @throws \DarthSoup\Cart\Exceptions\InstanceException
     */
    public function instance($instance = null)
    {
        if (empty($instance)) {
            throw new InstanceException();
        }
        $this->instance = $instance;

        // Return self so the method is chainable
        return $this;
    }

    /**
     * Set the associated model.
     *
     * @param string $rowId
     * @param string $model
     * @return Cart
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function associate($rowId, $model)
    {
        if (!class_exists($model)) {
            throw new ModelNotFoundException("The supplied model {$model} does not exist.");
        }
        $cartItem = $this->get($rowId);
        $cartItem->associate($model);

        $content = $this->getContent();
        $content->put($cartItem->rowId, $cartItem);

        $this->updateInstance($content);

        return $this;
    }

    /**
     * Add a row to the cart.
     *
     * @param string|array $id Unique ID of the item|Item formatted as array|Array of items
     * @param string $name Name of the item
     * @param int $quantity Item qty to add to the cart
     * @param float $price Price of one item
     * @param array $options Array of additional options, such as 'size' or 'color'
     *
     * @return \DarthSoup\Cart\Item
     * @throws \InvalidArgumentException
     */
    public function add($id, $name = null, $quantity = null, $price = null, array $options = [])
    {
        // If the first parameter is an array we need to call the add() function again
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }

        $item = $this->addItem($id, $name, $quantity, $price, $options);

        $cart = $this->getContent();

        // increase quantity
        if ($cart->has($item->rowId)) {
            $item->quantity += $cart->get($item->rowId)->quantity;
        }

        // Insert item to Cart
        $cart->put($item->rowId, $item);

        $this->event->fire('cart.added', $item);

        $this->updateInstance($cart);

        return $item;
    }

    /**
     * @param $id
     * @param $name
     * @param $quantity
     * @param $price
     * @param array $options
     * @return Item
     * @throws \InvalidArgumentException
     */
    protected function addItem($id, $name, $quantity, $price, array $options = [])
    {
        if (is_array($id)) {
            $cartItem = Item::fromArray($id);
            $cartItem->setQuantity($id['quantity']);
        } else {
            $cartItem = Item::fromAttributes($id, $name, $price, $options);
            $cartItem->setQuantity($quantity);
        }

        $cartItem->setTaxRate(config('cart.tax', 19));

        return $cartItem;
    }

    /**
     * Update the quantity of one row of the cart.
     *
     * @param string $rowId The rowid of the item you want to update
     * @param int|array $attribute New quantity of the item|Array of attributes to update
     *
     * @throws \DarthSoup\Cart\Exceptions\InvalidRowIDException
     */
    public function update($rowId, $attribute)
    {
        $item = $this->get($rowId);

        $cart = $this->getContent();

        $cart->forget($item->rowId);

        // Fire the cart.updated event
        $this->event->fire('cart.updated', $rowId);

        $this->updateInstance($cart);
    }

    /**
     * Remove a row from the cart.
     *
     * @param string $rowId The rowid of the item
     *
     * @return bool
     */
    public function remove($rowId)
    {
        $item = $this->get($rowId);

        $cart = $this->getContent();

        $cart->forget($item->rowId);

        // Fire the cart.removed event
        $this->event->fire('cart.removed', $rowId);

        $this->updateInstance($cart);
    }

    /**
     * Get a item of the cart by its ID.
     *
     * @param string $rowId
     *
     * @return Item
     */
    public function get($rowId)
    {
        $cart = $this->getContent();

        if (!$cart->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }
        return $cart->get($rowId);
    }

    /**
     * Check if a rowId exists in the current cart instance.
     *
     * @param $rowId
     * @return bool
     */
    protected function has($rowId)
    {
        return $this->getContent()->has($rowId);
    }

    /**
     * Get the cart content.
     *
     * @return Collection
     */
    public function content()
    {
        $cart = $this->getContent();

        if (!empty($cart)) {
            return $cart;
        } else {
            return null;
        }
    }

    /**
     * Empty the cart.
     *
     * @return bool
     */
    public function destroy()
    {
        $result = $this->updateInstance(null);

        // Fire the cart.destroyed event
        $this->event->fire('cart.destroyed');

        return $result;
    }

    /**
     * Get the price total.
     *
     * @return float
     */
    public function total()
    {
        $total = 0;
        $cart = $this->getContent();

        if (empty($cart)) {
            return $total;
        }

        foreach ($cart as $row) {
            $total += $row->subtotal;
        }

        return $total;
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int
     */
    public function count()
    {
        return $this->getContent()->sum('quantity');
    }

    /**
     * Search if the cart has a item.
     *
     * @param \Closure $search
     * @return \Illuminate\Support\Collection
     */
    public function search(\Closure $search)
    {
        $cart = $this->getContent();

        return $cart->filter($search);
    }


    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return Collection
     */
    protected function getContent()
    {
        $cart = $this->session->has($this->getInstance())
            ? $this->session->get($this->getInstance())
            : new Collection();

        return $cart;
    }

    /**
     * Update the cart.
     *
     * @param Collection $cart The new cart content
     */
    protected function updateInstance($cart)
    {
        return $this->session->put($this->getInstance(), $cart);
    }

    /**
     * Check if the array is a multidimensional array.
     *
     * @param array $id The array to check
     *
     * @return bool
     */
    protected function isMulti($id)
    {
        if (!is_array($id)) return false;

        return is_array(head($id));
    }

    /**
     * Check if Collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getContent()->isEmpty();
    }
}
