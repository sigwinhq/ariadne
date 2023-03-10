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
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile;
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\ProfileTemplateFactory;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Bridge\Gitlab\GitlabProfile
 *
 * @uses \Sigwin\Ariadne\Model\Collection\ProfileTemplateCollection
 * @uses \Sigwin\Ariadne\Model\Collection\RepositoryCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileSummary
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 *
 * @small
 */
final class GitlabProfileTest extends TestCase
{
    /**
     * @dataProvider getUrls
     */
    public function testCanFetchApiUser(?string $baseUrl): void
    {
        $httpClient = $this->mockHttpClient([
            $this->generateUrl($baseUrl, '/user') => '{"username": "ariadne"}',
        ]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = $this->generateConfig($baseUrl);

        $profile = GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);
        $login = $profile->getApiUser();

        static::assertSame('ariadne', $login->username);
    }

    /**
     * @dataProvider getUrls
     */
    public function testCanFetchTemplates(?string $baseUrl): void
    {
        $httpClient = $this->mockHttpClient([
            $this->generateUrl($baseUrl, '/projects?membership=false&owned=true&per_page=50') => '[]',
        ]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = $this->generateConfig($baseUrl);

        $profile = GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);

        static::assertCount(1, $profile->getSummary()->getTemplates());
    }

    public function testCanRecognizeInvalidMembershipOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $httpClient = $this->mockHttpClient();
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'gitlab', 'name' => 'GL', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => ['membership' => 'aa']], 'templates' => []]);

        GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);
    }

    public function testCanRecognizeInvalidOwnedOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $httpClient = $this->mockHttpClient();
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'gitlab', 'name' => 'GL', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => ['owned' => 'aa']], 'templates' => []]);

        GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);
    }

    public function testCanSetReadWriteAttributes(): void
    {
        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'gitlab', 'name' => 'GL', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => ['description' => 'desc']]],
        ]]);

        $profile = GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);

        static::assertSame('GL', $profile->getName());
    }

    public function testCannotSetReadOnlyAttributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute "star_count" is read-only.');

        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'gitlab', 'name' => 'GL', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => ['star_count' => 1000]]],
        ]]);

        GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);
    }

    public function testWillGetDidYouMeanWhenSettingAttributesWithATypo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute "desciption" does not exist. Did you mean "description"?');

        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'gitlab', 'name' => 'GL', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => ['desciption' => 'desc']]],
        ]]);

        GitlabProfile::fromConfig($config, $httpClient, $factory, $cachePool);
    }

    /**
     * @return iterable<string, array{0: null|string}>
     */
    public function getUrls(): iterable
    {
        yield 'default' => [null];
        yield 'custom' => ['https://example.com'];
    }

    /**
     * @param array<string, string> $requests
     */
    private function mockHttpClient(array $requests = []): ClientInterface
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

    private function mockTemplateFactory(): ProfileTemplateFactory
    {
        $factory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $factory
            ->method('create')
            ->willReturn(new ProfileTemplate(
                'foo',
                ProfileTemplateTargetConfig::fromArray(['attribute' => []]),
                RepositoryCollection::fromArray([]),
            ))
        ;

        return $factory;
    }

    private function mockCachePool(): CacheItemPoolInterface
    {
        return $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
    }

    private function generateConfig(?string $url = null): ProfileConfig
    {
        $config = [
            'type' => 'gitlab',
            'name' => 'GL',
            'client' => ['auth' => ['token' => 'ABC', 'type' => 'http_token'], 'options' => []],
            'templates' => [
                ['name' => 'foo', 'filter' => [], 'target' => ['attribute' => []]],
            ],
        ];
        if ($url !== null) {
            $config['client']['url'] = $url;
        }

        return ProfileConfig::fromArray($config);
    }

    private function generateUrl(?string $baseUrl, string $path): string
    {
        return sprintf('%1$s/api/v4%2$s', $baseUrl ?? 'https://gitlab.com', $path);
    }
}
