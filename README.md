## Laravel Cart

[![Build Status](https://travis-ci.org/darthsoup/laravel-shoppingcart.svg?branch=master)](https://travis-ci.org/darthsoup/laravel-shoppingcart)
[![Total Downloads](https://poser.pugx.org/darthsoup/shoppingcart/downloads)](https://packagist.org/packages/darthsoup/shoppingcart)
[![License](https://poser.pugx.org/darthsoup/shoppingcart/license)](https://packagist.org/packages/darthsoup/shoppingcart)

An easy shoppingcart implementation for Laravel > 5.2.
Based on the work of Gloudemans\Shoppingcart.

## Features

This package for shopping carts provides these features:

* Cart items can have subitems
* Custom hash algorithms for item identifiers
* Individual taxing for single items # todo
* Database Support # todo

## Installation

Install the package through [Composer](http://getcomposer.org/). Edit your project's `composer.json` file by adding:

### Requirements

This package needs at least Laravel 5.2 and PHP 7.1.

### Install

First, you'll need to install the package via Composer:

```shell
$ composer require darthsoup/shoppingcart
```

### After Laravel 5.5

You do not need to do anything else here

### Before Laravel 5.5 

Then, update `config/app.php` by adding an entry for the service provider.

```php
'providers' => [
    // ...
    DarthSoup\Cart\CartServiceProvider::class,
];
```

If you want to access the Cart via Facade than add a new line to the `aliases` array

```php
'aliases' => [
    // ...
    'Cart' => DarthSoup\Cart\Facades\Cart::class,
];
```

## Usage

The cart package provides you the following methods to use:


### Add Item

```php
/**
 * Add a Item to the cart.
 *
 * @param string|array $id Unique ID of the item|Item formatted as array|Array of items
 * @param string $name Name of the item
 * @param int $quantity Item quantity to add to the cart
 * @param float $price Price of one item
 * @param array $options Array of additional options, such as 'size' or 'color'
 */

// Basic form
Cart::add('1', 'Product One', 1, 9.99, ['option_key' => 'option_value']);

// Array form
Cart::add(['id' => 'mail1000', 'name' => 'Mail Package One', 'quantity' => 5, 'price' => 4.99, 'options' => []]);

// Batch method
Cart::add([
    ['id' => '15', 'name' => 'Hamburger', 'quantity' => 1, 'price' => 1.99],
    ['id' => '16', 'name' => 'Cheeseburger', 'quantity' => 1, 'price' => 2.49, 'options' => ['onion' => false]]
]);
```

you also can make Items by make them manually 

```php
$item = new \DarthSoup\Cart\Item('15', 'Hamburger', 1.99, ['onion' => false]);

Cart::add($item);
```

### Update one Item

```php
Cart::update('rowId', [
    'options' => [$field => $value]
]);
```

### Get One Cart Item

```php
Cart::get('rowId');
```

### Show Cart Content

Show the content of the Cart by returning the CartCollection

```php
Cart::content();
```

### Empty the cart

```php
Cart::destroy();
```

### Remove one Item

```php
Cart::remove('rowId');
```

### Total Price of all Items

```php
Cart::total();
```

### Item Count

```php
Cart::count();
```

## SubItems

This package also includes the functionality to add Subitems by adding them to an additional Collection in the Item

### Add SubItem

The `addSubItem` function works basically like `add` but it accepts a parent row Id at the end to add an SubItem
to the item.

```php
$hamburger = Cart::add('15', 'Hamburger', 1, 1.99, ['onion' => false]);

Cart::addSubItem('99', 'Extra Bacon', 1, 0.99, [], $hamburger->getRowId())
```

### Remove SubItem

Just like removing normal ones, just include your subItem `rowId` and it will be removed from the parent

## Models

A new feature is associating a model with the items in the cart. Let's say you have a `Product` model in your application. With the `associate()` method, you can tell the cart that an item in the cart, is associated to the `Product` model. 

That way you can access your model right from the `CartCollection`!

Here is an example:

```php
<?php 

Cart::associate(\App\Product::class)->add('15', 'Hamburger', 1, 9.99, ['extra_sauce' => true]);

$content = Cart::content();

foreach($content as $row) {
	echo 'You have ' . $row->quantity . ' items of ' . $row->model->name . ' with description: "' . $row->model->description . '" in your cart.';
}
```
Using the key `model` to access the model that you associated.

## Exceptions
The Cart package will throw exceptions if something goes wrong. This way it's easier to debug your code using the Cart package or to handle the error based on the type of exceptions. The Cart packages can throw the following exceptions:

| Exception                             | Reason                                                                            |
| ------------------------------------- | --------------------------------------------------------------------------------- |
| *InstanceException*                   | When no instance is passed to the instance() method                               |
| *InvalidRowIdException*               | When the `$rowId` that got passed doesn't exists in the current cart              |
| *InvalidQuantityException*            | When the quantity is outside the set limits                                       |
| *ClassNotFoundException*              | When an class cannot found while association                                      |

## Events

The cart also has events build in. There are five events available for you to listen for.

| Event                          | Fired                                   |
| ------------------------------ | --------------------------------------- |
| cart.added($item)              | When a item is added                    |
| cart.subitem.added($item)      | When a sub item is added                |              |
| cart.updated($rowId)           | When an item in the cart is updated     |
| cart.removed($rowId)           | When an item is removed from the cart   |
| cart.destroyed()               | When the cart is destroyed              |

## Contributions

Please use [Github](https://github.com/darthsoup/shoppingcart) for reporting bugs, and making comments or suggestions.
See [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute changes.
