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

use Sigwin\Ariadne\Model\Change\NamedResourceChangeTrait;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;

final class NamedResourceChangeFlattenedCollection implements NamedResourceChangeCollection
{
    use NamedResourceChangeTrait;

    /**
     * @param array<NamedResourceChange> $changes
     */
    private function __construct(private readonly NamedResource $resource, private readonly array $changes)
    {
    }

    /**
     * @param array<NamedResourceChange> $changes
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
