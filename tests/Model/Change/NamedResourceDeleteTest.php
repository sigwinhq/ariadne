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
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\Test\AssertTrait;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\Change\NamedResourceDelete
 *
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Model\Change\NamedResourceDelete::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection::class)]
final class NamedResourceDeleteTest extends TestCase
{
    use AssertTrait;

    public function testCanCreate(): void
    {
        $resource = $this->createMock(NamedResource::class);
        $change = NamedResourceDelete::fromResource($resource, []);

        self::assertNamedResourceChangeCollectionIsEmpty($resource, $change);
    }
}
