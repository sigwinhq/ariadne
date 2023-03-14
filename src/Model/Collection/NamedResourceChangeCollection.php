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

use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;

/**
 * @implements \IteratorAggregate<NamedResourceChange>
 */
final class NamedResourceChangeCollection implements \Countable, \IteratorAggregate, NamedResourceChange
{
    /**
     * @param array<NamedResourceChange> $changes
     */
    private function __construct(private readonly NamedResource $resource, private readonly array $changes)
    {
    }

    public function isActual(): bool
    {
        foreach ($this->changes as $change) {
            if ($change->isActual() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, NamedResourceAttributeUpdate>
     */
    public function getAttributeChanges(): array
    {
        $diff = [];
        foreach ($this->changes as $change) {
            if ($change instanceof NamedResourceAttributeUpdate === false) {
                continue;
            }
            $diff[$change->getResource()->getName()] = $change;
        }

        return $diff;
    }

    /**
     * @param array<NamedResourceChange> $changes
     */
    public static function fromResource(NamedResource $resource, array $changes): self
    {
        return new self($resource, $changes);
    }

    public function getResource(): NamedResource
    {
        return $this->resource;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->changes);
    }

    public function count(): int
    {
        return \count($this->changes);
    }
}
