<?php

namespace DarthSoup\Cart;

use Carbon\Carbon;
use DarthSoup\Cart\Contracts\ItemContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
     * @var string
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
     * @var int
     */
    public $quantity = 1;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var ItemOptions
     */
    public $options;

    /**
     * @var Collection
     */
    public $subItems = [];

    /**
     * @var bool
     */
    public $subItem = false;

    /**
     * Created at.
     *
     * @var Carbon
     */
    protected $created_at;

    /**
     * Updated at.
     *
     * @var Carbon
     */
    protected $updated_at;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    protected $taxRate = 0;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    protected $associatedModel;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     * @param array      $options
     */
    public function __construct($id, string $name, float $price = null, array $options = [])
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Please enter a valid identifier.');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Please enter a valid name.');
        }

        $this->rowId = $this->generateRowId($id, $options);
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->options = new ItemOptions($options);
        $this->subItems = new Collection();
        $this->created_at = $this->freshTimestamp();
        $this->updated_at = $this->freshTimestamp();
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     *
     * @return Item
     */
    public function associate($model)
    {
        $this->associatedModel = \is_string($model)
            ? $model
            : \get_class($model);

        return $this;
    }

    /**
     * Add a SubItem to the SubItemCollection.
     *
     * @param Item $subItem
     */
    public function addSubItem(self $subItem)
    {
        $subItem->subItem = true;

        $this->subItems->put($subItem->getRowId(), $subItem);

        $this->updated_at = $this->freshTimestamp();
    }

    /**
     * @param Item $subItem
     */
    public function removeSubItem(self $subItem)
    {
        $this->subItems->forget($subItem->rowId);

        $this->updated_at = $this->freshTimestamp();
    }

    /**
     * @return Collection
     */
    public function getSubItems()
    {
        return $this->subItems;
    }

    /**
     * @param string $rowId
     * @return Item
     */
    public function getSubItem(string $rowId)
    {
        return $this->subItems->get($rowId);
    }

    /**
     * @return bool
     */
    public function hasSubItems()
    {
        return ! $this->subItems->isEmpty();
    }

    /**
     * Update the cart item from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id = Arr::get($attributes, 'id', $this->id);
        $this->quantity = Arr::get($attributes, 'quantity', $this->quantity);
        $this->name = Arr::get($attributes, 'name', $this->name);
        $this->price = Arr::get($attributes, 'price', $this->price);
        $this->options = $this->options->merge(
            new ItemOptions(Arr::get($attributes, 'options', $this->options))
        );

        if (Arr::get($attributes, 'generateRowId')) {
            $this->rowId = $this->generateRowId($this->id, $this->options->toArray());
        }

        $this->updated_at = $this->freshTimestamp();
    }

    /**
     * Update the cart item from an array.
     *
     * @param string $id
     * @param string $name
     * @param int    $quantity
     * @param float  $price
     * @param array  $options
     *
     * @return void
     */
    public function updateFromAttributes(string $id, string $name = null, int $quantity = null, float $price = null, array $options = [])
    {
        $this->id = $id;
        $this->name = $name ?? $this->name;
        $this->quantity = $quantity ?? $this->quantity;
        $this->price = $price ?? $this->price;
        $this->options = $this->options->merge($options);

        $this->updated_at = $this->freshTimestamp();
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     *
     * @return Item
     */
    public static function fromArray(array $attributes)
    {
        $price = Arr::get($attributes, 'price');
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $price, $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     * @param array      $options
     *
     * @return Item
     */
    public static function fromAttributes($id, string $name, float $price = null, array $options = [])
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     *
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
     * @param int $quantity
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array  $options
     *
     * @return string
     */
    protected function generateRowId(string $id, array $options)
    {
        return app('cart.hash')->make($id, $options);
    }

    /**
     * Get the item as an array.
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
            'price' => (float)$this->price,
            'priceTax' => $this->priceTax,
            'tax' => $this->tax,
            'options' => $this->options->toArray(),
            'subtotal' => $this->subtotal,
            'subItems' => $this->getSubItems()->toArray(),
            'model' => null === $this->associatedModel ? $this->associatedModel : $this->model->toArray(),
            'created_at' => $this->created_at->getTimestamp(),
            'updated_at' => $this->updated_at->getTimestamp(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * @return bool
     */
    public function isSubItem(): bool
    {
        return $this->subItem;
    }

    /**
     * @return bool
     */
    public function isAssociated(): bool
    {
        return isset($this->associatedModel);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return with(new $this->associatedModel())->find($this->id);
    }

    /**
     * @return string
     */
    public function getRowId(): string
    {
        return $this->rowId;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp(): Carbon
    {
        return new Carbon();
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if ($attribute === 'priceTax') {
            return $this->price + $this->tax;
        }

        if ($attribute === 'subtotal') {
            return $this->quantity * $this->price;
        }

        if ($attribute === 'total') {
            return $this->quantity * ($this->priceTax);
        }

        if ($attribute === 'tax') {
            return $this->price * ($this->taxRate / 100);
        }

        if ($attribute === 'model') {
            return $this->getModel();
        }
    }
}
