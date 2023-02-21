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

final class ExpressionLanguageFilteredTemplateFactory implements ProfileTemplateFactory
{
    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    public function create(ProfileTemplateConfig $config, RepositoryCollection $repositories): Template
    {
        return new Template($config->name, $repositories->filter(function (Repository $repository) use ($config): bool {
            foreach ($config->filter as $name => $value) {
                if ($value === null) {
                    continue;
                }
                if ($this->expressionLanguage->evaluate($value, [
                    'property' => $name,
                    'repository' => $repository,
                ]) !== true) {
                    return false;
                }
            }

            return true;
        }));
    }
}
