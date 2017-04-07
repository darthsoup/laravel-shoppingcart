<?php

namespace DarthSoup\Cart;

use DarthSoup\Cart\Contracts\ItemContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

/**
 * Class CartItem
 *
 * @author Kevin Krummnacker <kk@dogado.de>
 * @package DarthSoup\Cart
 */
class Item implements ItemContract, Arrayable, Jsonable
{
    /**
     * The rowId of the cart item.
     *
     * @var string
     */
    public $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $quantity;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var array
     */
    public $options;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    private $associatedModel;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function __construct($id, $name, $price, array $options = [])
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Please supply a valid identifier.');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Please supply a valid name.');
        }

        if (strlen($price) < 0 || !is_numeric($price)) {
            throw new \InvalidArgumentException('Please supply a valid price.');
        }

        $this->rowId = $this->generateRowId($id, $options);
        $this->id = $id;
        $this->name = $name;
        $this->price = (float) $price;
        $this->options = new CartItemOptions($options);
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     * @return Item
     */
    public function associate($model)
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);

        return $this;
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     * @return Item
     */
    public static function fromArray(array $attributes)
    {
        $options = array_get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array $options
     * @return Item
     * @throws \InvalidArgumentException
     */
    public static function fromAttributes($id, $name, $price, array $options = [])
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     * @return Item
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Set the quantity for this cart item.
     *
     * @param int|float $quantity
     * @throws \InvalidArgumentException
     */
    public function setQuantity($quantity)
    {
        if (empty($quantity) || !is_numeric($quantity)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->quantity = $quantity;
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array $options
     * @return string
     */
    protected function generateRowId($id, array $options)
    {
        Arr::sortRecursive($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'rowId' => $this->rowId,
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'options' => $this->options,
            //'tax' => $this->tax,
            //'subtotal' => $this->subtotal
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return with(new $this->associatedModel)->find($this->id);
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }
        if ($attribute === 'model') {
            return with(new $this->associatedModel)->find($this->id);
        }

        return null;
    }
}