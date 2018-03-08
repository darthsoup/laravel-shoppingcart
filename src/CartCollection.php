<?php

namespace DarthSoup\Cart;

use Illuminate\Support\Collection;

/**
 * Class CartCollection.
 */
class CartCollection extends Collection
{
    /**
     * Returns a collection with all items and subitems merged together.
     */
    public function flatItems()
    {
        $result = [];

        foreach ($this->items as $k => $item) {
            if (! $item->subItems->isEmpty()) {
                $result[$k] = $item;
                $result = array_merge($result, $this->getArrayableItems($item->subItems));

                continue;
            }

            $result[$k] = $item;
        }

        return new static($result);
    }

    /**
     * returns a collection with all subitems.
     *
     * @return static
     */
    public function showSubItems()
    {
        $result = [];

        foreach ($this->items as $k => $item) {
            if ($item->subItems->isEmpty()) {
                continue;
            }

            $result = array_merge($result, $this->getArrayableItems($item->subItems));
        }

        return new static($result);
    }

    /**
     * get the origin item of a subitem.
     *
     * @param string $rowId
     * @return null|Item
     */
    public function findSubItemOrigin(string $rowId)
    {
        foreach ($this->items as $item) {
            if ($item->subItems->has($rowId)) {
                return $item;
            }
        }
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function find(string $id)
    {
        /* @var CartCollection $content */
        foreach ($this->items as $item) {
            if ($id === $item->getRowId()) {
                return $item;
            }

            $found = $this->recursionFind($item, $id);
            if ($found) {
                return $found;
            }
        }
    }

    /**
     * @param $item
     * @param $id
     * @return null
     */
    private function recursionFind(Item $item, $id)
    {
        if (! $item->hasSubItems()) {
            return;
        }

        foreach ($item->getSubItems() as $subItem) {
            if ($id === $subItem->getRowId()) {
                return $subItem;
            }

            $found = $this->recursionFind($subItem, $id);
            if ($found) {
                return $found;
            }
        }
    }
}
