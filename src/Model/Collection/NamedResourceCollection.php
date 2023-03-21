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
 * @implements \Sigwin\Ariadne\NamedResourceCollection<T>
 */
final class NamedResourceCollection implements \Sigwin\Ariadne\NamedResourceCollection
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

    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public function filter(callable $filter): \Sigwin\Ariadne\NamedResourceCollection
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($filter($item)) {
                $items[] = $item;
            }
        }

        return self::fromArray($items);
    }

    public function diff(\Sigwin\Ariadne\NamedResourceCollection $other): \Sigwin\Ariadne\NamedResourceCollection
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($other->contains($item) === false) {
                $items[] = $item;
            }
        }

        return self::fromArray($items);
    }

    public function intersect(\Sigwin\Ariadne\NamedResourceCollection $other): \Sigwin\Ariadne\NamedResourceCollection
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $items[] = $item;
            }
        }

        return self::fromArray($items);
    }

    public function contains(NamedResource $item): bool
    {
        return \array_key_exists($item->getName(), $this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->items));
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function get(string $name): NamedResource
    {
        if (\array_key_exists($name, $this->items) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid argument "%1$s"', $name));
        }

        return $this->items[$name];
    }
}
