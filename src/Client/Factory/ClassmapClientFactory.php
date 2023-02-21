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

namespace Sigwin\Ariadne\Client\Factory;

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Client;
use Sigwin\Ariadne\ClientFactory;
use Sigwin\Ariadne\Model\ProfileConfig;

final class ClassmapClientFactory implements ClientFactory
{
    /**
     * @param array<string, class-string<Client>> $clientsMap
     */
    public function __construct(private readonly array $clientsMap, private readonly ClientInterface $httpClient)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(ProfileConfig $config): Client
    {
        if (! \array_key_exists($config->type, $this->clientsMap)) {
            throw new \LogicException(sprintf('Unknown client type "%1$s"', $config->type));
        }
        $className = $this->clientsMap[$config->type];

        return $className::fromConfig($this->httpClient, $config);
    }
}
