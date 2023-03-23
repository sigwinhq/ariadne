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

use Nyholm\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\Bridge\ProfileTestCase;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile
 *
 * @uses \Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileSummary
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 *
 * @small
 */
final class GitlabProfileTest extends ProfileTestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testCanFetchApiUser(?string $baseUrl): void
    {
        $httpClient = $this->createHttpClient([
            $this->createUrl($baseUrl, '/user') => '{"username": "ariadne"}',
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
            $this->createUrl($baseUrl, '/projects?membership=false&owned=true&per_page=50') => '[]',
        ]);
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig($baseUrl);
        $profile = $this->createProfileInstance($config, $httpClient, $factory, $cachePool);

        static::assertCount(1, $profile->getSummary()->getTemplates());
    }

    public function testCanRecognizeInvalidMembershipOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(options: ['membership' => 'aa']);

        $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
    }

    public function testCanRecognizeInvalidOwnedOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(options: ['owned' => 'aa']);

        $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
    }

    /**
     * @return iterable<array{string, bool|string}>
     */
    protected function provideValidAttributeValues(): iterable
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

    /**
     * @return iterable<array{string, int|bool|string}>
     */
    protected function provideInvalidAttributeValues(): iterable
    {
        return [
            ['star_count', 10000],
        ];
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
                ->willReturnCallback(static function (RequestInterface $request) use ($url, $response): Response {
                    self::assertSame('GET', $request->getMethod());
                    self::assertSame($url, $request->getUri()->__toString());
                    self::assertSame('ABC', $request->getHeaderLine('PRIVATE-TOKEN'));

                    return new Response(200, ['Content-Type' => 'application/json'], $response);
                })
            ;
        }

        return $httpClient;
    }

    protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile
    {
        return GitlabProfile::fromConfig($config, $client, $factory, $cachePool);
    }

    protected function createConfig(?string $url = null, ?array $options = null, ?array $attribute = null): ProfileConfig
    {
        $config = [
            'type' => 'gitlab',
            'name' => 'GL',
            'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => $options ?? []],
            'templates' => [
                ['name' => 'foo', 'filter' => [], 'target' => ['attribute' => $attribute ?? []]],
            ],
        ];
        if ($url !== null) {
            $config['client']['url'] = $url;
        }

        return ProfileConfig::fromArray($config);
    }

    protected function createUrl(?string $baseUrl, string $path): string
    {
        return sprintf('%1$s/api/v4%2$s', $baseUrl ?? 'https://gitlab.com', $path);
    }
}
