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

namespace Sigwin\Ariadne\Test\Model\Change;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Model\Change\NamedResourceUpdate;
use Sigwin\Ariadne\NamedResource;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\Change\NamedResourceUpdate
 *
 * @uses \Sigwin\Ariadne\Model\Collection\NamedResourceChangeCollection
 *
 * @small
 */
final class NamedResourceUpdateTest extends TestCase
{
    public function testCreate(): void
    {
        $resource = $this->createMock(NamedResource::class);

        $change = NamedResourceUpdate::fromResource($resource, []);

        static::assertSame($resource, $change->getResource());
        static::assertSame([], iterator_to_array($change));
        static::assertCount(0, $change);
        static::assertTrue($change->isActual());
        static::assertSame([], $change->getAttributeChanges());
    }
}
