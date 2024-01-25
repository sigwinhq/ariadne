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

namespace Sigwin\Ariadne\Model\Config;

/**
 * @psalm-import-type TProfileTemplateTarget from ProfileTemplateTargetConfig
 *
 * @psalm-type TProfileTemplateFilter = array{type?: value-of<\Sigwin\Ariadne\Model\RepositoryType>, path?: list<string>|string, visibility?: value-of<\Sigwin\Ariadne\Model\RepositoryVisibility>, topics?: array<string>, languages?: array<string>, archived?: bool}
 * @psalm-type TProfileTemplate = array{name: string, filter: TProfileTemplateFilter, target: TProfileTemplateTarget}
 */
final readonly class ProfileTemplateConfig
{
    /**
     * @param TProfileTemplateFilter $filter
     */
    public function __construct(public string $name, public array $filter, public ProfileTemplateTargetConfig $target)
    {
    }

    /**
     * @param TProfileTemplate $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['name'], $config['filter'], ProfileTemplateTargetConfig::fromArray($config['target']));
    }
}
