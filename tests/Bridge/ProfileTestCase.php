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
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

abstract class ProfileTestCase extends TestCase
{
    use ModelGeneratorTrait;

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
     */
    public function testCannotSetInvalidOptions(string $name, bool|string $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
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
        $this->expectExceptionMessage(sprintf($message, $name));

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

    abstract protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile;

    /**
     * @param null|array<string, bool|string>     $options
     * @param null|array<string, bool|int|string> $attribute
     */
    abstract protected function createConfig(?string $url = null, ?array $options = null, ?array $attribute = null): ProfileConfig;

    abstract protected function createRequest(?string $baseUrl, string $method, string $path): string;
}
