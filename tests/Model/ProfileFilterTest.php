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
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\ProfileFilter
 *
 * @small
 */
final class ProfileFilterTest extends TestCase
{
    use ModelGeneratorTrait;

    public function testEmptyFilterMatchesEverything(): void
    {
        $filter = ProfileFilter::create(null, null);

        self::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByNameOnly(): void
    {
        $filter = ProfileFilter::create('foo', null);

        self::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByTypeOnly(): void
    {
        $filter = ProfileFilter::create(null, 'fake');

        self::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByNameAndType(): void
    {
        $filter = ProfileFilter::create('foo', 'fake');

        self::assertTrue($filter->match($this->createProfile()));
    }

    public function testBothNameAndTypeMustMatch(): void
    {
        $filter = ProfileFilter::create('foo', 'not fake');
        self::assertFalse($filter->match($this->createProfile()));

        $filter = ProfileFilter::create('not foo', 'fake');
        self::assertFalse($filter->match($this->createProfile()));
    }
}
