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
    protected const REPOSITORY_SCENARIO_EXTENDED = 'extended repository';
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
    public static function provideRepositories(): iterable
    {
        yield self::REPOSITORY_SCENARIO_BASIC => [self::REPOSITORY_SCENARIO_BASIC, self::createRepository('namespace1/repo1')];
        yield self::REPOSITORY_SCENARIO_FORK => [self::REPOSITORY_SCENARIO_FORK, self::createRepository('namespace1/repo1', type: 'fork')];
        yield self::REPOSITORY_SCENARIO_PRIVATE => [self::REPOSITORY_SCENARIO_PRIVATE, self::createRepository('namespace1/repo1', visibility: 'private')];
        yield self::REPOSITORY_SCENARIO_ARCHIVED => [self::REPOSITORY_SCENARIO_ARCHIVED, self::createRepository('namespace1/repo1', archived: true)];
        yield self::REPOSITORY_SCENARIO_USER => [
            self::REPOSITORY_SCENARIO_USER,
            self::createRepository('namespace1/repo1', users: [['theseus', 'admin']]),
            ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin']]],
        ];
        yield self::REPOSITORY_SCENARIO_TOPICS => [self::REPOSITORY_SCENARIO_TOPICS, self::createRepository('namespace1/repo1', topics: ['topic1', 'topic2'])];
        yield self::REPOSITORY_SCENARIO_LANGUAGES => [
            self::REPOSITORY_SCENARIO_LANGUAGES,
            self::createRepository('namespace1/repo1', languages: ['language1']),
            ['filter' => ['languages' => ['language1']]],
        ];
    }

    /**
     * @return iterable<array-key, array{0: string, 1: Repository, 2?: TConfig}>
     */
    public static function provideVendorSpecificRepositories(): iterable
    {
        return [];
    }

    /**
     * @dataProvider provideRepositories
     * @dataProvider provideVendorSpecificRepositories
     *
     * @param TConfig $config
     */
    public function testCanCreateRepository(string $name, Repository $fixture, array $config = []): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $fixture, $config);

        foreach ($profile as $repository) {
            if ($fixture->getName() === $repository->getName()) {
                self::assertSame($fixture->type->value, $repository->type->value);
                self::assertSame($fixture->visibility->value, $repository->visibility->value);
                self::assertEmpty($fixture->users->diff($repository->users));
                self::assertSame($fixture->id, $repository->id);
                self::assertSame($fixture->path, $repository->path);
                self::assertSame($fixture->topics, $repository->topics);
                self::assertSame($fixture->languages, $repository->languages);
                self::assertSame($fixture->archived, $repository->archived);

                return;
            }
        }

        self::fail(sprintf('Repository for scenario "%1$s" not found in profile.', $name));
    }

    /**
     * @group plan
     *
     * @dataProvider provideCanCreatePlanAttributeChangesCases
     *
     * @param array<string, bool|int|string> $expected
     * @param TConfig                        $config
     */
    public function testCanCreatePlanAttributeChanges(string $name, Repository $fixture, array $config, array $expected): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $fixture, $config);
        $plan = $profile->plan($fixture);

        self::assertSame($fixture->getName(), $plan->getResource()->getName());
        self::assertSame(\count($expected) === 0, $plan->isActual());

        $changes = iterator_to_array($plan->filter(NamedResourceAttributeUpdate::class));
        self::assertTrue(array_is_list($changes), 'Changes must be a list.');

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

        self::assertSame($expected, $actual);
    }

    /**
     * @group plan
     * @group user
     *
     * @dataProvider provideCanPlanUserChangesCases
     *
     * @param array<string, bool|int|string> $expected
     * @param TConfig                        $config
     */
    public function testCanPlanUserChanges(string $name, Repository $repository, array $config, array $expected): void
    {
        $profile = $this->createProfileForRepositoryScenario($name, $repository, $config);
        $plan = $profile->plan($repository);

        self::assertSame($repository->getName(), $plan->getResource()->getName());
        self::assertSame(\count($expected) === 0, $plan->isActual());

        $actual = iterator_to_array($plan);

        self::assertEqualsIgnoringCase($expected, $actual);
    }

    /**
     * @dataProvider provideCanSetValidAttributesCases
     */
    public function testCanSetValidAttributes(string $name, bool|string $value): void
    {
        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(attribute: [$name => $value]);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        self::assertSame($config->name, $profile->getName());
    }

    /**
     * @dataProvider provideCannotSetInvalidAttributesCases
     */
    public function testCannotSetInvalidAttributes(string $name, bool|int|string $value, string $message): void
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
    public static function provideUrls(): iterable
    {
        yield 'default' => [null];
        yield 'custom' => ['https://example.com'];
    }

    /**
     * @param null|list<array{string, string}> $users
     */
    protected static function createRepositoryFromValidAttributes(?array $users = null): Repository
    {
        $response = [];
        foreach (static::provideCanSetValidAttributesCases() as $attribute) {
            $response[$attribute[0]] = $attribute[1];
        }

        return self::createRepository('namespace1/repo1', response: $response, users: $users);
    }

    abstract protected function validateRequest(RequestInterface $request): void;

    /**
     * @return iterable<string, array{string, bool|string}>
     */
    abstract public static function provideCanSetValidOptionsCases(): iterable;

    /**
     * @return iterable<string, array{string, bool|string, string}>
     */
    abstract public static function provideCannotSetInvalidOptionsCases(): iterable;

    /**
     * @return iterable<string, array{string, bool|string}>
     */
    abstract public static function provideCanSetValidAttributesCases(): iterable;

    /**
     * @return iterable<string, array{string, bool|int|string, string}>
     */
    abstract public static function provideCannotSetInvalidAttributesCases(): iterable;

    /**
     * @return iterable<string, array{0: string, 1: Repository, 2: TConfig, 3: array<string, bool|int|string>}>
     */
    abstract public static function provideCanCreatePlanAttributeChangesCases(): iterable;

    /**
     * @return iterable<string, array{
     *     string,
     *     Repository,
     *     TConfig,
     *     list<NamedResourceCreate<RepositoryUser, NamedResourceAttributeUpdate>|NamedResourceUpdate<RepositoryUser, NamedResourceAttributeUpdate>|NamedResourceDelete<RepositoryUser, NamedResourceAttributeUpdate>>
     * }>
     */
    abstract public static function provideCanPlanUserChangesCases(): iterable;

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
