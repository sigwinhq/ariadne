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

use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;

/**
 * @template TResource of NamedResource
 * @template TChange of NamedResourceChange
 */
trait NamedResourceChangeTrait
{
    /**
     * @param TResource     $resource
     * @param list<TChange> $changes
     */
    private function __construct(private readonly NamedResource $resource, private readonly array $changes)
    {
    }

    /**
     * @template STResource of NamedResource
     * @template STChange of NamedResourceChange
     *
     * @param STResource      $resource
     * @param array<STChange> $changes
     *
     * @return self<STResource, STChange>
     */
    public static function fromResource(NamedResource $resource, array $changes): self
    {
        return new self($resource, $changes);
    }

    /**
     * @return TResource
     */
    public function getResource(): NamedResource
    {
        return $this->resource;
    }

    /**
     * @return \Traversable<TChange>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->changes);
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
     * @param class-string<TChange> $type
     *
     * @return self<TResource, TChange>
     */
    public function filter(string $type): self
    {
        $changes = [];
        foreach ($this->changes as $change) {
            $resource = $change->getResource();
            if ($change instanceof \Sigwin\Ariadne\NamedResourceChangeCollection) {
                if ($this->resource !== $resource && ! ($this->resource instanceof Repository && $resource instanceof ProfileTemplate)) {
                    // unfortunate corner case: treat template and repository as the same resource since they both apply to the repository
                    continue;
                }
                $changes = array_merge($changes, iterator_to_array($change->filter($type)));
            }

            if ($change instanceof NamedResourceAttributeUpdate) {
                $changes[$resource->getName()] = $change;
            }
        }

        return self::fromResource($this->resource, array_values($changes));
    }
}
