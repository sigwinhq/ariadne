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

/**
 * @psalm-import-type TRepositoryTarget from RepositoryTarget
 *
 * @psalm-type TProfileTemplateFilter = array{type?: value-of<RepositoryType>, path?: string, visibility?: value-of<RepositoryVisibility>}
 * @psalm-type TProfileTemplate = array{name: string, filter: TProfileTemplateFilter, target: TRepositoryTarget}
 */
final class ProfileTemplateConfig
{
    /**
     * @param TProfileTemplateFilter $filter
     */
    public function __construct(public readonly string $name, public readonly array $filter, public readonly RepositoryTarget $target)
    {
    }

    /**
     * @param TProfileTemplate $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['name'], $config['filter'], RepositoryTarget::fromArray($config['target']));
    }
}
