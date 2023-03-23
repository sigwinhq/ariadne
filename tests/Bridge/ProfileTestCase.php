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
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

abstract class ProfileTestCase extends TestCase
{
    use ModelGeneratorTrait;

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
    public function testCannotSetInvalidAttributes(string $name, int|bool|string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Attribute "%1$s" is read-only.', $name));

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(attribute: [$name => $value]);

        $this->createProfileInstance($config, $httpClient, $factory, $cachePool);
    }

    public function testWillGetDidYouMeanWhenSettingAttributesWithATypo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute "desciption" does not exist. Did you mean "description"?');

        $httpClient = $this->createHttpClient();
        $factory = $this->createTemplateFactory();
        $cachePool = $this->createCachePool();
        $config = $this->createConfig(attribute: ['desciption' => 'desc']);

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

    abstract protected function provideValidAttributeValues(): iterable;

    abstract protected function provideInvalidAttributeValues(): iterable;

    abstract protected function createProfileInstance(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $factory, CacheItemPoolInterface $cachePool): Profile;

    abstract protected function createConfig(?string $url = null, ?array $options = null, ?array $attribute = null): ProfileConfig;

    abstract protected function createUrl(?string $baseUrl, string $path): string;
}
