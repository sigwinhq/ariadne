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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Sigwin\Ariadne\Bridge\Github\GithubProfile;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\ProfileTemplateFactory;

/**
 * @covers \Sigwin\Ariadne\Bridge\Github\GithubProfile
 *
 * @uses \Sigwin\Ariadne\Model\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 *
 * @internal
 *
 * @small
 */
final class GithubProfileTest extends TestCase
{
    public function testCanFetchApiUser(): void
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClient
            ->expects(static::once())
            ->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $request): Response {
                self::assertSame('GET', $request->getMethod());
                self::assertSame('https://api.github.com/user', $request->getUri()->__toString());
                self::assertSame('token ABC', $request->getHeaderLine('Authorization'));

                return new Response(200, ['Content-Type' => 'application/json'], '{"login":"ariadne"}');
            })
        ;
        $templateFactory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $config = ProfileConfig::fromArray(['type' => 'github', 'name' => 'GH', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => []]);

        $profile = GithubProfile::fromConfig($httpClient, $templateFactory, $config);
        $login = $profile->getApiUser();

        static::assertSame('ariadne', $login->username);
    }

    public function testCanUseCustomUrl(): void
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClient
            ->expects(static::once())
            ->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $request): Response {
                self::assertSame('GET', $request->getMethod());
                self::assertSame('https://example.com/api/v3/user', $request->getUri()->__toString());
                self::assertSame('token ABC', $request->getHeaderLine('Authorization'));

                return new Response(200, ['Content-Type' => 'application/json'], '{"login":"ariadne"}');
            })
        ;
        $templateFactory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $config = ProfileConfig::fromArray(['type' => 'github', 'name' => 'GH', 'client' => ['url' => 'https://example.com', 'auth' => ['token' => 'ABC', 'type' => 'access_token_header'], 'options' => []], 'templates' => []]);

        $profile = GithubProfile::fromConfig($httpClient, $templateFactory, $config);
        $profile->getApiUser();
    }
}
