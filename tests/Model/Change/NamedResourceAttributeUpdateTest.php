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
use Sigwin\Ariadne\Model\Attribute;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate
 *
 * @uses \Sigwin\Ariadne\Model\Attribute
 *
 * @small
 */
final class NamedResourceAttributeUpdateTest extends TestCase
{
    public function testCanGetResource(): void
    {
        $attribute = new Attribute('foo');
        $change = new NamedResourceAttributeUpdate($attribute, null, null);

        static::assertSame($attribute, $change->getResource());
    }

    public function testIsActualIfActualIsEqualToExpected(): void
    {
        $attribute = new Attribute('foo');
        $change = new NamedResourceAttributeUpdate($attribute, 'bar', 'bar');

        static::assertTrue($change->isActual());
    }
}
