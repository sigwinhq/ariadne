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

namespace Sigwin\Ariadne\Model;

use Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig;

final class ProfileTemplateTarget
{
    private function __construct(public readonly ProfileTemplateTargetConfig $config)
    {
    }

    public static function fromConfig(ProfileTemplateTargetConfig $config): self
    {
        return new self($config);
    }
}
