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
use function PHPUnit\Framework\at;

trait ModelGeneratorTrait
{
    /**
     * @param list<array{string, array<Repository>}> $list
     *
     * @return NamedResourceCollection<ProfileTemplate>
     */
    protected function createTemplates(array $list = []): NamedResourceCollection
    {
        return SortedNamedResourceCollection::fromArray(array_map($this->createTemplate(...), array_column($list, 0), array_column($list, 1)));
    }

    /**
     * @param array<Repository> $repositories
     */
    protected function createTemplate(string $name, array $attribute = [], array $repositories = []): ProfileTemplate
    {
        $evaluator = $this->getMockBuilder(Evaluator::class)->getMock();
        $evaluator
            ->method('evaluate')
            ->willReturnCallback(function (string $expression, array $context) {
                return $context[$expression] ?? $expression;
            });

        return new ProfileTemplate(
            $name,
            ProfileTemplateTarget::fromConfig(ProfileTemplateTargetConfig::fromArray(['attribute' => $attribute]), $evaluator),
            SortedNamedResourceCollection::fromArray($repositories),
        );
    }

    /**
     * @param array<Repository> $repositories
     */
    protected function createTemplateFactory(array $attribute = [], array $repositories = []): ProfileTemplateFactory
    {
        $factory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $factory
            ->method('fromConfig')
            ->willReturn($this->createTemplate('foo', attribute: $attribute, repositories: $repositories))
        ;

        return $factory;
    }

    protected function createActiveCachePool(): CacheItemPoolInterface
    {
        $mock = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $mock
            ->expects(self::atLeastOnce())
            ->method('getItem')
        ;

        return $mock;
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
     * @param null|list<string> $languages
     */
    protected function createRepository(string $path, ?string $type = null, ?string $visibility = null, ?array $users = null, ?array $topics = null, ?array $languages = null): Repository
    {
        return new Repository(
            ['path' => $path, 'description' => 'ABC'],
            $type !== null ? RepositoryType::from($type) : RepositoryType::SOURCE,
            $visibility !== null ? RepositoryVisibility::from($visibility) : RepositoryVisibility::PUBLIC,
            $this->createUsers($users ?? []),
            12345,
            $path,
            $topics ?? [],
            $languages ?? []
        );
    }

    /**
     * @param list<array{string, string|array<int|object>}> $items
     */
    protected function createHttpClient(array $items = []): ClientInterface
    {
        $idx = 0;

        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClient
            ->expects(self::exactly(\count($items)))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$idx, $items): Response {
                [$requestSpec, $response] = $items[$idx++];
                $parts = explode(' ', $requestSpec, 2);
                if (\count($parts) !== 2) {
                    throw new \InvalidArgumentException('Invalid request, expected "METHOD PATH"');
                }
                [$method, $url] = $parts;

                self::assertSame($method, $request->getMethod());
                self::assertSame($url, $request->getUri()->__toString());
                $this->validateRequest($request);

                if (\is_array($response)) {
                    $response = json_encode($response, \JSON_THROW_ON_ERROR);
                }

                return new Response(200, ['Content-Type' => 'application/json'], $response);
            })
        ;

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
