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

namespace Sigwin\Ariadne\Test\Bridge;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Exception\ConfigException;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\Model\Change\NamedResourceUpdate;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

/**
 * @psalm-type TOptions = array<string, bool|string>
 * @psalm-type TAttribute = array<string, bool|int|string>
 * @psalm-type TUser = array<string, array{"username": string, "role": string}>
 * @psalm-type TFilter = array{languages?: list<string>}
 * @psalm-type TTemplate = array{name: string, filter: TFilter, target: array{attribute: TAttribute}}
 * @psalm-type TConfig = array{templates?: list<TTemplate>, options?: TOptions, attribute?: TAttribute, user?: TUser, filter?: TFilter}
 */
abstract class ProfileTestCase extends TestCase
{
    use ModelGeneratorTrait;

    protected const REPOSITORY_SCENARIO_BASIC = 'basic repository';
    protected const REPOSITORY_SCENARIO_FORK = 'forked repository';
    protected const REPOSITORY_SCENARIO_PRIVATE = 'private repository';
    protected const REPOSITORY_SCENARIO_USER = 'repository with a single user';
    protected const REPOSITORY_SCENARIO_USERS = 'repository with multiple users';
    protected const REPOSITORY_SCENARIO_TOPICS = 'repository with topics';
    protected const REPOSITORY_SCENARIO_LANGUAGES = 'repository with languages';
    protected const REPOSITORY_SCENARIO_ARCHIVED = 'archived repository';

    /**
     * @return iterable<array-key, array{0: string, 1: Repository, 2?: TConfig}>
     */
    protected function provideRepositories(): iterable
    {
        yield [self::REPOSITORY_SCENARIO_BASIC, $this->createRepository('namespace1/repo1')];
        yield [self::REPOSITORY_SCENARIO_FORK, $this->createRepository('namespace1/repo1', type: 'fork')];
        yield [self::REPOSITORY_SCENARIO_PRIVATE, $this->createRepository('namespace1/repo1', visibility: 'private')];
        yield [self::REPOSITORY_SCENARIO_ARCHIVED, $this->createRepository('namespace1/repo1', archived: true)];
        yield [
            self::REPOSITORY_SCENARIO_USER,
            $this->createRepository('namespace1/repo1', users: [['theseus', 'admin']]),
            ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin']]],
        ];
        yield [self::REPOSITORY_SCENARIO_TOPICS, $this->createRepository('namespace1/repo1', topics: ['topic1', 'topic2'])];
        yield [
            self::REPOSITORY_SCENARIO_LANGUAGES,
            $this->createRepository('namespace1/repo1', languages: ['language1']),
            ['filter' => ['languages' => ['language1']]],
        ];
    }

