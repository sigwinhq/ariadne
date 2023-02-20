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

namespace Sigwin\Ariadne\Client;

use Sigwin\Ariadne\ClientFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * @implements \IteratorAggregate<\Sigwin\Ariadne\Client>
 */
final class IterableClientProvider implements \IteratorAggregate
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
    }

    public function getIterator(): \Traversable
    {
        // TODO: placeholder config loader
        // TODO: validate config to confirm it conforms to this type
        /** @var list<array{type: string, name: string, auth: array{type: string, token: string}, parameters: array}> $config */
        $config = Yaml::parseFile('ariadne.yaml');

        foreach ($config as $spec) {
            $type = $spec['type'];
            unset($spec['type']);

            yield $this->clientFactory->create($type, $spec);
        }
    }
}
