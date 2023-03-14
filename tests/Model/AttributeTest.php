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

namespace Sigwin\Ariadne\Test\Model;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Model\Attribute;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\Attribute
 *
 * @small
 */
final class AttributeTest extends TestCase
{
    public function testCanSetName(): void
    {
        $attribute = new Attribute('name');
        static::assertSame('name', $attribute->getName());
    }
}
