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

namespace Sigwin\Ariadne\Bridge\Symfony\DependencyInjection;

use Sigwin\Ariadne\Resolver\XdgEnvironmentResolver;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final class RuntimeEnvProcessor implements EnvVarProcessorInterface
{
    public function __construct(private readonly XdgEnvironmentResolver $resolver)
    {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        return $this->resolver->getCacheDir();
    }

    public static function getProvidedTypes(): array
    {
        return [
            'ariadne_cache_dir' => 'string',
        ];
    }
}
