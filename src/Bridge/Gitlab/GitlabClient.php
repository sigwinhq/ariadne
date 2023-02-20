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
use Sigwin\Ariadne\Model\ClientConfig;
use Sigwin\Ariadne\Model\CurrentUser;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsClient(name: 'gitlab')]
final class GitlabClient implements Client
{
    private readonly array $parameters;

    private function __construct(private readonly \Gitlab\Client $client, private readonly string $name, array $parameters)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('membership')
            ->setAllowedTypes('membership', ['boolean'])
        ;
        $resolver
            ->setDefined('owned')
            ->setAllowedTypes('owned', ['boolean'])
        ;

        $this->parameters = $resolver->resolve($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromConfig(ClientInterface $client, ClientConfig $config): self
    {
        $sdk = \Gitlab\Client::createWithHttpClient($client);
        $sdk->authenticate($config->auth['token'], $config->auth['type']);

        return new self($sdk, $config->name, $config->parameters);
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
        $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => $this->parameters]);

        $repositories = [];
        foreach ($response as $repository) {
            $repositories[] = new Repository($repository['path_with_namespace']);
        }

        return new Repositories($repositories);
    }
}
