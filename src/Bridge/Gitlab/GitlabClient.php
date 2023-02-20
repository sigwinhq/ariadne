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

namespace Sigwin\Ariadne\Bridge\Gitlab;

use Gitlab\ResultPager;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsClient;
use Sigwin\Ariadne\Client;
use Sigwin\Ariadne\Model\CurrentUser;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;

#[AsClient(name: 'gitlab')]
final class GitlabClient implements Client
{
    private function __construct(private readonly \Gitlab\Client $client, private readonly string $name)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function fromSpec(ClientInterface $client, array $spec): self
    {
        $sdk = \Gitlab\Client::createWithHttpClient($client);
        $sdk->authenticate($spec['auth']['token'], $spec['auth']['type']);

        return new self($sdk, $spec['name']);
    }

    public function getApiVersion(): string
    {
        /** @var array{version: string} $info */
        $info = $this->client->version()->show();

        return $info['version'];
    }

    public function getCurrentUser(): CurrentUser
    {
        /** @var array{username: string} $me */
        $me = $this->client->users()->me();

        return new CurrentUser($me['username']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRepositories(): Repositories
    {
        $pager = new ResultPager($this->client);
        /** @var list<array{path_with_namespace: string}> $response */
        $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => ['simple' => true,  'membership' => true]]);

        $repositories = [];
        foreach ($response as $repository) {
            $repositories[] = new Repository($repository['path_with_namespace']);
        }

        return new Repositories($repositories);
    }
}
