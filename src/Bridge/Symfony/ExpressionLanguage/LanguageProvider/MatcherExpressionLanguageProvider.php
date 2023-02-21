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

namespace Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage\LanguageProvider;

use Sigwin\Ariadne\Model\Repository;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class MatcherExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('match',
                static function (string $string): string {
                    // TODO: don't know how this works exactly
                    return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $string);
                },
                /** @param array{repository: Repository, property: "path"} $arguments */
                static function (array $arguments, string $regex, ?string $value = null): bool {
                    if ($value === null) {
                        $repository = $arguments['repository'];
                        $property = $arguments['property'];

                        /** @phpstan-ignore-next-line False positive */
                        $value = $repository->{$property};
                    }

                    return preg_match(sprintf('@%1$s@', preg_quote($regex, '@')), $value) === 1;
                }
            ),
        ];
    }
}
