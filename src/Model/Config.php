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
 * @implements \IteratorAggregate<\Sigwin\Ariadne\Model\ProfileConfig>
 */
final class Config implements \IteratorAggregate
{
    /**
     * @param array<ProfileConfig> $clientConfig
     */
    private function __construct(public readonly string $url, private readonly array $clientConfig)
    {
    }

    /**
     * @param array{
     *     profiles: list<array{
     *          type: string,
     *          name: string,
     *          client: array{auth: array{type: string, token: string}, options: array<string, bool|string>},
     *          templates: list<array{name: string, filter: array{path: ?string}}>
     *     }>} $config
     */
    public static function fromArray(string $url, array $config): self
    {
        $profiles = [];
        foreach ($config['profiles'] as $profile) {
            $profiles[] = ProfileConfig::fromArray($profile);
        }

        return new self($url, $profiles);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->clientConfig);
    }
}
