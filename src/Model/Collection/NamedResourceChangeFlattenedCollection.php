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
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;

final class NamedResourceChangeFlattenedCollection implements NamedResourceChangeCollection
{
    private function __construct(private readonly NamedResourceChangeCollection $changes)
    {
    }

    /**
     * @param array<NamedResourceChange> $changes
     */
    public static function fromResource(NamedResource $resource, array $changes): self
    {
        return new self(\Sigwin\Ariadne\Model\Collection\NamedResourceChangeCollection::fromResource($resource, $changes));
    }

    public function getIterator(): \Traversable
    {
        return $this->changes;
    }

    public function count(): int
    {
        return \count($this->changes);
    }

    public function getResource(): NamedResource
    {
        return $this->changes->getResource();
    }

    public function isActual(): bool
    {
        return $this->getAttributeChanges() === [];
    }

    public function getAttributeChanges(): array
    {
        return $this->changes->getAttributeChanges();
    }
}
