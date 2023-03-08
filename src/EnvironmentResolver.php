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

namespace Sigwin\Ariadne;

final class EnvironmentResolver
{
    public function __construct(private readonly ?string $cache, private readonly ?string $config, private readonly string $home)
    {
    }

    public function getCacheDir(): string
    {
        return ($this->cache ?? $this->home.'/.cache').'/ariadne';
    }

    public function getConfigDir(): string
    {
        return ($this->config ?? $this->home.'/.config').'/ariadne';
    }
}
