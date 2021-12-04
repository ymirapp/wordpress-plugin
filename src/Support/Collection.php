<?php

declare(strict_types=1);

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Plugin\Support;

/**
 * A collection offers a fluent interface for easier array manipulation.
 */
class Collection implements \ArrayAccess
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Constructor.
     */
    public function __construct($items = [])
    {
        $this->items = $this->convertToArray($items);
    }

    /**
     * Get all of the items in the collection.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Chunk the collection into chunks of the given size.
     */
    public function chunk(int $size)
    {
        $chunks = new self();

        if ($size <= 0) {
            return $chunks;
        }

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return $chunks;
    }

    /**
     * Collapse the collection of items into a single array.
     */
    public function collapse(): self
    {
        $results = [];

        foreach ($this->items as $item) {
            if ($item instanceof self) {
                $item = $item->all();
            } elseif (!is_array($item)) {
                continue;
            }

            $results[] = $item;
        }

        return new self(array_merge([], ...$results));
    }

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     */
    public function diff($items): self
    {
        return new self(array_diff($this->items, $this->convertToArray($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     */
    public function diffKeys($items)
    {
        return new self(array_diff_key($this->items, $this->convertToArray($items)));
    }

    /**
     * Execute a callback over each collection item.
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if (false === $callback($item, $key)) {
                break;
            }
        }

        return $this;
    }

    /**
     * Run a filter over each collection item.
     */
    public function filter(callable $callback = null): self
    {
        $filtered = $callback ? array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH) : array_filter($this->items);

        return new self($filtered);
    }

    /**
     * Flip the items in the collection.
     */
    public function flip(): self
    {
        return new self(array_flip($this->items));
    }

    /**
     * Determine if the collection is empty or not.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get the keys of the collection items.
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    /**
     * Run a map over each collection item.
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new self(array_combine($keys, $items));
    }

    /**
     * Run an associative map over each collection item.
     */
    public function mapWithKeys(callable $callback): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new self($result);
    }

    /**
     * Merge the collection with the given items.
     */
    public function merge($items): self
    {
        return new self(array_merge($this->items, $this->convertToArray($items)));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (null === $key) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     */
    public function search($value, $strict = false)
    {
        return array_search($value, $this->items, $strict);
    }

    /**
     * Get and remove the first N items from the collection.
     */
    public function shift(int $count = 1)
    {
        if (1 === $count) {
            return array_shift($this->items);
        }

        if ($this->isEmpty()) {
            return new self();
        }

        $results = [];

        $collectionCount = $this->count();

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = array_shift($this->items);
        }

        return new self($results);
    }

    /**
     * Extract a slice of the collection.
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Return a collection with only unique values in the collection array.
     */
    public function unique(): self
    {
        return new self(array_unique($this->items));
    }

    /**
     * Reset the keys on the underlying array.
     */
    public function values()
    {
        return new self(array_values($this->items));
    }

    /**
     * Convert the given value to an array.
     */
    private function convertToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        } elseif ($value instanceof self) {
            return $value->all();
        }

        return (array) $value;
    }
}
