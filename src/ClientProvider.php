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

namespace Sigwin\Ariadne;

use Symfony\Component\Yaml\Yaml;

/**
 * @implements \IteratorAggregate<Client>
 */
final class ClientProvider implements \IteratorAggregate
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
    }

    public function getIterator(): \Traversable
    {
        // TODO: placeholder
        /** @var list<array{type: string, name: string, auth: array}> $config */
        $config = Yaml::parseFile('ariadne.yaml');

        foreach ($config as $spec) {
            yield $this->clientFactory->create($spec);
        }
    }
}
