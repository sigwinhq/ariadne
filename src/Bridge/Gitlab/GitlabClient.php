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
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsClient(name: 'gitlab')]
final class GitlabClient implements Client
{
    /** @var array{membership: ?bool, owned: ?bool} */
    private readonly array $options;

    private function __construct(private readonly \Gitlab\Client $client, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->options = $this->validateOptions($this->config->clientConfig->options);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromConfig(ClientInterface $client, ProfileConfig $config): self
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('type')
            ->setDefault('type', 'http_token')
            ->setAllowedTypes('type', 'string')
            ->setAllowedValues('type', ['http_token'])
        ;
        $resolver
            ->setDefined('token')
            ->setAllowedTypes('token', 'string')
        ;
        /** @var array{type: string, token: string} $auth */
        $auth = $resolver->resolve($config->clientConfig->auth);

        $sdk = \Gitlab\Client::createWithHttpClient($client);
        $sdk->authenticate($auth['token'], $auth['type']);

        return new self($sdk, $config->name, $config);
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
        $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => $this->options]);

        $repositories = [];
        foreach ($response as $repository) {
            $repositories[] = new Repository($repository['path_with_namespace']);
        }

        return new Repositories($repositories);
    }

    /**
     * @param array<string, bool|string> $options
     *
     * @return array{membership: ?bool, owned: ?bool}
     */
    private function validateOptions(array $options): array
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

        /** @var array{membership: ?bool, owned: ?bool} $options */
        $options = $resolver->resolve($options);

        return $options;
    }
}
