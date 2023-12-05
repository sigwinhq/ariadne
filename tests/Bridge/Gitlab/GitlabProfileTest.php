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

namespace Sigwin\Ariadne\Test\Bridge\Gitlab;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile;
use Sigwin\Ariadne\Exception\ConfigException;
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
 * @internal
 *
 * @covers \Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile
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
 * @small
 *
 * @psalm-import-type TTemplate from ProfileTestCase
 */
final class GitlabProfileTest extends ProfileTestCase
{
    /**
     * @dataProvider provideCanSetValidOptionsCases
     */
    public function testCanSetValidOptions(string $name, bool|string $value): void
    {
        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(options: [$name => $value]);

        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        self::assertSame($config->name, $profile->getName());
    }

    /**
     * @dataProvider provideCannotSetInvalidOptionsCases
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
     * @dataProvider provideUrls
     */
    public function testCanFetchApiUser(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            [$this->createRequest($baseUrl, 'GET', '/user'), '{"username": "ariadne"}'],
        ]);
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig($baseUrl);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
        $login = $profile->getApiUser();

        self::assertSame('ariadne', $login->getName());
    }

    /**
     * @dataProvider provideUrls
     */
    public function testCanFetchTemplates(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            [$this->createRequest($baseUrl, 'GET', '/projects?membership=false&owned=true&per_page=50'), '[]'],
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
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_FORK => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false, 'forked_from_project' => (object) ['id' => 1]]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_PRIVATE => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'private', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_ARCHIVED => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => true]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_USER => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/projects/12345/members/all?per_page=50'),
                    [(object) ['username' => 'theseus', 'access_level' => 50]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_USERS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/projects/12345/members/all?per_page=50'),
                    [(object) ['username' => 'theseus', 'access_level' => 50], (object) ['username' => 'ariadne', 'access_level' => 50]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_TOPICS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => ['topic1', 'topic2'], 'archived' => false]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_LANGUAGES => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/projects?membership=false&owned=true&per_page=50'),
                    [(object) ['id' => $repository->id, 'visibility' => 'public', 'path_with_namespace' => $repository->path, 'topics' => [], 'archived' => false]],
                ],
                [
                    $this->createRequest(null, 'GET', '/projects/12345/languages'),
                    ['language1' => 100],
                ],
            ]),
            default => throw new \InvalidArgumentException(sprintf('Unknown repository scenario "%1$s".', $name)),
        };
    }

    public static function provideCanCreatePlanAttributeChangesCases(): iterable
    {
        $repository = self::createRepositoryFromValidAttributes();

        $config = ['attribute' => ['description' => 'AAA']];
        $expected = ['description' => 'AAA'];
        yield 'single template with a single target to change' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = ['attribute' => ['description' => 'AAA', 'wiki_enabled' => true]];
        $expected = ['description' => 'AAA'];
        yield 'single template with a multiple targets to change, one of them to actually change' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = [
            'templates' => [
                ['name' => 'disable wikis', 'target' => ['attribute' => ['description' => 'AAA', 'wiki_enabled' => false]], 'filter' => []],
                ['name' => 'disable packages', 'target' => ['attribute' => ['description' => 'AAA', 'packages_enabled' => false]], 'filter' => []],
                ['name' => 'enable stuff back as it was', 'target' => ['attribute' => ['wiki_enabled' => true, 'packages_enabled' => true]], 'filter' => []],
            ],
        ];
        $expected = ['description' => 'AAA'];
        yield 'multiple templates, one does a change and then the next one undoes the change' => [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        $config = [
            'templates' => [
                ['name' => 'ZZZZZ', 'target' => ['attribute' => ['wiki_enabled' => false]], 'filter' => []],
                ['name' => 'AAAAA', 'target' => ['attribute' => ['wiki_enabled' => true]], 'filter' => []],
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
        yield 'membership' => ['membership', false];
        yield 'owned' => ['owned', true];
    }

    public static function provideCannotSetInvalidOptionsCases(): iterable
    {
        $error = 'The option "%1$s" with value "aa" is expected to be of type "boolean", but is of type "string".';

        yield 'membership' => ['membership', 'aa', $error];
        yield 'owned' => ['owned', 'aa', $error];
        yield 'unknown' => ['unknown', 'aa', 'Unrecognized option "%1$s" under "profiles.GL,client.options". Permissible values: "membership", "owned"'];
    }

    public static function provideCanSetValidAttributesCases(): iterable
    {
        yield 'description' => ['description', 'foo'];
        yield 'issues_enabled' => ['issues_enabled', true];
        yield 'lfs_enabled' => ['lfs_enabled', true];
        yield 'merge_requests_enabled' => ['merge_requests_enabled', true];
        yield 'container_registry_enabled' => ['container_registry_enabled', true];
        yield 'wiki_enabled' => ['wiki_enabled', true];
        yield 'service_desk_enabled' => ['service_desk_enabled', true];
        yield 'snippets_enabled' => ['snippets_enabled', true];
        yield 'packages_enabled' => ['packages_enabled', true];
        yield 'remove_source_branch_after_merge' => ['remove_source_branch_after_merge', true];
        yield 'only_allow_merge_if_pipeline_succeeds' => ['only_allow_merge_if_pipeline_succeeds', true];
        yield 'only_allow_merge_if_all_discussions_are_resolved' => ['only_allow_merge_if_all_discussions_are_resolved', true];
        yield 'allow_merge_on_skipped_pipeline' => ['allow_merge_on_skipped_pipeline', true];
        yield 'monitor_access_level' => ['monitor_access_level', 'public'];
        yield 'pages_access_level' => ['pages_access_level', 'public'];
        yield 'forking_access_level' => ['forking_access_level', 'public'];
        yield 'analytics_access_level' => ['analytics_access_level', 'public'];
        yield 'security_and_compliance_access_level' => ['security_and_compliance_access_level', 'public'];
        yield 'environments_access_level' => ['environments_access_level', 'public'];
        yield 'feature_flags_access_level' => ['feature_flags_access_level', 'public'];
        yield 'infrastructure_access_level' => ['infrastructure_access_level', 'public'];
        yield 'releases_access_level' => ['releases_access_level', 'public'];
        yield 'merge_method' => ['merge_method', 'ff'];
        yield 'squash_option' => ['squash_option', 'always'];
        yield 'squash_commit_template' => ['squash_commit_template', '%{title} (%{reference})'];
    }

    public static function provideCannotSetInvalidAttributesCases(): iterable
    {
        $readOnlyError = 'Attribute "%1$s" is read-only.';
        $notExistsError = 'Attribute "%1$s" does not exist.';

        yield 'star_count' => ['star_count', 10000, $readOnlyError];
        yield 'nah' => ['nah', 'aaa', $notExistsError];
        yield 'desciption' => ['desciption', 'aaa', 'Attribute "desciption" does not exist. Did you mean "description"?'];
    }

    protected function validateRequest(RequestInterface $request): void
    {
        self::assertSame('ABC', $request->getHeaderLine('PRIVATE-TOKEN'));
    }

    protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile
    {
        return GitlabProfile::fromConfig($config, $client, $factory, $cachePool);
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
            'type' => 'gitlab',
            'name' => 'GL',
            'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => $options ?? []],
            'templates' => $templates,
        ];
        if ($url !== null) {
            $config['client']['url'] = $url;
        }

        return ProfileConfig::fromArray($config);
    }

    protected function createRequest(?string $baseUrl, string $method, string $path): string
    {
        return sprintf('%1$s %2$s/api/v4%3$s', $method, $baseUrl ?? 'https://gitlab.com', $path);
    }
}
