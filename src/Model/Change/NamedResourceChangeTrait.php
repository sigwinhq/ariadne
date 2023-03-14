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

namespace Sigwin\Ariadne\Model\Change;

use Sigwin\Ariadne\Model\Collection\NamedResourceChangeCollection;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;

trait NamedResourceChangeTrait
{
    /**
     * @param array<NamedResourceChange> $changes
     */
    public static function fromResource(NamedResource $resource, array $changes): self
    {
        return new self($resource, NamedResourceChangeCollection::fromResource($resource, $changes));
    }

    public function getResource(): NamedResource
    {
        return $this->resource;
    }

    public function getIterator(): \Traversable
    {
        return $this->changes;
    }

    public function count(): int
    {
        return \count($this->changes);
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
}
