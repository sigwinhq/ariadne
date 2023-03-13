<?php

declare(strict_types=1);

/*
 * This file is part of the Sigwin Ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne\Model\Collection;

use Sigwin\Ariadne\NamedResource;

/**
 * @template T of NamedResource
 *
 * @implements \IteratorAggregate<T>
 */
final class NamedResourceCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, T>
     */
    private readonly array $items;

    /**
     * @param list<T> $resources
     */
    private function __construct(array $resources)
    {
        $items = [];
        foreach ($resources as $resource) {
            $items[$resource->getName()] = $resource;
        }
        ksort($items);

        $this->items = $items;
    }

    /**
     * @param list<T> $items
     *
     * @return self<T>
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @param callable(T): bool $filter
     *
     * @return self<T>
     */
    public function filter(callable $filter): self
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($filter($item)) {
                $items[] = $item;
            }
        }

        return self::fromArray($items);
    }

    /**
     * @param T $item
     */
    public function contains(NamedResource $item): bool
    {
        return \array_key_exists($item->getName(), $this->items);
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->items));
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