    /**
     * @dataProvider provideRepositories
     *
     * @param TConfig $config
     */
    public function testCanCreateRepository(string $name, Repository $fixture, array $config = []): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $fixture, $config);

        foreach ($profile as $repository) {
            if ($fixture->getName() === $repository->getName()) {
                static::assertSame($fixture->type->value, $repository->type->value);
                static::assertSame($fixture->visibility->value, $repository->visibility->value);
                static::assertEmpty($fixture->users->diff($repository->users));
                static::assertSame($fixture->id, $repository->id);
                static::assertSame($fixture->path, $repository->path);
                static::assertSame($fixture->topics, $repository->topics);
                static::assertSame($fixture->languages, $repository->languages);
                static::assertSame($fixture->archived, $repository->archived);

                return;
            }
        }

        static::fail(sprintf('Repository for scenario "%1$s" not found in profile.', $name));
    }

    /**
     * @group plan
     *
     * @dataProvider provideRepositoriesAttributeChange
     *
     * @param array<string, bool|int|string> $expected
     * @param TConfig                        $config
     */
    public function testCanCreatePlanAttributeChanges(string $name, Repository $fixture, array $config, array $expected): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $fixture, $config);
        $plan = $profile->plan($fixture);

        static::assertSame($fixture->getName(), $plan->getResource()->getName());
        static::assertSame(\count($expected) === 0, $plan->isActual());

        $changes = iterator_to_array($plan->filter(NamedResourceAttributeUpdate::class));
        static::assertTrue(array_is_list($changes), 'Changes must be a list.');

        // filter out changes which are already actual
        foreach ($changes as $idx => $change) {
            if ($change->isActual()) {
                unset($changes[$idx]);
            }
        }

        // replace the change object with the target value
        $actual = [];
        foreach ($changes as $change) {
            $actual[$change->getResource()->getName()] = $change->expected;
        }

        static::assertSame($expected, $actual);
    }

    /**
     * @group plan
     * @group user
     *
     * @dataProvider provideRepositoriesUserChange
     *
     * @param array<string, bool|int|string> $expected
     * @param TConfig                        $config
     */
    public function testCanPlanUserChanges(string $name, Repository $repository, array $config, array $expected): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $repository, $config);
        $plan = $profile->plan($repository);

        static::assertSame($repository->getName(), $plan->getResource()->getName());
        static::assertSame(\count($expected) === 0, $plan->isActual());

        $actual = iterator_to_array($plan);

        static::assertEqualsIgnoringCase($expected, $actual);
    }

    /**
     * @dataProvider provideValidOptions
     */
    public function testCanSetValidOptions(string $name, bool|string $value): void
    {
        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(options: [$name => $value]);

        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        static::assertSame($config->name, $profile->getName());
    }

    /**
     * @dataProvider provideInvalidOptions
     *
     * @uses \Sigwin\Ariadne\Exception\ConfigException
     */
    public function testCannotSetInvalidOptions(string $name, bool|string $value, string $message): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf($message, $name));

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(options: [$name => $value]);

        $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
    }

    /**
     * @dataProvider provideValidAttributeValues
     */
    public function testCanSetValidAttributes(string $name, bool|string $value): void
    {
        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(attribute: [$name => $value]);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        static::assertSame($config->name, $profile->getName());
    }

    /**
     * @dataProvider provideInvalidAttributeValues
     */
    public function testCannotSetInvalidAttributes(string $name, int|bool|string $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^'.preg_quote(sprintf($message, $name), '/').'$/');

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(attribute: [$name => $value]);

        $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
    }

    /**
     * @return iterable<string, array{0: null|string}>
     */
    protected function provideUrls(): iterable
    {
        yield 'default' => [null];
        yield 'custom' => ['https://example.com'];
    }

    /**
     * @param list<array{string, string}>|null $users
     */
    protected function createRepositoryFromValidAttributes(?array $users = null): Repository
    {
        $response = [];
        foreach ($this->provideValidAttributeValues() as $attribute) {
            $response[$attribute[0]] = $attribute[1];
        }

        return $this->createRepository('namespace1/repo1', response: $response, users: $users);
    }

    abstract protected function validateRequest(RequestInterface $request): void;

    /**
     * @return iterable<array-key, array{string, bool|string}>
     */
    abstract protected function provideValidOptions(): iterable;

    /**
     * @return iterable<array-key, array{string, bool|string, string}>
     */
    abstract protected function provideInvalidOptions(): iterable;

    /**
     * @return iterable<array-key, array{string, bool|string}>
     */
    abstract protected function provideValidAttributeValues(): iterable;

    /**
     * @return iterable<array-key, array{string, int|bool|string, string}>
     */
    abstract protected function provideInvalidAttributeValues(): iterable;

    /**
     * @return iterable<array-key, array{0: string, 1: Repository, 2: TConfig, 3: array<string, bool|int|string>}>
     */
    abstract protected function provideRepositoriesAttributeChange(): iterable;

    /**
     * @return iterable<array-key, array{
     *     string,
     *     Repository,
     *     TConfig,
     *     list<NamedResourceCreate<RepositoryUser, NamedResourceAttributeUpdate>|NamedResourceUpdate<RepositoryUser, NamedResourceAttributeUpdate>|NamedResourceDelete<RepositoryUser, NamedResourceAttributeUpdate>>
     * }>
     */
    abstract protected function provideRepositoriesUserChange(): iterable;

    abstract protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile;

    /**
     * @param null|list<TTemplate> $templates
     * @param null|TOptions        $options
     * @param null|TAttribute      $attribute
     * @param null|TUser           $user
     * @param null|TFilter         $filter
     */
    abstract protected function createConfig(?string $url = null, ?array $templates = null, ?array $options = null, ?array $attribute = null, ?array $user = null, ?array $filter = null): ProfileConfig;

    abstract protected function createRequest(?string $baseUrl, string $method, string $path): string;

    abstract protected function createHttpClientForRepositoryScenario(string $name, Repository $repository): ClientInterface;

    /**
     * @param TConfig $config
     */
    private function createProfileForRepositoryScenario(string $name, Repository $fixture, array $config): Profile
    {
        $profileConfig = $this->createConfig(templates: $config['templates'] ?? null, options: $config['options'] ?? null, attribute: $config['attribute'] ?? null, user: $config['user'] ?? null, filter: $config['filter'] ?? null);
        $repositories = array_fill(0, \count($profileConfig->templates), [$fixture]);

        $httpClient = $this->createHttpClientForRepositoryScenario($name, $fixture);
        $factory = $this->createTemplateFactory(repositories: $repositories);
        $cachePool = $this->createActiveCachePool();

        return $this->createProfileInstance($profileConfig, $httpClient, $factory, $cachePool);
    }
}
