<?php

namespace Sigwin\Ariadne\Bridge\Symfony\DependencyInjection;

use Sigwin\Ariadne\EnvironmentResolver;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class RuntimeEnvProcessor implements EnvVarProcessorInterface
{
    public function __construct(private readonly EnvironmentResolver $resolver)
    {}

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        return $t;
    }

    public static function getProvidedTypes(): array
    {
        return ['cache' => 'string', 'config' => 'string'];
    }
}
