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

namespace Sigwin\Ariadne\Test\Resolver;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Resolver\XdgEnvironmentResolver;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Resolver\XdgEnvironmentResolver
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Resolver\XdgEnvironmentResolver::class)]
final class XdgEnvironmentResolverTest extends TestCase
{
    public function testWillUseXdgCacheHomeIfPassed(): void
    {
        $resolver = new XdgEnvironmentResolver('/cacheee', null, '/home');
        self::assertSame('/cacheee/ariadne', $resolver->getCacheDir());
    }

    public function testWillFallBackToHomeCacheIfXdgCacheHomeNotPassed(): void
    {
        $resolver = new XdgEnvironmentResolver(null, null, '/home');
        self::assertSame('/home/.cache/ariadne', $resolver->getCacheDir());
    }

    public function testWillUseXdgConfigHomeIfPassed(): void
    {
        $resolver = new XdgEnvironmentResolver(null, '/configgg', '/home');
        self::assertSame('/configgg/ariadne', $resolver->getConfigDir());
    }

    public function testWillFallBackToHomeConfigIfXdgConfigHomeNotPassed(): void
    {
        $resolver = new XdgEnvironmentResolver(null, null, '/home');
        self::assertSame('/home/.config/ariadne', $resolver->getConfigDir());
    }
}
