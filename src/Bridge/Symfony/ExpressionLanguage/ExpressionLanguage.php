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

namespace Sigwin\Ariadne\Bridge\Symfony\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionLanguage extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage
{
    /**
     * @param array<ExpressionFunctionProviderInterface> $providers
     */
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        array_unshift($providers, new MatcherExpressionLanguageProvider());

        parent::__construct($cache, $providers);
    }
}
