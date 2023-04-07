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

namespace Sigwin\Ariadne\Test;

use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;

trait AssertTrait
{
    /**
     * @param list<mixed> $all
     * @param list<int>   $expected
     * @param list<mixed> $actual
     */
    protected static function assertArrayInArrayByKey(array $all, array $expected, array $actual): void
    {
        $expected = array_values(array_intersect_key($all, array_flip($expected)));
        static::assertSame($expected, $actual);
    }

    /**
     * @template TResource of NamedResource
     * @template TChange of NamedResourceChange
     *
     * @param NamedResourceChangeCollection<TResource, TChange> $change
     */
    protected static function assertNamedResourceChangeCollectionIsEmpty(NamedResource $resource, NamedResourceChangeCollection $change): void
    {
        static::assertSame($resource, $change->getResource());
        static::assertSame([], iterator_to_array($change));
        static::assertCount(0, $change);
        static::assertTrue($change->isActual());
        static::assertSame([], iterator_to_array($change->filter(NamedResourceAttributeUpdate::class)));
    }
}
