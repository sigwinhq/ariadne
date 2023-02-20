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

final class ClientConfig
{
    private function __construct(public readonly string $type, public readonly string $name, public readonly array $auth, public readonly array $parameters)
    {
    }

    public static function fromArray(array $config): self
    {
        return new self($config['type'], $config['name'], $config['auth'], $config['parameters']);
    }
}
