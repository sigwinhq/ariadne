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

final class ProfileTemplateConfig
{
    /**
     * @param array{type?: value-of<RepositoryType>, path?: string, visibility?: value-of<RepositoryVisibility>} $filter
     */
    public function __construct(public readonly string $name, public readonly array $filter, public readonly RepositoryTarget $target)
    {
    }

    /**
     * @param array{
     *     name: string,
     *     filter: array{type?: value-of<RepositoryType>, path?: string, visibility?: value-of<RepositoryVisibility>},
     *     target: array{attribute: array<string, bool|string>}
     * } $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['name'], $config['filter'], RepositoryTarget::fromArray($config['target']));
    }
}
