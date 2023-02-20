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
    private function __construct(private readonly array $clientConfig)
    {
    }

    public static function fromArray(array $config): self
    {
        $clients = [];
        foreach ($config as $clientConfig) {
            $clients[] = ClientConfig::fromArray($clientConfig);
        }

        return new self($clients);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->clientConfig);
    }
}
