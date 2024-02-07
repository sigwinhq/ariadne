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

namespace Sigwin\Ariadne\Test\Bridge\Github;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Github\GithubProfile;
use Sigwin\Ariadne\Model\Attribute;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\Model\Change\NamedResourceUpdate;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\Bridge\ProfileTestCase;

/**
 * @covers \Sigwin\Ariadne\Bridge\Github\GithubProfile
 * @covers \Sigwin\Ariadne\Model\Change\NamedResourceChangeTrait
 * @covers \Sigwin\Ariadne\Model\Repository
 * @covers \Sigwin\Ariadne\Model\RepositoryType
 * @covers \Sigwin\Ariadne\Model\RepositoryUser
 * @covers \Sigwin\Ariadne\Model\RepositoryVisibility
 *
 * @uses \Sigwin\Ariadne\Model\Attribute
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate
 * @uses \Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Collection\UnsortedNamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateRepositoryUserConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileSummary
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 *
 * @internal
 *
 * @small
 *
 * @psalm-import-type TTemplate from ProfileTestCase
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(GithubProfile::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(Repository::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Model\RepositoryType::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(RepositoryUser::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Model\RepositoryVisibility::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Model\Change\NamedResourceChangeTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Attribute::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(NamedResourceAttributeUpdate::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Collection\UnsortedNamedResourceCollection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileClientConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProfileConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileTemplateConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileTemplateRepositoryUserConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileSummary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplate::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplateTarget::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileUser::class)]
final class GithubProfileTest extends ProfileTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideUrls')]
    public function testCanFetchApiUser(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            [$this->createRequest($baseUrl, 'GET', '/user'), '{"login": "ariadne"}'],
        ]);
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig($baseUrl);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
        $login = $profile->getApiUser();

        self::assertSame('ariadne', $login->getName());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUrls')]
    public function testCanFetchTemplates(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            [$this->createRequest($baseUrl, 'GET', '/user/repos?per_page=100'), '[]'],
        ]);
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig($baseUrl);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        self::assertCount(1, $profile->getSummary()->getTemplates());
    }

    protected function createHttpClientForRepositoryScenario(string $name, Repository $repository): ClientInterface
    {
        return match ($name) {
            self::REPOSITORY_SCENARIO_BASIC => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_EXTENDED => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/repos/namespace1/repo1'),
                    ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => false, 'allow_squash_merge' => true],
                ],
            ]),
            self::REPOSITORY_SCENARIO_FORK => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => true, 'private' => false, 'topics' => [], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_PRIVATE => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => true, 'topics' => [], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_ARCHIVED => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => true]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_USER => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/repos/namespace1/repo1/collaborators?per_page=100'),
                    [(object) ['login' => 'theseus', 'role_name' => 'admin']],
                ],
            ]),
            self::REPOSITORY_SCENARIO_USERS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/repos/namespace1/repo1/collaborators?per_page=100'),
                    [(object) ['login' => 'theseus', 'role_name' => 'admin'], (object) ['login' => 'ariadne', 'role_name' => 'admin']],
                ],
            ]),
            self::REPOSITORY_SCENARIO_TOPICS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => ['topic1', 'topic2'], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_LANGUAGES => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'language' => 'language1', 'archived' => false]],
                ],
            ]),
            default => throw new \InvalidArgumentException(sprintf('Unknown repository scenario "%1$s".', $name)),
        };
    }

    public static function provideVendorSpecificRepositories(): iterable
    {
        /** @var list<array{string, bool|int|string}> $values */
        $values = self::provideCanSetValidAttributesCases();
        $map = [];
        foreach ($values as $item) {
            $map[$item[0]] = $item[1];
        }

        $extendedAttributes = ['allow_squash_merge', 'allow_merge_commit', 'allow_rebase_merge', 'allow_auto_merge', 'allow_update_branch', 'delete_branch_on_merge', 'use_squash_pr_title_as_default'];
        foreach ($extendedAttributes as $extendedAttribute) {
            yield $extendedAttribute => [
                self::REPOSITORY_SCENARIO_EXTENDED,
                self::createRepository('namespace1/repo1'),
                ['attribute' => [$extendedAttribute => $map[$extendedAttribute] ?? throw new \LogicException(sprintf('Missing value for "%1$s".', $extendedAttribute))]],
            ];
        }
    }

    public static function provideCanCreatePlanAttributeChangesCases(): iterable
    {
        $repository = self::createRepositoryFromValidAttributes();

        $config = ['attribute' => ['description' => 'AAA']];
        $expected = ['description' => 'AAA'];
        yield self::REPOSITORY_SCENARIO_BASIC => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = ['attribute' => ['description' => 'AAA', 'has_wiki' => true]];
        $expected = ['description' => 'AAA'];
        yield 'single template with a multiple targets to change, one of them to actually change' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = [
            'templates' => [
                ['name' => 'disable wikis', 'target' => ['attribute' => ['description' => 'AAA', 'has_wiki' => false]], 'filter' => []],
                ['name' => 'disable discussions', 'target' => ['attribute' => ['description' => 'AAA', 'has_discussions' => false]], 'filter' => []],
                ['name' => 'enable stuff back as it was', 'target' => ['attribute' => ['has_wiki' => true, 'has_discussions' => true]], 'filter' => []],
            ],
        ];
        $expected = ['description' => 'AAA'];
        yield 'multiple templates, one does a change and then the next one undoes the change' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = [
            'templates' => [
                ['name' => 'ZZZZZ', 'target' => ['attribute' => ['has_wiki' => false]], 'filter' => []],
                ['name' => 'AAAAA', 'target' => ['attribute' => ['has_wiki' => true]], 'filter' => []],
            ],
        ];
        $expected = [];
        yield 'multiple templates, will not sort templates by name' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];
    }

    public static function provideCanPlanUserChangesCases(): iterable
    {
        $repository = self::createRepositoryFromValidAttributes(users: [['theseus', 'guest']]);
        $repositoryWithBoth = self::createRepositoryFromValidAttributes(users: [['theseus', 'admin'], ['ariadne', 'guest']]);

        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin']]];
        $expected = [
            NamedResourceUpdate::fromResource(new RepositoryUser('theseus', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', 'admin'),
            ]),
        ];
        yield 'single template with a single target to update' => [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'guest']]];
        $expected = [];
        yield 'already up to date' => [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin'], 'ariadne' => ['username' => 'ariadne', 'role' => 'admin']]];
        $expected = [
            NamedResourceUpdate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', 'admin'),
            ]),
        ];
        yield 'update two users' => [self::REPOSITORY_SCENARIO_USERS, $repositoryWithBoth, $config, $expected];

        $config = ['user' => ['ariadne' => ['username' => 'ariadne', 'role' => 'admin'], 'theseus' => ['username' => 'theseus', 'role' => 'guest']]];
        $expected = [
            NamedResourceCreate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), null, 'admin'),
            ]),
        ];
        yield 'add a user' => [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        $config = ['user' => ['ariadne' => ['username' => 'ariadne', 'role' => 'admin']]];
        $expected = [
            NamedResourceCreate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), null, 'admin'),
            ]),
            NamedResourceDelete::fromResource(new RepositoryUser('theseus', 'guest'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', null),
            ]),
        ];
        yield 'add a user, delete a user' => [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];
    }

    public static function provideCanSetValidOptionsCases(): iterable
    {
        self::markTestSkipped('Github profile does not provide options');
    }

    public static function provideCannotSetInvalidOptionsCases(): iterable
    {
        self::markTestSkipped('Github profile does not provide options');
    }

    public static function provideCanSetValidAttributesCases(): iterable
    {
        yield 'allow_squash_merge' => ['allow_squash_merge', true];
        yield 'allow_merge_commit' => ['allow_merge_commit', true];
        yield 'allow_rebase_merge' => ['allow_rebase_merge', true];
        yield 'allow_auto_merge' => ['allow_auto_merge', true];
        yield 'allow_update_branch' => ['allow_update_branch', true];
        yield 'delete_branch_on_merge' => ['delete_branch_on_merge', true];
        yield 'use_squash_pr_title_as_default' => ['use_squash_pr_title_as_default', true];
        yield 'description' => ['description', 'desc'];
        yield 'has_discussions' => ['has_discussions', true];
        yield 'has_downloads' => ['has_downloads', true];
        yield 'has_issues' => ['has_issues', true];
        yield 'has_pages' => ['has_pages', true];
        yield 'has_projects' => ['has_projects', true];
        yield 'has_wiki' => ['has_wiki', true];
    }

    public static function provideCannotSetInvalidAttributesCases(): iterable
    {
        $readOnlyError = 'Attribute "%1$s" is read-only.';
        $notExistsError = 'Attribute "%1$s" does not exist.';

        yield 'open_issues_count' => ['open_issues_count', -1, $readOnlyError];
        yield 'stargazers_count' => ['stargazers_count', 10000, $readOnlyError];
        yield 'watchers_count' => ['watchers_count', 10000, $readOnlyError];
        yield 'nah' => ['nah', 'aaa', $notExistsError];
        yield 'desciption' => ['desciption', 'aaa', 'Attribute "desciption" does not exist. Did you mean "description"?'];
        yield 'has_pragects' => ['has_pragects', true, 'Attribute "has_pragects" does not exist. Did you mean "has_pages", "has_projects"?'];
    }

    protected function validateRequest(RequestInterface $request): void
    {
        self::assertSame('token ABC', $request->getHeaderLine('Authorization'));
    }

    protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile
    {
        return GithubProfile::fromConfig($config, $client, $factory, $cachePool);
    }

    protected function createConfig(?string $url = null, ?array $templates = null, ?array $options = null, ?array $attribute = null, ?array $user = null, ?array $filter = null): ProfileConfig
    {
        $spec = ['name' => 'foo', 'filter' => $filter ?? [], 'target' => ['attribute' => $attribute ?? [], 'user' => $user ?? []]];
        if ($templates !== null) {
            $specs = [];
            foreach ($templates as $template) {
                $specs[$template['name']] = array_replace_recursive($spec, $template);
            }
            /** @var array<string, TTemplate> $templates */
            $templates = $specs;
        } else {
            $templates = [$spec['name'] => $spec];
        }

        $config = [
            'type' => 'github',
            'name' => 'GH',
            'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => $options ?? []],
            'templates' => $templates,
        ];
        if ($url !== null) {
            $config['client']['url'] = $url;
        }

        return ProfileConfig::fromArray($config);
    }

    protected function createRequest(?string $baseUrl, string $method, string $path): string
    {
        return sprintf('%1$s %2$s%3$s%4$s', $method, $baseUrl ?? 'https://api.github.com', $baseUrl === null ? '' : '/api/v3', $path);
    }
}
