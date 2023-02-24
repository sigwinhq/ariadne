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

namespace Sigwin\Ariadne\Profile\Template\Factory;

use Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\ExpressionLanguage;
use Sigwin\Ariadne\Model\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryCollection;
use Sigwin\Ariadne\Model\Template;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class ExpressionLanguageFilteredTemplateFactory implements ProfileTemplateFactory
{
    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    public function create(ProfileTemplateConfig $config, RepositoryCollection $repositories): Template
    {
        return new Template($config->name, $config->target, $repositories->filter(function (Repository $repository) use ($config): bool {
            foreach ($config->filter as $name => $value) {
                try {
                    $expressionValue = $this->expressionLanguage->evaluate($value, [
                        'property' => $name,
                        'repository' => $repository,
                    ]);
                    if ($expressionValue !== true) {
                        return false;
                    }
                    continue;
                } catch (SyntaxError $e) {
                    // not a valid expression, compare as a literal
                }

                /**
                 * @var null|string|\UnitEnum $repositoryValue
                 *
                 * @phpstan-ignore-next-line
                 */
                $repositoryValue = $repository->{$name};
                if ($repositoryValue instanceof \BackedEnum) {
                    if ($repositoryValue->value !== $value) {
                        return false;
                    }
                    continue;
                }

                if ($repositoryValue !== $value) {
                    return false;
                }
            }

            return true;
        }));
    }
}
