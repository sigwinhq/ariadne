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

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Github\GithubProfile;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\ProfileTemplateFactory;

/**
 * @covers \Sigwin\Ariadne\Bridge\Github\GithubProfile
 *
 * @uses \Sigwin\Ariadne\Model\Config\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 *
 * @internal
 *
 * @small
 */
final class GithubProfileTest extends TestCase
{
    /**
     * @dataProvider getUrls
     */
    public function testCanFetchApiUser(?string $baseUrl): void
    {
        $httpClient = $this->mockHttpClient([
            $this->generateUrl($baseUrl, '/user') => '{"login": "ariadne"}',
        ]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = $this->generateConfig($baseUrl);

        $profile = GithubProfile::fromConfig($config, $httpClient, $factory, $cachePool);
        $login = $profile->getApiUser();

        static::assertSame('ariadne', $login->getName());
    }

    /**
     * @dataProvider getValidAttributeValues
     */
    public function testCanSetReadWriteAttributes(string $name, bool|string $value): void
    {
        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'github', 'name' => 'GH', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => [$name => $value]]],
        ]]);

        $profile = GithubProfile::fromConfig($config, $httpClient, $factory, $cachePool);

        static::assertSame('GH', $profile->getName());
    }

    /**
     * @return list<array{string, bool|string}>
     */
    public function getValidAttributeValues(): array
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

    /**
     * @dataProvider getInvalidAttributeValues
     */
    public function testCannotSetReadOnlyAttributes(string $name, int|bool|string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Attribute "%1$s" is read-only.', $name));

        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'github', 'name' => 'GH', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => [$name => $value]]],
        ]]);

        $profile = GithubProfile::fromConfig($config, $httpClient, $factory, $cachePool);

        static::assertSame('GH', $profile->getName());
    }

    /**
     * @return list<array{string, bool|string}>
     */
    public function getInvalidAttributeValues(): array
    {
        return [
            ['open_issues_count', -1],
            ['stargazers_count', 10000],
            ['watchers_count', 10000],
        ];
    }

    public function testWillGetDidYouMeanWhenSettingAttributesWithATypo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute "desciption" does not exist. Did you mean "description"?');

        $httpClient = $this->mockHttpClient([]);
        $factory = $this->mockTemplateFactory();
        $cachePool = $this->mockCachePool();
        $config = ProfileConfig::fromArray(['type' => 'github', 'name' => 'GH', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => [
            ['name' => 'Desc', 'filter' => [], 'target' => ['attribute' => ['desciption' => 'desc']]],
        ]]);

        GithubProfile::fromConfig($config, $httpClient, $factory, $cachePool);
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
    private function mockHttpClient(array $requests): ClientInterface
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();

        foreach ($requests as $url => $response) {
            $httpClient
                ->expects(static::once())
                ->method('sendRequest')
                ->willReturnCallback(static function (RequestInterface $request) use ($url, $response): Response {
                    self::assertSame('GET', $request->getMethod());
                    self::assertSame($url, $request->getUri()->__toString());
                    self::assertSame('token ABC', $request->getHeaderLine('Authorization'));

                    return new Response(200, ['Content-Type' => 'application/json'], $response);
                })
            ;
        }

        return $httpClient;
    }

    private function mockTemplateFactory(): ProfileTemplateFactory
    {
        return $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();
    }

    private function mockCachePool(): CacheItemPoolInterface
    {
        return $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
    }

    private function generateConfig(?string $url = null): ProfileConfig
    {
        $config = ['type' => 'github', 'name' => 'GH', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => []];
        if ($url !== null) {
            $config['client']['url'] = $url;
        }

        return ProfileConfig::fromArray($config);
    }

    private function generateUrl(?string $baseUrl, string $path): string
    {
        return sprintf('%1$s%2$s%3$s', $baseUrl ?? 'https://api.github.com', $baseUrl === null ? '' : '/api/v3', $path);
    }
}
