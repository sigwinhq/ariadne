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

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\EnvironmentResolver;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\EnvironmentResolver
 *
 * @small
 */
final class EnvironmentResolverTest extends TestCase
{
    public function testWillUseXdgCacheHomeIfPassed(): void
    {
        $resolver = new EnvironmentResolver('/cacheee', null, '/home');
        static::assertSame('/cacheee/ariadne', $resolver->getCacheDir());
    }

    public function testWillFallBackToHomeCacheIfXdgCacheHomeNotPassed(): void
    {
        $resolver = new EnvironmentResolver(null, null, '/home');
        static::assertSame('/home/.cache/ariadne', $resolver->getCacheDir());
    }

    public function testWillUseXdgConfigHomeIfPassed(): void
    {
        $resolver = new EnvironmentResolver(null, '/configgg', '/home');
        static::assertSame('/configgg/ariadne', $resolver->getConfigDir());
    }

    public function testWillFallBackToHomeConfigIfXdgConfigHomeNotPassed(): void
    {
        $resolver = new EnvironmentResolver(null, null, '/home');
        static::assertSame('/home/.config/ariadne', $resolver->getConfigDir());
    }
}
