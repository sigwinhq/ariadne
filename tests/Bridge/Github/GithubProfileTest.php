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
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\Bridge\ProfileTestCase;

/**
 * @covers \Sigwin\Ariadne\Bridge\Github\GithubProfile
 * @covers \Sigwin\Ariadne\Model\Repository
 * @covers \Sigwin\Ariadne\Model\RepositoryType
 * @covers \Sigwin\Ariadne\Model\RepositoryUser
 * @covers \Sigwin\Ariadne\Model\RepositoryVisibility
 *
 * @uses \Sigwin\Ariadne\Model\Attribute
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection
 * @uses \Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate
 * @uses \Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection
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
final class GithubProfileTest extends ProfileTestCase
{
    /**
     * @dataProvider provideUrls
     */
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

        static::assertSame('ariadne', $login->getName());
    }

    /**
     * @dataProvider provideUrls
     */
    public function testCanFetchTemplates(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            [$this->createRequest($baseUrl, 'GET', '/user/repos?per_page=100'), '[]'],
        ]);
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig($baseUrl);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        static::assertCount(1, $profile->getSummary()->getTemplates());
    }

    protected function createHttpClientForRepositoryScenario(string $name, Repository $repository): ClientInterface
    {
        return match ($name) {
            self::REPOSITORY_SCENARIO_BASIC => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => []]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_FORK => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => true, 'private' => false, 'topics' => []]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_PRIVATE => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => true, 'topics' => []]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_USERS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => []]],
                ],
                [
                    $this->createRequest(null, 'GET', '/repos/namespace1/repo1/collaborators?per_page=100'),
                    [(object) ['login' => 'theseus', 'role_name' => 'admin']],
                ],
            ]),
            self::REPOSITORY_SCENARIO_TOPICS => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => ['topic1', 'topic2']]],
                ],
            ]),
            self::REPOSITORY_SCENARIO_LANGUAGES => $this->createHttpClient([
                [
                    $this->createRequest(null, 'GET', '/user/repos?per_page=100'),
                    [(object) ['id' => $repository->id, 'full_name' => $repository->path, 'fork' => false, 'private' => false, 'topics' => [], 'language' => 'language1']],
                ],
            ]),
            default => throw new \InvalidArgumentException(sprintf('Unknown repository scenario "%1$s".', $name)),
        };
    }

    protected function provideRepositoriesAttributeChange(): iterable
    {
        $response = [];
        foreach ($this->provideValidAttributeValues() as $attribute) {
            $response[$attribute[0]] = $attribute[1];
        }
        $repository = $this->createRepository('namespace1/repo1', response: $response);

        $config = ['attribute' => ['description' => 'AAA']];
        $expected = ['description' => 'AAA'];
        yield [self::REPOSITORY_SCENARIO_BASIC, $repository, $config, $expected];
    }

    protected function provideValidOptions(): iterable
    {
        static::markTestSkipped('Github profile does not provide options');
    }

    protected function provideInvalidOptions(): iterable
    {
        static::markTestSkipped('Github profile does not provide options');
    }

    protected function provideValidAttributeValues(): iterable
    {
        return [
            ['description', 'desc'],
            ['has_discussions', false],
            ['has_downloads', false],
            ['has_issues', true],
            ['has_pages', false],
            ['has_projects', false],
            ['has_wiki', false],
        ];
    }

    protected function provideInvalidAttributeValues(): iterable
    {
        $readOnlyError = 'Attribute "%1$s" is read-only.';
        $notExistsError = 'Attribute "%1$s" does not exist.';

        return [
            ['open_issues_count', -1, $readOnlyError],
            ['stargazers_count', 10000, $readOnlyError],
            ['watchers_count', 10000, $readOnlyError],
            ['nah', 'aaa', $notExistsError],
            ['desciption', 'aaa', 'Attribute "desciption" does not exist. Did you mean "description"?'],
            ['has_pragects', true, 'Attribute "has_pragects" does not exist. Did you mean "has_pages", "has_projects"?'],
        ];
    }

    protected function validateRequest(RequestInterface $request): void
    {
        static::assertSame('token ABC', $request->getHeaderLine('Authorization'));
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
                $specs[] = array_replace_recursive($spec, $template);
            }
            /** @var list<TTemplate> $templates */
            $templates = $specs;
        } else {
            $templates = [$spec];
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
