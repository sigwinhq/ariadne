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

use Gitlab\Client;
use Gitlab\ResultPager;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsProfile;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\Templates;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsProfile(type: 'gitlab')]
final class GitlabProfile implements Profile
{
    /** @var array{membership: bool, owned: bool} */
    private readonly array $options;

    private Repositories $repositories;

    private function __construct(private readonly Client $client, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->options = $this->validateOptions($this->config->client->options);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromConfig(ClientInterface $client, ProfileTemplateFactory $templateFactory, ProfileConfig $config): self
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
        $auth = $resolver->resolve($config->client->auth);

        $sdk = Client::createWithHttpClient($client);
        $sdk->authenticate($auth['token'], $auth['type']);

        return new self($sdk, $config->name, $config);
    }

    public function getApiVersion(): string
    {
        /** @var array{version: string} $info */
        $info = $this->client->version()->show();

        return $info['version'];
    }

    public function getApiUser(): ProfileUser
    {
        /** @var array{username: string} $me */
        $me = $this->client->users()->me();

        return new ProfileUser($me['username']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSummary(): ProfileSummary
    {
        return new ProfileSummary($this->getRepositories());
    }

    public function getIterator(): \Traversable
    {
        return new Templates([]);
    }

    private function getRepositories(): Repositories
    {
        if (! isset($this->repositories)) {
            $pager = new ResultPager($this->client);
            /** @var list<array{path_with_namespace: string}> $response */
            $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => $this->options]);

            $repositories = [];
            foreach ($response as $repository) {
                $repositories[] = new Repository($repository['path_with_namespace']);
            }

            $this->repositories = new Repositories($repositories);
        }

        return $this->repositories;
    }

    /**
     * @param array<string, bool|string> $options
     *
     * @return array{membership: bool, owned: bool}
     */
    private function validateOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('membership')
            ->setDefault('membership', false)
            ->setAllowedTypes('membership', ['boolean'])
        ;
        $resolver
            ->setDefined('owned')
            ->setDefault('owned', true)
            ->setAllowedTypes('owned', ['boolean'])
        ;

        /** @var array{membership: bool, owned: bool} $options */
        $options = $resolver->resolve($options);

        return $options;
    }
}
