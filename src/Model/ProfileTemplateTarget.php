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

/**
 * @psalm-import-type TProfileTemplateTargetAttribute from ProfileTemplateTargetConfig
 */
final class ProfileTemplateTarget
{
    private function __construct(private readonly ProfileTemplateTargetConfig $config)
    {
    }

    /**
     * @return TProfileTemplateTargetAttribute
     */
    public function getAttributes(ProfileTemplate $template, Repository $repository): array
    {
        return $this->config->attribute;
    }

    public static function fromConfig(ProfileTemplateTargetConfig $config): self
    {
        return new self($config);
    }
}
