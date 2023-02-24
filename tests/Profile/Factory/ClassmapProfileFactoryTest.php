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

namespace Sigwin\Ariadne\Test\Profile\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryCollection;
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Model\TemplateCollection;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Profile\Factory\ClassmapProfileFactory
 *
 * @uses \Sigwin\Ariadne\Model\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\ProfileConfig
 *
 * @small
 */
final class ClassmapProfileFactoryTest extends TestCase implements Profile
{
    public function testCanCreateAProfileInstance(): void
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $templateFactory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $factory = new Profile\Factory\ClassmapProfileFactory(['self' => self::class], $httpClient, $templateFactory);
        $factory->create(ProfileConfig::fromArray(['type' => 'self', 'name' => 'My Self', 'client' => ['auth' => ['type' => '', 'token' => ''], 'options' => []], 'templates' => []]));
    }

    public function testWillThrowAnExceptionOnInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to create profile type "no such type", unknown profile type');

        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $templateFactory = $this->getMockBuilder(ProfileTemplateFactory::class)->getMock();

        $factory = new Profile\Factory\ClassmapProfileFactory(['foo' => self::class], $httpClient, $templateFactory);
        $factory->create(ProfileConfig::fromArray(['type' => 'no such type', 'name' => 'My Foo', 'client' => ['auth' => ['type' => '', 'token' => ''], 'options' => []], 'templates' => []]));
    }

    public static function fromConfig(ClientInterface $client, ProfileTemplateFactory $templateFactory, ProfileConfig $config): Profile
    {
        static::assertSame('My Self', $config->name);

        /** @psalm-suppress InternalMethod */
        return new self();
    }

    public function getIterator(): \Traversable
    {
        throw new \LogicException('Not implemented');
    }

    public function getApiUser(): ProfileUser
    {
        throw new \LogicException('Not implemented');
    }

    public function getApiVersion(): string
    {
        throw new \LogicException('Not implemented');
    }

    public function getRepositories(): RepositoryCollection
    {
        throw new \LogicException('Not implemented');
    }

    public function getTemplates(): TemplateCollection
    {
        throw new \LogicException('Not implemented');
    }

    public function plan(Repository $repository): RepositoryPlan
    {
        throw new \LogicException('Not implemented');
    }

    public function apply(RepositoryPlan $plan): void
    {
        throw new \LogicException('Not implemented');
    }
}
