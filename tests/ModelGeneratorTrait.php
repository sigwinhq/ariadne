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

use Nyholm\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Evaluator;
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\ProfileTemplateTarget;
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
    protected static function assertArrayInArrayByKey(array $all, array $expected, array $actual): void
    {
        $expected = array_values(array_intersect_key($all, array_flip($expected)));
        static::assertSame($expected, $actual);
    }

    protected function createTemplateFactory(): ProfileTemplateFactory
    {
        $factory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        /** @var NamedResourceCollection<Repository> $repositories */
        $repositories = SortedNamedResourceCollection::fromArray([]);
        $factory
            ->method('fromConfig')
            ->willReturn(new ProfileTemplate(
                'foo',
                ProfileTemplateTarget::fromConfig(
                    ProfileTemplateTargetConfig::fromArray(['attribute' => []]),
                    $this->getMockBuilder(Evaluator::class)->getMock(),
                ),
                $repositories,
            ))
        ;

        return $factory;
    }

    protected function createCachePool(): CacheItemPoolInterface
    {
        return $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
    }

    /**
     * @param list<array{string, string}> $list
     *
     * @return NamedResourceCollection<RepositoryUser>
     */
    protected function createUsers(array $list = []): NamedResourceCollection
    {
        return SortedNamedResourceCollection::fromArray(array_map($this->createUser(...), array_column($list, 0), array_column($list, 1)));
    }

    protected function createUser(string $name = 'theseus', string $role = 'admin'): RepositoryUser
    {
        return new RepositoryUser($name, $role);
    }

    /**
     * @param list<array{string, string}>|null $users
     * @param null|list<string> $topics
     */
    protected function createRepository(string $path, ?array $users = null, ?string $type = null, ?array $topics = null): Repository
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
     * @param array<string, string> $requests
     */
    protected function createHttpClient(array $requests = []): ClientInterface
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();

        foreach ($requests as $url => $response) {
            $httpClient
                ->expects(static::once())
                ->method('sendRequest')
                ->willReturnCallback(function (RequestInterface $request) use ($url, $response): Response {
                    self::assertSame('GET', $request->getMethod());
                    self::assertSame($url, $request->getUri()->__toString());
                    $this->validateRequest($request);

                    return new Response(200, ['Content-Type' => 'application/json'], $response);
                })
            ;
        }

        return $httpClient;
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function validateRequest(RequestInterface $request): void
    {
    }

    /**
     * @param list<ProfileConfig> $configs
     */
    protected function createProfileFactory(array $configs): ProfileFactory
    {
        $idx = 0;
        $mock = $this->createMock(ProfileFactory::class);
        $mock
            ->expects(static::exactly(\count($configs)))
            ->method('fromConfig')
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

    protected function createProfile(string $name = 'foo'): Profile
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
