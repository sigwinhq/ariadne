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
 * @implements \IteratorAggregate<\Sigwin\Ariadne\Model\ClientConfig>
 */
final class Config implements \IteratorAggregate
{
    private function __construct(public readonly string $url, private readonly array $clientConfig)
    {
    }

    /**
     * @param list<array{type: string, name: string, auth: array{type: string, token: string}, parameters: array}> $config
     */
    public static function fromArray(string $url, array $config): self
    {
        $clients = [];
        foreach ($config as $clientConfig) {
            $clients[] = ClientConfig::fromArray($clientConfig);
        }

        return new self($url, $clients);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->clientConfig);
    }
}
