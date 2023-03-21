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
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
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
 * @uses \Sigwin\Ariadne\Model\Collection\NamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Collection\RepositoryCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 * @uses \Sigwin\Ariadne\Model\Repository
 *
 * @small
 *
 * @psalm-type TFilter = array{type?: 'fork'|'source', path?: string, visibility?:'private'|'public', topics?: array<string>, languages?: array<string>}
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
        $repositories = RepositoryCollection::fromArray($all);
        $template = $factory->create($config, $repositories);

        static::assertSame('test', $template->getName());
        static::assertCount(\count($expected), iterator_to_array($template));

        // extract the repositories that are expected to be in the template by key
        $expected = array_values(array_intersect_key($all, array_flip($expected)));
        $actual = iterator_to_array($template);

        static::assertSame($expected, $actual);
    }

    /**
     * @param TFilter $filter
     */
    public function createTemplateConfig(array $filter): ProfileTemplateConfig
    {
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
        ];
    }
}
