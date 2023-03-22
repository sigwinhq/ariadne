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

namespace Sigwin\Ariadne\Test\Profile\Template\Factory;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\ExpressionLanguage;
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Profile\Template\Factory\FilteredProfileTemplateFactory;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Profile\Template\Factory\FilteredProfileTemplateFactory
 *
 * @uses \Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\ExpressionLanguage
 * @uses \Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\LanguageProvider\FilterExpressionLanguageProvider
 * @uses \Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 * @uses \Sigwin\Ariadne\Model\Repository
 *
 * @small
 *
 * @psalm-type TFilter = array{type?: 'fork'|'source', path?: string|list<string>, visibility?:'private'|'public', topics?: string|list<string>, languages?: array<string>}
 */
final class FilteredProfileTemplateFactoryTest extends TestCase
{
    use ModelGeneratorTrait;

    /**
     * @dataProvider provideFilterAndRepositories
     *
     * @param TFilter          $filter
     * @param list<Repository> $all
     * @param list<int>        $expected
     */
    public function testWillFilterOutUnmatchedRepositories(array $filter, array $all, array $expected): void
    {
        $factory = new FilteredProfileTemplateFactory(new ExpressionLanguage());

        $config = $this->createTemplateConfig($filter);
        $repositories = SortedNamedResourceCollection::fromArray($all);
        $template = $factory->fromConfig($config, $repositories);

        $actual = iterator_to_array($template);

        static::assertSame('test', $template->getName());
        static::assertCount(\count($expected), $actual);

        self::assertArrayInArrayByKey($all, $expected, $actual);
    }

    /**
     * @param TFilter $filter
     */
    public function createTemplateConfig(array $filter): ProfileTemplateConfig
    {
        /**
         * @phpstan-ignore-next-line
         *
         * @psalm-suppress InvalidArgument We're breaking it by design
         */
        return ProfileTemplateConfig::fromArray(['name' => 'test', 'filter' => $filter, 'target' => ['attribute' => []]]);
    }

    /**
     * @return array<string, array{TFilter, list<Repository>, list<int>}>
     */
    public function provideFilterAndRepositories(): array
    {
        return [
            'everything is empty' => [
                [],
                [],
                [],
            ],
            'literal on a string' => [
                ['path' => 'foo/bar'],
                [
                    $this->createRepository('foo/bar'),
                    $this->createRepository('bar/foo'),
                ],
                [0], // matches foo/bar
            ],
            'expression on a string' => [
                ['path' => '@=match("^foo")'],
                [
                    $this->createRepository('foo/bar'),
                    $this->createRepository('bar/foo'),
                ],
                [0], // matches foo/bar
            ],
            'expression without the prefix will be treated as a literal' => [
                ['path' => 'match("^foo")'],
                [
                    $this->createRepository('foo/bar'),
                    $this->createRepository('bar/foo'),
                ],
                [], // no matches
            ],
            'match on an enum' => [
                ['type' => 'fork'],
                [
                    $this->createRepository('foo/bar', type: 'source'),
                    $this->createRepository('bar/foo', type: 'fork'),
                ],
                [1], // matches bar/foo
            ],
            'match on an array' => [
                ['topics' => ['bar']],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [1], // matches bar/foo
            ],
            'match on an array with an unknown requirement' => [
                ['topics' => ['unknown']],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array must match at least one element' => [
                ['topics' => ['unknown', 'foo']],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [0], // matches foo/bar
            ],
            'filter can be an array even if the property is not' => [
                ['path' => ['unknown', 'bar/foo']],
                [
                    $this->createRepository('foo/bar'),
                    $this->createRepository('bar/foo'),
                ],
                [1], // matches bar/foo
            ],
            'property can be an array even if the filter is not' => [
                ['topics' => 'foo'],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [0], // matches foo/bar
            ],
            'match on both arrays before a miss' => [
                ['topics' => ['foo'], 'path' => 'invalid'],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array filter before a miss' => [
                ['path' => ['foo/bar'], 'topics' => 'invalid'],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array property before a miss' => [
                ['topics' => 'foo', 'path' => 'invalid'],
                [
                    $this->createRepository('foo/bar', topics: ['foo']),
                    $this->createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an enum before a literal' => [
                ['type' => 'fork', 'path' => 'foo/bar'],
                [
                    $this->createRepository('foo/bar', type: 'source'),
                    $this->createRepository('bar/foo', type: 'fork'),
                ],
                [], // no matches
            ],
            'filter on multiple items, one eliminates each' => [
                ['path' => '@=match("^foo")', 'type' => 'fork'],
                [
                    $this->createRepository('foo/bar', type: 'source'),
                    $this->createRepository('bar/foo', type: 'fork'),
                ],
                [], // no matches
            ],
            'filter on multiple items, both eliminates one, both match the other' => [
                ['path' => '@=match("^foo")', 'type' => 'source'],
                [
                    $this->createRepository('foo/bar', type: 'source'),
                    $this->createRepository('bar/foo', type: 'fork'),
                ],
                [0], // matches foo/bar
            ],
            'order of the filters is irrelevant' => [
                ['type' => 'source', 'path' => '@=match("^foo")'],
                [
                    $this->createRepository('foo/bar', type: 'source'),
                    $this->createRepository('bar/foo', type: 'fork'),
                ],
                [0], // matches foo/bar
            ],
        ];
    }
}
