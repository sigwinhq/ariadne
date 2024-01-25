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
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\Test\AssertTrait;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\Change\NamedResourceCreate
 *
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(NamedResourceCreate::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection::class)]
final class NamedResourceCreateTest extends TestCase
{
    use AssertTrait;

    public function testCreate(): void
    {
        $resource = $this->createMock(NamedResource::class);
        $change = NamedResourceCreate::fromResource($resource, []);

        self::assertNamedResourceChangeCollectionIsEmpty($resource, $change);
    }
}
