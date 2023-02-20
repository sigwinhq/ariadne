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

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Client;
use Sigwin\Ariadne\ClientFactory;

final class ClassmapClientFactory implements ClientFactory
{
    /**
     * @param array<string, class-string<Client>> $clientsMap
     */
    public function __construct(private readonly ClientInterface $httpClient, private readonly array $clientsMap)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $type, array $spec): Client
    {
        if (! \array_key_exists($type, $this->clientsMap)) {
            throw new \LogicException(sprintf('Unknown client type "%1$s"', $type));
        }
        $className = $this->clientsMap[$type];

        return $className::fromSpec($this->httpClient, $spec);
    }
}
