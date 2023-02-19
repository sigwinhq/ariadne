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

use Psr\Http\Client\ClientInterface;

final class ClientFactory
{
    public function __construct(private readonly ClientInterface $httpClient)
    {
    }

    /**
     * @param array{name: string, auth: array{type: string, token: string}} $spec
     */
    public function create(string $type, array $spec): Client
    {
        return match ($type) {
            // TODO: detect clients via attribute autoconfiguration
            'gitlab' => \Sigwin\Ariadne\Bridge\Gitlab\GitlabClient::fromSpec($this->httpClient, $spec),
            'github' => \Sigwin\Ariadne\Bridge\Github\GithubClient::fromSpec($this->httpClient, $spec),
            default => throw new \LogicException(sprintf('Unknown client type "%1$s"', $type)),
        };
    }
}
