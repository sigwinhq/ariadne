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

use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;

/**
 * @template TResource of NamedResource
 * @template TChange of NamedResourceChange
 *
 * @implements NamedResourceChangeCollection<TResource, TChange>
 */
final class NamedResourceArrayChangeCollection implements NamedResourceChangeCollection
{
    /**
     * @use NamedResourceChangeTrait<TResource, TChange>
     */
    use NamedResourceChangeTrait;

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
     * @param STResource     $resource
     * @param list<STChange> $changes
     *
     * @return self<STResource, STChange>
     */
    public static function fromResource(NamedResource $resource, array $changes): self
    {
        return new self($resource, $changes);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->changes);
    }
}
