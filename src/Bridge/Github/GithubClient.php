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

namespace Sigwin\Ariadne\Bridge\Github;

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsClient;
use Sigwin\Ariadne\Client;
use Sigwin\Ariadne\Model\CurrentUser;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsClient(name: 'github')]
final class GithubClient implements Client
{
    private readonly array $parameters;

    private function __construct(private readonly \Github\Client $client, private readonly string $name, array $parameters)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('organizations')
            ->setAllowedTypes('organizations', ['boolean'])
        ;

        $this->parameters = $resolver->resolve($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromSpec(ClientInterface $client, array $spec): self
    {
        $sdk = \Github\Client::createWithHttpClient($client);
        $sdk->authenticate($spec['auth']['token'], $spec['auth']['type']);

        return new self($sdk, $spec['name'], $spec['parameters']);
    }

    public function getApiVersion(): string
    {
        return $this->client->getApiVersion();
    }

    public function getCurrentUser(): CurrentUser
    {
        /** @var array{login: string} $me */
        $me = $this->client->me()->show();

        return new CurrentUser($me['login']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRepositories(): Repositories
    {
        $repositories = [];

        /** @var list<array{full_name: string}> $userRepositories */
        $userRepositories = $this->client->user()->myRepositories();
        foreach ($userRepositories as $userRepository) {
            $repositories[] = new Repository($userRepository['full_name']);
        }

        if ($this->parameters['organizations'] ?? false) {
            /** @var list<array{login: string}> $organizations */
            $organizations = $this->client->currentUser()->organizations();
            foreach ($organizations as $organization) {
                /** @var list<array{full_name: string}> $organizationRepositories */
                $organizationRepositories = $this->client->organizations()->repositories($organization['login']);
                foreach ($organizationRepositories as $organizationRepository) {
                    $repositories[] = new Repository($organizationRepository['full_name']);
                }
            }
        }

        return new Repositories($repositories);
    }
}
