<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\CartContract;
use DarthSoup\Cart\Exceptions\InstanceException;
use DarthSoup\Cart\Exceptions\InvalidRowIdException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\SessionManager;

/**
 * Cart Class.
 */
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
     * @param \Illuminate\Session\SessionManager      $session
     * @param \Illuminate\Contracts\Events\Dispatcher $event
     */
    public function __construct(SessionManager $session, Dispatcher $event)
    {
        $this->session = $session;
        $this->event = $event;

        $this->instance('main');
    }

    /**
     * Get the current cart instance.
     *
     * @return string
     */
    protected function getInstance(): string
    {
        return 'cart.'.$this->instance;
    }

    /**
     * Return the current instance.
     *
     * @return string
     */
    public function getCurrentInstance()
    {
        return $this->instance;
    }

    /**
     * Set the current cart instance.
     *
     * @param string|null $instance
     *
     * @throws \DarthSoup\Cart\Exceptions\InstanceException
     *
     * @return \DarthSoup\Cart\Cart
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
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return Cart
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
     * Add a Item to the cart.
     *
     * @param string|array $id       Unique ID of the item|Item formatted as array|Array of items
     * @param string       $name     Name of the item
     * @param int          $quantity Item quantity to add to the cart
     * @param float        $price    Price of one item
     * @param array        $options  Array of additional options, such as 'size' or 'color'
     *
     * @return Item
     */
    public function add($id, $name = null, $quantity = null, $price = null, array $options = []): Item
    {
        // If the first parameter is an array we need to call the add() function again
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }

        $item = $this->buildItem($id, $name, $quantity, $price, $options);

        $cart = $this->getContent();

        // increase quantity
        if ($cart->has($item->getRowId())) {
            $item->quantity += $cart->get($item->rowId)->quantity;
        }

        // Insert item to Cart
        $cart->put($item->rowId, $item);

        $this->event->fire('cart.added', $item);

        $this->updateInstance($cart);

        return $item;
    }

    /**
     * Add a subItem to the cart.
     *
     * @param string|array $id       Unique ID of the item|Item formatted as array|Array of items
     * @param string       $name     Name of the item
     * @param int          $quantity Item qty to add to the cart
     * @param float        $price    Price of one item
     * @param array        $options  Array of additional options, such as 'size' or 'color'
     * @param string       $parent
     *
     * @return Item
     */
    public function addSubItem($id, $name, $quantity, $price, array $options, string $parent): Item
    {
        $parentItem = $this->get($parent);

        //if ($this->isMulti($id)) {
        //    return array_map(function ($item) {
        //        return $this->addSubItem($item);
        //    }, $id);
        //}

        $subItem = $this->buildItem($id, $name, $quantity, $price, $options);

        // Insert subitem to item
        $parentItem->addSubItem($subItem);

        $cart = $this->getContent();

        // Insert item to Cart
        $cart->put($parentItem->rowId, $parentItem);

        $this->event->fire('cart.subitem.added', [$subItem, $parentItem]);

        $this->updateInstance($cart);

        return $subItem;
    }

    /**
     * Update the quantity or attributes of one item of the cart.
     *
     * @param string    $rowId     The rowId of the item you want to update
     * @param int|array $attribute New quantity of the item|array of attributes to update
     *
     * @throws \DarthSoup\Cart\Exceptions\InvalidRowIdException
     *
     * @return Item
     */
    public function update($rowId, $attribute)
    {
        $item = $this->get($rowId);

        if (is_array($attribute)) {
            $item->updateFromArray($attribute);
        } else {
            $item->quantity = $attribute;
        }

        $cart = $this->getContent();

        if ($rowId !== $item->rowId) {
            $cart->pull($rowId);

            if ($cart->has($item->rowId)) {
                $existingCartItem = $this->get($item->rowId);
                $item->setQuantity($existingCartItem->quantity + $item->quantity);
            }
        }

        if ($item->quantity <= 0) {
            $this->remove($item->rowId);

            return;
        }

        $cart->put($item->rowId, $item);

        // Fire the cart.updated event
        $this->event->fire('cart.updated', $rowId);

        $this->updateInstance($cart);

        return $item;
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
            throw new InvalidRowIdException("The cart does not contain rowId {$rowId}.");
        }

        return $cart->get($rowId);
    }

    /**
     * Get a item of the cart by its ID.
     *
     * @param string $rowId
     *
     * @return bool
     */
    public function has($rowId): bool
    {
        return $this->getContent()->has($rowId);
    }

    /**
     * Get the cart content.
     *
     * @return CartCollection
     */
    public function content(): CartCollection
    {
        return $this->getContent();
    }

    /**
     * Empty the cart.
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->updateInstance(null);

        // Fire the cart.destroyed event
        $this->event->fire('cart.destroyed');
    }

    /**
     * Get the total price of all items with TAX.
     *
     * @return float
     */
    public function total(): float
    {
        return $this->getContent()->reduce(function ($total, Item $item) {
            return $total + ($item->quantity * $item->priceTax);
        }, 0);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @return float
     */
    public function tax(): float
    {
        return $this->getContent()->reduce(function ($tax, Item $item) {
            return $tax + ($item->quantity * $item->tax);
        }, 0);
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int
     */
    public function count() : int
    {
        return $this->getContent()->sum('quantity');
    }

    /**
     * Search if the cart has a item.
     *
     * @param \Closure $search
     *
     * @return CartCollection
     */
    public function search(\Closure $search): CartCollection
    {
        $cart = $this->getContent();

        return $cart->filter($search);
    }

    /**
     * Check if Collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->getContent()->isEmpty();
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return CartCollection
     */
    private function getContent(): CartCollection
    {
        $cart = $this->session->has($this->getInstance())
            ? $this->session->get($this->getInstance())
            : new CartCollection();

        return $cart;
    }

    /**
     * Update the cart.
     *
     * @param CartCollection $cart
     */
    private function updateInstance($cart)
    {
        return $this->session->put($this->getInstance(), $cart);
    }

    /**
     * Create a Item.
     *
     * @param Item|array|string $id
     * @param string            $name
     * @param int               $quantity
     * @param float             $price
     * @param array             $options
     *
     * @return Item
     */
    protected function buildItem($id, $name, $quantity, $price, array $options = []): Item
    {
        // 1. Insert an prepared Item directly
        // 2. Create item by array
        // 3. Create item by attributes

        if ($id instanceof Item) {
            $item = $id;
            $arguments = func_get_args();
            $arguments[0] = $item->id;
            $item->updateFromAttributes(...$arguments);
        } elseif (is_array($id)) {
            $item = Item::fromArray($id);
            $item->setQuantity($id['quantity']);
        } else {
            $item = Item::fromAttributes($id, $name, $price, $options);
            $item->setQuantity($quantity);
        }

        $item->setTaxRate(config('cart.tax', 19));

        return $item;
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
        if (!is_array($id)) {
            return false;
        }

        return is_array(head($id));
    }
}
