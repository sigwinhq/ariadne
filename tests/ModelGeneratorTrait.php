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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\NamedResourceCollection;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileFactory;
use Sigwin\Ariadne\ProfileTemplateFactory;

trait ModelGeneratorTrait
{
    /**
     * @param list<mixed> $all
     * @param list<int>   $expected
     * @param list<mixed> $actual
     */
    private static function assertArrayInArrayByKey(array $all, array $expected, array $actual): void
    {
        $expected = array_values(array_intersect_key($all, array_flip($expected)));
        static::assertSame($expected, $actual);
    }

    /**
     * @param list<array{string, string}> $list
     *
     * @return NamedResourceCollection<RepositoryUser>
     */
    private function createUsers(array $list = []): NamedResourceCollection
    {
        return SortedNamedResourceCollection::fromArray(array_map($this->createUser(...), array_column($list, 0), array_column($list, 1)));
    }

    private function createUser(string $name = 'theseus', string $role = 'admin'): RepositoryUser
    {
        return new RepositoryUser($name, $role);
    }

    /**
     * @param list<array{string, string}>|null $users
     * @param null|list<string> $topics
     */
    private function createRepository(string $path, ?array $users = null, ?string $type = null, ?array $topics = null): Repository
    {
        return new Repository(
            ['path' => $path],
            $type !== null ? RepositoryType::from($type) : RepositoryType::SOURCE,
            RepositoryVisibility::PUBLIC,
            $this->createUsers($users ?? []),
            time(),
            $path,
            $topics ?? [],
            []
        );
    }

    /**
     * @param list<ProfileConfig> $configs
     */
    private function createProfileFactory(array $configs): ProfileFactory
    {
        $idx = 0;
        $mock = $this->createMock(ProfileFactory::class);
        $mock
            ->expects(static::exactly(\count($configs)))
            ->method('create')
            ->with(static::callback(static function (ProfileConfig $config) use (&$idx, $configs) {
                static::assertSame($configs[$idx]->name, $config->name);

                return true;
            }))
            ->willReturnCallback(function () use (&$idx, $configs) {
                return $this->createProfile($configs[$idx++]->name);
            })
        ;

        return $mock;
    }

    private function createProfile(string $name = 'foo'): Profile
    {
        return new class($name) implements Profile {
            public function __construct(private readonly string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
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

            public function getTemplates(): NamedResourceCollection
            {
                throw new \LogicException('Not implemented');
            }

            public function getMatchingTemplates(Repository $repository): NamedResourceCollection
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
