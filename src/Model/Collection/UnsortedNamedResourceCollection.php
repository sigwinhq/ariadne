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
final class UnsortedNamedResourceCollection implements \Sigwin\Ariadne\NamedResourceCollection
{
    /** @use NamedResourceCollectionTrait<T> */
    use NamedResourceCollectionTrait;

    /**
     * @param list<T> $resources
     */
    private function __construct(array $resources)
    {
        $items = [];
        foreach ($resources as $resource) {
            $items[$resource->getName()] = $resource;
        }
        $this->items = $items;
    }
}
