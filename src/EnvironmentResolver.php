<?php

namespace Sigwin\Ariadne;

class EnvironmentResolver
{
    public function __construct(private readonly ?string $cache, private readonly ?string $config, private readonly string $home)
    {
    }

    public function getCacheDir(): string
    {
        return $this->cache ?? $this->home .'/.cache/ariadne';
    }

    public function getConfigDir(): string
    {
        return $this->config ?? $this->home .'/.config/ariadne';
    }
}
