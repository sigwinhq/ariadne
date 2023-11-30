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

namespace Sigwin\Ariadne\Test\Profile\Collection;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Model\Config\AriadneConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection
 *
 * @uses \Sigwin\Ariadne\Model\Config\AriadneConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileClientConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileConfig
 * @uses \Sigwin\Ariadne\Model\ProfileFilter
 *
 * @small
 */
final class FilteredProfileGeneratorCollectionTest extends TestCase
{
    use ModelGeneratorTrait;

    /**
     * @dataProvider provideWillFilterOutUnmatchedProfilesCases
     */
    public function testWillFilterOutUnmatchedProfiles(ProfileFilter $filter, int $matches): void
    {
        $config = AriadneConfig::fromArray('file:///ariadne.yaml', ['profiles' => [
            'Foo Indeed' => ['type' => 'fake', 'name' => 'Foo Indeed', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'token'], 'options' => []],  'templates' => []],
            'This one is bar' => ['type' => 'fake', 'name' => 'This one is bar', 'client' => ['auth' => ['token' => 'ABC', 'type' => 'token'], 'options' => []],  'templates' => []],
        ]]);

        $collection = new FilteredProfileGeneratorCollection(
            $this->createProfileFactory(iterator_to_array($config) + iterator_to_array($config)),
            $config,
            $filter
        );

        self::assertCount($matches, $collection);
    }

    /**
     * @return list<array{ProfileFilter, int}>
     */
    public static function provideWillFilterOutUnmatchedProfilesCases(): iterable
    {
        return [
            [ProfileFilter::create(null, null), 2],
            [ProfileFilter::create('Foo Indeed', null), 1],
            [ProfileFilter::create(null, 'fake'), 2],
            [ProfileFilter::create('Foo Indeed', 'fake'), 1],
            [ProfileFilter::create('Foo Indeed', 'very fake'), 0],
        ];
    }
}
