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
 * @psalm-import-type TProfileTemplate from ProfileTemplateConfig
 * @psalm-import-type TProfileClient from ProfileClientConfig
 *
 * @psalm-type TProfile = array{type: string, name: string, client: TProfileClient, templates: array<string, TProfileTemplate>}
 */
final class ProfileConfig
{
    /**
     * @param array<ProfileTemplateConfig> $templates
     */
    private function __construct(public readonly string $type, public readonly string $name, public readonly ProfileClientConfig $client, public readonly array $templates) {}

    /**
     * @param TProfile $config
     */
    public static function fromArray(array $config): self
    {
        $templates = [];
        foreach ($config['templates'] as $name => $template) {
            $template['name'] = $name;

            $templates[] = ProfileTemplateConfig::fromArray($template);
        }

        return new self($config['type'], $config['name'], ProfileClientConfig::fromArray($config['client']), $templates);
    }
}
