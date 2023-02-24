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

final class ProfileConfig
{
    /**
     * @param array<ProfileTemplateConfig> $templates
     */
    private function __construct(public readonly string $type, public readonly string $name, public readonly ProfileClientConfig $client, public readonly array $templates)
    {
    }

    /**
     * @param array{
     *     type: string,
     *     name: string,
     *     client: array{auth: array{type: string, token: string}, options: array<string, bool|string>},
     *     templates: list<array{
     *          name: string,
     *          filter: array{type?: value-of<RepositoryType>, path?: string, visibility?: value-of<RepositoryVisibility>},
     *          apply: array{attribute?: list<array{string, bool|string}>}
     *     }>
     * } $config
     */
    public static function fromArray(array $config): self
    {
        $templates = [];
        foreach ($config['templates'] as $template) {
            $templates[] = ProfileTemplateConfig::fromArray($template);
        }

        return new self($config['type'], $config['name'], ProfileClientConfig::fromArray($config['client']), $templates);
    }
}
