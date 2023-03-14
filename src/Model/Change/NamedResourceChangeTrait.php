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
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\Repository;
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

    /**
     * @return \Traversable<NamedResourceChange>
     */
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
        $changes = [];
        foreach ($this->changes as $change) {
            $resource = $change->getResource();
            if ($change instanceof \Sigwin\Ariadne\NamedResourceChangeCollection) {
                if ($this->resource !== $resource || ! ($this->resource instanceof Repository && $resource instanceof ProfileTemplate)) {
                    // unfortunate corner case: treat template and repository as the same resource since they both apply to the repository
                    continue;
                }
                $changes = array_replace($changes, $change->getAttributeChanges());
            }

            if ($change instanceof NamedResourceAttributeUpdate === false) {
                continue;
            }
            $changes[$resource->getName()] = $change;
        }

        foreach ($changes as $name => $change) {
            if ($change->isActual() === true) {
                unset($changes[$name]);
            }
        }

        return $changes;
    }
}
