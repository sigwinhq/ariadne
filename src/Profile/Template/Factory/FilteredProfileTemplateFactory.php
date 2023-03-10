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
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
use Sigwin\Ariadne\Model\Config\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\ProfileTemplateTarget;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class FilteredProfileTemplateFactory implements ProfileTemplateFactory
{
    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    public function create(ProfileTemplateConfig $config, RepositoryCollection $repositories): ProfileTemplate
    {
        return new ProfileTemplate($config->name, $this->createTemplateTarget($config->target), $repositories->filter(function (Repository $repository) use ($config): bool {
            foreach ($config->filter as $name => $value) {
                /**
                 * @var null|array<string>|string|\UnitEnum $repositoryValue
                 *
                 * @phpstan-ignore-next-line
                 */
                $repositoryValue = $repository->{$name};

                if (\is_array($value)) {
                    if ($value === []) {
                        // no values to match against, skip
                        continue;
                    }

                    if (\is_array($repositoryValue)) {
                        // both are arrays, must have at least one common value
                        if (array_intersect($value, $repositoryValue) === []) {
                            return false;
                        }

                        continue;
                    }

                    // repository value is not an array, so compare it to each value in the filter array
                    if (! \in_array($repositoryValue, $value, true)) {
                        return false;
                    }

                    continue;
                }

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

                if ($repositoryValue instanceof \BackedEnum) {
                    if ($repositoryValue->value !== $value) {
                        return false;
                    }
                    continue;
                }

                if (\is_array($repositoryValue)) {
                    if (! \in_array($value, $repositoryValue, true)) {
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

    public function createTemplateTarget(ProfileTemplateTargetConfig $config): ProfileTemplateTarget
    {
        return ProfileTemplateTarget::fromConfig($config);
    }
}
