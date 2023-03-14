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
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\Collection\ProfileTemplateCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Model\ProfileFilter
 *
 * @small
 */
final class ProfileFilterTest extends TestCase
{
    public function testEmptyFilterMatchesEverything(): void
    {
        $filter = ProfileFilter::create(null, null);

        static::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByNameOnly(): void
    {
        $filter = ProfileFilter::create('foo', null);

        static::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByTypeOnly(): void
    {
        $filter = ProfileFilter::create(null, 'fake');

        static::assertTrue($filter->match($this->createProfile()));
    }

    public function testCanMatchByNameAndType(): void
    {
        $filter = ProfileFilter::create('foo', 'fake');

        static::assertTrue($filter->match($this->createProfile()));
    }

    public function testBothNameAndTypeMustMatch(): void
    {
        $filter = ProfileFilter::create('foo', 'not fake');
        static::assertFalse($filter->match($this->createProfile()));

        $filter = ProfileFilter::create('not foo', 'fake');
        static::assertFalse($filter->match($this->createProfile()));
    }

    private function createProfile(): Profile
    {
        return new class() implements Profile {
            public function getName(): string
            {
                return 'foo';
            }

            public static function getType(): string
            {
                return 'fake';
            }

            public function getIterator(): \Traversable
            {
                throw new \LogicException('Not implemented');
            }

            public static function fromConfig(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $templateFactory, CacheItemPoolInterface $cachePool): Profile
            {
                throw new \LogicException('Not implemented');
            }

            public function getApiUser(): ProfileUser
            {
                throw new \LogicException('Not implemented');
            }

            public function getApiVersion(): string
            {
                throw new \LogicException('Not implemented');
            }

            public function getSummary(): ProfileSummary
            {
                throw new \LogicException('Not implemented');
            }

            public function getTemplates(): ProfileTemplateCollection
            {
                throw new \LogicException('Not implemented');
            }

            public function getMatchingTemplates(Repository $repository): ProfileTemplateCollection
            {
                throw new \LogicException('Not implemented');
            }

            public function plan(Repository $repository): NamedResourceChangeCollection
            {
                throw new \LogicException('Not implemented');
            }

            public function apply(NamedResourceChangeCollection $plan): void
            {
                throw new \LogicException('Not implemented');
            }
        };
    }
}
