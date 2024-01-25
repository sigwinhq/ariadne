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
 * @psalm-import-type TProfileTemplateRepositoryUser from ProfileTemplateRepositoryUserConfig
 *
 * @psalm-type TProfileTemplateTargetAttribute = array<string, bool|string|int>
 * @psalm-type TProfileTemplateTarget = array{attribute: TProfileTemplateTargetAttribute, user?: array<string, TProfileTemplateRepositoryUser>}
 */
final readonly class ProfileTemplateTargetConfig
{
    /**
     * @param TProfileTemplateTargetAttribute           $attribute
     * @param list<ProfileTemplateRepositoryUserConfig> $users
     */
    private function __construct(public array $attribute, public array $users)
    {
    }

    /**
     * @param TProfileTemplateTarget $config
     */
    public static function fromArray(array $config): self
    {
        $users = [];
        foreach ($config['user'] ?? [] as $username => $user) {
            // TODO: this shouldn't be required
            $user['username'] = $username;

            $users[] = ProfileTemplateRepositoryUserConfig::fromArray($user);
        }

        return new self($config['attribute'], $users);
    }
}
