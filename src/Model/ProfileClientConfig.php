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

final class ProfileClientConfig
{
    /**
     * @param array{type: string, token: string} $auth
     * @param array<string, bool|string>         $options
     */
    private function __construct(public readonly array $auth, public readonly array $options)
    {
    }

    /**
     * @param array{auth: array{type: string, token: string}, options: array<string, bool|string>} $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['auth'], $config['options']);
    }
}
