<?php

namespace DarthSoup\Cart\Contracts;

/**
 * Interface CartContract.
 */
interface CartContract
{
    public function add($id, $name = null, $quantity = null, $price = null, array $options = []);

    public function update($rowId, $attribute);

    public function remove($rowId);

    public function get($rowId);

    public function destroy();
}
