<?php

declare(strict_types=1);

/*
 * This file is part of the ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne\Bridge\Github;

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsClient;
use Sigwin\Ariadne\Client;
use Sigwin\Ariadne\Model\CurrentUser;

#[AsClient(name: 'github')]
final class GithubClient implements Client
{
    private function __construct(private readonly \Github\Client $client, private readonly string $name)
    {
    }

    public static function fromSpec(ClientInterface $client, array $spec): self
    {
        $sdk = \Github\Client::createWithHttpClient($client);
        $sdk->authenticate($spec['auth']['token'], $spec['auth']['type']);

        return new self($sdk, $spec['name']);
    }

    public function getApiVersion(): string
    {
        return $this->client->getApiVersion();
    }

    public function getCurrentUser(): CurrentUser
    {
        $me = $this->client->me()->show();

        return new CurrentUser($me['login']);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
