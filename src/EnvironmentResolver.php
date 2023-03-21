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
    public function __construct(private readonly ?string $cacheHome, private readonly ?string $configHome, private readonly string $home)
    {
    }

    public function getCacheDir(): string
    {
        return ($this->cacheHome ?? $this->home.'/.cache').'/ariadne';
    }

    public function getConfigDir(): string
    {
        return ($this->configHome ?? $this->home.'/.config').'/ariadne';
    }
}
