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
 * @psalm-type TProfileClientAuth = array{type: string, token: string}
 * @psalm-type TProfileClientOptions = array<string, bool|string>
 * @psalm-type TProfileClient = array{auth: TProfileClientAuth, options: TProfileClientOptions, url?: string}
 */
final class ProfileClientConfig
{
    /**
     * @param TProfileClientAuth    $auth
     * @param TProfileClientOptions $options
     */
    private function __construct(public readonly array $auth, public readonly array $options, public readonly ?string $url = null) {}

    /**
     * @param TProfileClient $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['auth'], $config['options'], $config['url'] ?? null);
    }
}
