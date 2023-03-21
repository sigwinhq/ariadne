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
use Sigwin\Ariadne\Profile\Template\Factory\FilteredProfileTemplateFactory;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Profile\Template\Factory\FilteredProfileTemplateFactory
 *
 * @uses \Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\ExpressionLanguage
 * @uses \Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\LanguageProvider\FilterExpressionLanguageProvider
 * @uses \Sigwin\Ariadne\Model\Collection\RepositoryCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateConfig
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 *
 * @small
 */
final class FilteredProfileTemplateFactoryTest extends TestCase
{
    public function testCanCreateProfileTemplate(): void
    {
        $factory = new FilteredProfileTemplateFactory(new ExpressionLanguage());

        $config = ProfileTemplateConfig::fromArray(['name' => 'test', 'filter' => [], 'target' => ['attribute' => []]]);
        $repositories = RepositoryCollection::fromArray([]);
        $template = $factory->create($config, $repositories);

        static::assertSame('test', $template->getName());
    }
}
