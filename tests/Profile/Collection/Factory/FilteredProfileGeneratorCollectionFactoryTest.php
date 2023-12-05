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

namespace Sigwin\Ariadne\Test\Profile\Collection\Factory;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Model\Config\AriadneConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Profile\Collection\Factory\FilteredProfileGeneratorCollectionFactory;
use Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection;
use Sigwin\Ariadne\ProfileFactory;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Profile\Collection\Factory\FilteredProfileGeneratorCollectionFactory
 *
 * @uses \Sigwin\Ariadne\Model\Config\AriadneConfig
 * @uses \Sigwin\Ariadne\Model\ProfileFilter
 * @uses \Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(\Sigwin\Ariadne\Profile\Collection\Factory\FilteredProfileGeneratorCollectionFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\AriadneConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileFilter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection::class)]
final class FilteredProfileGeneratorCollectionFactoryTest extends TestCase
{
    public function testWillCreateFilteredProfileGeneratorCollection(): void
    {
        $factory = new FilteredProfileGeneratorCollectionFactory($this->createMock(ProfileFactory::class));

        $config = AriadneConfig::fromArray('file:///ariadne.yaml', ['profiles' => []]);
        $filter = ProfileFilter::create('test', 'type');

        self::assertInstanceOf(FilteredProfileGeneratorCollection::class, $factory->fromConfig($config, $filter));
    }
}
