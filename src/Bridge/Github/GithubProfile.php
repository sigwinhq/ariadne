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

use Github\Client;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsProfile;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Profile;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsProfile(type: 'github')]
final class GithubProfile implements Profile
{
    /**
     * @var array{organizations: bool}
     */
    private readonly array $options;

    private Repositories $repositories;

    private function __construct(private readonly Client $client, private readonly string $name, private readonly ProfileConfig $config)
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
            ->setDefault('type', 'access_token_header')
            ->setAllowedTypes('type', 'string')
            ->setAllowedValues('type', ['access_token_header'])
        ;
        $resolver
            ->setDefined('token')
            ->setAllowedTypes('token', 'string')
        ;
        /** @var array{type: string, token: string} $auth */
        $auth = $resolver->resolve($config->clientConfig->auth);

        $sdk = Client::createWithHttpClient($client);
        $sdk->authenticate($auth['token'], $auth['type']);

        return new self($sdk, $config->name, $config);
    }

    public function getApiVersion(): string
    {
        return $this->client->getApiVersion();
    }

    public function getApiUser(): ProfileUser
    {
        /** @var array{login: string} $me */
        $me = $this->client->me()->show();

        return new ProfileUser($me['login']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSummary(): ProfileSummary
    {
        return new ProfileSummary($this->getRepositories());
    }

    private function getRepositories(): Repositories
    {
        if (! isset($this->repositories)) {
            $repositories = [];

            /** @var list<array{full_name: string}> $userRepositories */
            $userRepositories = $this->client->user()->myRepositories();
            foreach ($userRepositories as $userRepository) {
                $repositories[] = new Repository($userRepository['full_name']);
            }

            if ($this->options['organizations'] ?? false) {
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
            $this->repositories = new Repositories($repositories);
        }

        return $this->repositories;
    }

    /**
     * @param array<string, bool|string> $options
     *
     * @return array{organizations: bool}
     */
    private function validateOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('organizations')
            ->setDefault('organizations', true)
            ->setAllowedTypes('organizations', ['boolean'])
        ;
        /** @var array{organizations: bool} $options */
        $options = $resolver->resolve($options);

        return $options;
    }
}
