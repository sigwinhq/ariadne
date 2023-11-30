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

        // single template with a single target to change
        $config = ['attribute' => ['description' => 'AAA']];
        $expected = ['description' => 'AAA'];
        yield [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        // single template with a multiple targets to change, one of them to actually change
        $config = ['attribute' => ['description' => 'AAA', 'wiki_enabled' => true]];
        $expected = ['description' => 'AAA'];
        yield [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        // multiple templates, one does a change and then the next one undoes the change
        $config = [
            'templates' => [
                ['name' => 'disable wikis', 'target' => ['attribute' => ['description' => 'AAA', 'wiki_enabled' => false]], 'filter' => []],
                ['name' => 'disable packages', 'target' => ['attribute' => ['description' => 'AAA', 'packages_enabled' => false]], 'filter' => []],
                ['name' => 'enable stuff back as it was', 'target' => ['attribute' => ['wiki_enabled' => true, 'packages_enabled' => true]], 'filter' => []],
            ],
        ];
        $expected = ['description' => 'AAA'];
        yield [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];

        // multiple templates, will not sort templates by name
        $config = [
            'templates' => [
                ['name' => 'ZZZZZ', 'target' => ['attribute' => ['wiki_enabled' => false]], 'filter' => []],
                ['name' => 'AAAAA', 'target' => ['attribute' => ['wiki_enabled' => true]], 'filter' => []],
            ],
        ];
        $expected = [];
        yield [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];
    }

    public static function provideCanPlanUserChangesCases(): iterable
    {
        $repository = self::createRepositoryFromValidAttributes(users: [['theseus', 'guest']]);
        $repositoryWithBoth = self::createRepositoryFromValidAttributes(users: [['theseus', 'admin'], ['ariadne', 'guest']]);

        // single template with a single target to update
        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin']]];
        $expected = [
            NamedResourceUpdate::fromResource(new RepositoryUser('theseus', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', 'admin'),
            ]),
        ];
        yield [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        // already up to date
        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'guest']]];
        $expected = [];
        yield [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        // update two users
        $config = ['user' => ['theseus' => ['username' => 'theseus', 'role' => 'admin'], 'ariadne' => ['username' => 'ariadne', 'role' => 'admin']]];
        $expected = [
            NamedResourceUpdate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', 'admin'),
            ]),
        ];
        yield [self::REPOSITORY_SCENARIO_USERS, $repositoryWithBoth, $config, $expected];

        // add a user
        $config = ['user' => ['ariadne' => ['username' => 'ariadne', 'role' => 'admin'], 'theseus' => ['username' => 'theseus', 'role' => 'guest']]];
        $expected = [
            NamedResourceCreate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), null, 'admin'),
            ]),
        ];
        yield [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];

        // add a user, delete a user
        $config = ['user' => ['ariadne' => ['username' => 'ariadne', 'role' => 'admin']]];
        $expected = [
            NamedResourceCreate::fromResource(new RepositoryUser('ariadne', 'admin'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), null, 'admin'),
            ]),
            NamedResourceDelete::fromResource(new RepositoryUser('theseus', 'guest'), [
                new NamedResourceAttributeUpdate(new Attribute('role'), 'guest', null),
            ]),
        ];
        yield [self::REPOSITORY_SCENARIO_USER, $repository, $config, $expected];
    }

    public static function provideCanSetValidOptionsCases(): iterable
    {
        return [
            ['membership', false],
            ['owned', true],
        ];
    }

    public static function provideCannotSetInvalidOptionsCases(): iterable
    {
        $error = 'The option "%1$s" with value "aa" is expected to be of type "boolean", but is of type "string".';

        return [
            ['membership', 'aa', $error],
            ['owned', 'aa', $error],
            ['unknown', 'aa', 'Unrecognized option "%1$s" under "profiles.GL,client.options". Permissible values: "membership", "owned"'],
        ];
    }

    public static function provideCanSetValidAttributesCases(): iterable
    {
        return [
            ['description', 'foo'],
            ['issues_enabled', true],
            ['lfs_enabled', true],
            ['merge_requests_enabled', true],
            ['container_registry_enabled', true],
            ['wiki_enabled', true],
            ['service_desk_enabled', true],
            ['snippets_enabled', true],
            ['packages_enabled', true],
            ['remove_source_branch_after_merge', true],
            ['only_allow_merge_if_pipeline_succeeds', true],
            ['only_allow_merge_if_all_discussions_are_resolved', true],
            ['allow_merge_on_skipped_pipeline', true],
            ['monitor_access_level', 'public'],
            ['pages_access_level', 'public'],
            ['forking_access_level', 'public'],
            ['analytics_access_level', 'public'],
            ['security_and_compliance_access_level', 'public'],
            ['environments_access_level', 'public'],
            ['feature_flags_access_level', 'public'],
            ['infrastructure_access_level', 'public'],
            ['releases_access_level', 'public'],
            ['merge_method', 'ff'],
            ['squash_option', 'always'],
            ['squash_commit_template', '%{title} (%{reference})'],
        ];
    }

    public static function provideCannotSetInvalidAttributesCases(): iterable
    {
        $readOnlyError = 'Attribute "%1$s" is read-only.';
        $notExistsError = 'Attribute "%1$s" does not exist.';

        return [
            ['star_count', 10000, $readOnlyError],
            ['nah', 'aaa', $notExistsError],
            ['desciption', 'aaa', 'Attribute "desciption" does not exist. Did you mean "description"?'],
        ];
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
