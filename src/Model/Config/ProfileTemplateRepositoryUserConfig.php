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
 * @psalm-type TProfileTemplateRepositoryUser = array{username: string, role: string}
 */
final class ProfileTemplateRepositoryUserConfig
{
    public function __construct(public readonly string $username, public readonly string $role)
    {
    }

    /**
     * @param TProfileTemplateRepositoryUser $user
     */
    public static function fromArray(array $user): self
    {
        return new self($user['username'], $user['role']);
    }
}
