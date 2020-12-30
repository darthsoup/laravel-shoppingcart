<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\Hasher;
use DarthSoup\Cart\Exceptions\InvalidRowIdException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\SessionManager;

/**
 * Cart Class.
 */
class Cart
{
    /**
     * Default Instance Const.
     */
    public const DEFAULT_INSTANCE = 'main';

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
     * RowId Hashing Instance.
     *
     * @var Hasher
     */
    protected $hash;

    /**
     * Constructor.
     *
     * @param \Illuminate\Session\SessionManager $session
     * @param \Illuminate\Contracts\Events\Dispatcher $event
     * @param Hasher $hash
     */
    public function __construct(SessionManager $session, Dispatcher $event, Hasher $hash)
    {
        $this->session = $session;
        $this->event = $event;
        $this->hash = $hash;

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Get the current cart instance.
     *
     * @return string
     */
    protected function getInstance(): string
    {
        return $this->instance;
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
     * @return \DarthSoup\Cart\Cart
     */
    public function instance(string $instance = null)
    {
        $this->instance = $instance ?: self::DEFAULT_INSTANCE;

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
     * @return array|Item|Item[]
     */
    public function add($id, $name = null, $quantity = null, $price = null, array $options = [])
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

        $this->event->dispatch('cart.added', $item);

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
     * @param string       $parentRowId
     *
     * @return Item
     */
    public function addSubItem($id, $name, $quantity, $price, array $options, string $parentRowId): Item
    {
        $parentItem = $this->get($parentRowId);

        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->addSubItem($item);
            }, $id);
        }

        $subItem = $this->buildItem($id, $name, $quantity, $price, $options);

        // Insert subitem to item
        $parentItem->addSubItem($subItem);

        $cart = $this->getContent();

        // Insert item to Cart
        $cart->put($parentItem->rowId, $parentItem);

        $this->event->dispatch('cart.subitem.added', [$subItem, $parentItem]);

        $this->updateInstance($cart);

        return $subItem;
    }

    /**
     * Update the quantity or attributes of one item of the cart.
     *
     * @param string $rowId The rowId of the item you want to update
     * @param int|array $attribute New quantity of the item|array of attributes to update
     *
     * @return Item
     * @throws InvalidRowIdException
     */
    public function update(string $rowId, $attribute)
    {
        $cart = $this->getContent();
        $item = $cart->find($rowId);

        if (\is_array($attribute)) {
            $item->updateFromArray($attribute);
        } else {
            $item->quantity = $attribute;
        }

        if ($rowId !== $item->getRowId()) {
            $cart->pull($rowId);

            if ($cart->has($item->getRowId())) {
                $existingCartItem = $this->get($item->getRowId());
                $item->setQuantity($existingCartItem->quantity + $item->quantity);
            }
        }

        if ($item->quantity <= 0) {
            $this->remove($item->getRowId());

            return;
        }

        if ($item->isSubItem()) {
            $origin = $cart->findSubItemOrigin($rowId);

            $origin->forgetSubItem($item);
            $origin->addSubItem($item);
        } else {
            $cart->put($item->getRowId(), $item);
        }

        // Fire the cart.updated event
        $this->event->dispatch('cart.updated', $rowId);

        $this->updateInstance($cart);

        return $item;
    }

    /**
     * Remove a row from the cart.
     *
     * @param string $rowId The rowId of the item
     *
     * @return bool
     */
    public function remove(string $rowId)
    {
        $cart = $this->getContent();
        $item = $cart->find($rowId);

        if ($item->isSubItem()) {
            $origin = $cart->findSubItemOrigin($rowId);
            if (null !== $origin) {
                $origin->forgetSubItem($item);
            }
        } else {
            $cart->forget($item->rowId);
        }

        // Fire the cart.removed event
        $this->event->dispatch('cart.removed', $rowId);

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
        $item = $cart->find($rowId);

        if (null === $item) {
            throw new InvalidRowIdException("The cart does not contain rowId {$rowId}.");
        }

        return $item;
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
        return null !== $this->getContent()->find($rowId);
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
        $this->event->dispatch('cart.destroyed');
    }

    /**
     * Get the total price of all items with TAX.
     *
     * @return float
     */
    public function total(): float
    {
        $content = $this->getContent()->flatItems();

        return $content->reduce(function (float $total, Item $item) {
            return $total + ($item->quantity * $item->priceTax);
        }, 0);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return float
     */
    public function subtotal(): float
    {
        $content = $this->getContent()->flatItems();

        return $content->reduce(function ($subTotal, Item $item) {
            return $subTotal + ($item->quantity * $item->price);
        }, 0);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @return float
     */
    public function tax(): float
    {
        $content = $this->getContent()->flatItems();

        return $content->reduce(function (float $tax, Item $item) {
            return $tax + ($item->quantity * $item->tax);
        }, 0);
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->getContent()->flatItems()->sum('quantity');
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
    protected function getContent(): CartCollection
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
     * @return
     */
    protected function updateInstance($cart)
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
    protected function buildItem($id, $name, $quantity, $price, $options = []): Item
    {
        // 1. Insert an prepared Item directly
        // 2. Create item by array
        // 3. Create item by attributes

        if ($id instanceof Item) {
            $item = $id;
            $arguments = \func_get_args();
            $arguments[0] = $item->id;
            $item->updateFromAttributes(...$arguments);
        } elseif (is_array($id)) {
            $item = Item::fromArray($id);
        } else {
            $item = Item::fromAttributes($id, $name, $price, $options);
        }

        $item->setTaxRate(config('cart.tax'));

        return $item;
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
        if (! class_exists($model)) {
            throw (new ModelNotFoundException)->setModel($model);
        }
        $cartItem = $this->get($rowId);
        $cartItem->associate($model);

        $content = $this->getContent();
        $content->put($cartItem->rowId, $cartItem);

        $this->updateInstance($content);

        return $this;
    }

    /**
     * Check if the array is a multidimensional array.
     *
     * @param array $items The array to check
     *
     * @return bool
     */
    protected function isMulti($items)
    {
        if (!is_array($items)) {
            return false;
        }

        return is_array(head($items));
    }
}
