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
use Sigwin\Ariadne\Bridge\ProfileTrait;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryAttributeAccess;
use Sigwin\Ariadne\Model\RepositoryCollection;
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @psalm-type TRepository array{id: int, path_with_namespace: string, visibility: string, forked_from_project: ?array<string, int|string>}
 */
final class GitlabProfile implements Profile
{
    use ProfileTrait;

    /** @var array{membership: bool, owned: bool} */
    private readonly array $options;

    private RepositoryCollection $repositories;

    private function __construct(private readonly Client $client, private readonly ProfileTemplateFactory $templateFactory, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->options = $this->validateOptions($this->config->client->options);

        $this->validateAttributes($this->config->templates);
    }

    public static function getType(): string
    {
        return 'gitlab';
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
        if ($config->client->url !== null) {
            $sdk->setUrl($config->client->url);
        }

        return new self($sdk, $templateFactory, $config->name, $config);
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

    private function getRepositories(): RepositoryCollection
    {
        if (! isset($this->repositories)) {
            $pager = new ResultPager($this->client);
            /** @var list<TRepository> $response */
            $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => $this->options]);

            $repositories = [];
            foreach ($response as $repository) {
                $repositories[] = new Repository($repository, RepositoryType::fromFork(isset($repository['forked_from_project'])), $repository['id'], $repository['path_with_namespace'], RepositoryVisibility::from($repository['visibility']));
            }
            $this->repositories = RepositoryCollection::fromArray($repositories);
        }

        return $this->repositories;
    }

    public function apply(RepositoryPlan $plan): void
    {
        $this->client->projects()->update($plan->repository->id, $plan->generateAttributeChanges());
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

    /**
     * @return array<string, array{access: RepositoryAttributeAccess}>
     */
    private function getAttributes(): array
    {
        return [
            'description' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'star_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
        ];
    }
}
