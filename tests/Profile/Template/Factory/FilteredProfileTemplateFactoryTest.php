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
use Sigwin\Ariadne\Test\AssertTrait;
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
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(FilteredProfileTemplateFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ExpressionLanguage::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\LanguageProvider\FilterExpressionLanguageProvider::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SortedNamedResourceCollection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProfileTemplateConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplate::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplateTarget::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Repository::class)]
final class FilteredProfileTemplateFactoryTest extends TestCase
{
    use AssertTrait;
    use ModelGeneratorTrait;

    /**
     * @param TFilter          $filter
     * @param list<Repository> $all
     * @param list<int>        $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideWillFilterOutUnmatchedRepositoriesCases')]
    public function testWillFilterOutUnmatchedRepositories(array $filter, array $all, array $expected): void
    {
        $factory = new FilteredProfileTemplateFactory(new ExpressionLanguage());

        $config = $this->createTemplateConfig($filter);
        $repositories = SortedNamedResourceCollection::fromArray($all);
        $template = $factory->fromConfig($config, $repositories);

        $actual = iterator_to_array($template);

        self::assertSame('test', $template->getName());
        self::assertCount(\count($expected), $actual);

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
    public static function provideWillFilterOutUnmatchedRepositoriesCases(): iterable
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
                    self::createRepository('foo/bar'),
                    self::createRepository('bar/foo'),
                ],
                [0], // matches foo/bar
            ],
            'expression on a string' => [
                ['path' => '@=match("^foo")'],
                [
                    self::createRepository('foo/bar'),
                    self::createRepository('bar/foo'),
                ],
                [0], // matches foo/bar
            ],
            'expression without the prefix will be treated as a literal' => [
                ['path' => 'match("^foo")'],
                [
                    self::createRepository('foo/bar'),
                    self::createRepository('bar/foo'),
                ],
                [], // no matches
            ],
            'match on an enum' => [
                ['type' => 'fork'],
                [
                    self::createRepository('foo/bar', type: 'source'),
                    self::createRepository('bar/foo', type: 'fork'),
                ],
                [1], // matches bar/foo
            ],
            'match on an array' => [
                ['topics' => ['bar']],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [1], // matches bar/foo
            ],
            'match on an array with an unknown requirement' => [
                ['topics' => ['unknown']],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array must match at least one element' => [
                ['topics' => ['unknown', 'foo']],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [0], // matches foo/bar
            ],
            'filter can be an array even if the property is not' => [
                ['path' => ['unknown', 'bar/foo']],
                [
                    self::createRepository('foo/bar'),
                    self::createRepository('bar/foo'),
                ],
                [1], // matches bar/foo
            ],
            'property can be an array even if the filter is not' => [
                ['topics' => 'foo'],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [0], // matches foo/bar
            ],
            'match on both arrays before a miss' => [
                ['topics' => ['foo'], 'path' => 'invalid'],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array filter before a miss' => [
                ['path' => ['foo/bar'], 'topics' => 'invalid'],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an array property before a miss' => [
                ['topics' => 'foo', 'path' => 'invalid'],
                [
                    self::createRepository('foo/bar', topics: ['foo']),
                    self::createRepository('bar/foo', topics: ['bar']),
                ],
                [], // no matches
            ],
            'match on an enum before a literal' => [
                ['type' => 'fork', 'path' => 'foo/bar'],
                [
                    self::createRepository('foo/bar', type: 'source'),
                    self::createRepository('bar/foo', type: 'fork'),
                ],
                [], // no matches
            ],
            'filter on multiple items, one eliminates each' => [
                ['path' => '@=match("^foo")', 'type' => 'fork'],
                [
                    self::createRepository('foo/bar', type: 'source'),
                    self::createRepository('bar/foo', type: 'fork'),
                ],
                [], // no matches
            ],
            'filter on multiple items, both eliminates one, both match the other' => [
                ['path' => '@=match("^foo")', 'type' => 'source'],
                [
                    self::createRepository('foo/bar', type: 'source'),
                    self::createRepository('bar/foo', type: 'fork'),
                ],
                [0], // matches foo/bar
            ],
            'order of the filters is irrelevant' => [
                ['type' => 'source', 'path' => '@=match("^foo")'],
                [
                    self::createRepository('foo/bar', type: 'source'),
                    self::createRepository('bar/foo', type: 'fork'),
                ],
                [0], // matches foo/bar
            ],
        ];
    }
}
