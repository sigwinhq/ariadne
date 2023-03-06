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
use Github\HttpClient\Builder;
use Github\ResultPager;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\Attribute\AsProfile;
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
 * @psalm-type TRepository array{fork: bool, full_name: string, private: bool}
 */
#[AsProfile(type: 'github')]
final class GithubProfile implements Profile
{
    use ProfileTrait;

    private RepositoryCollection $repositories;

    private function __construct(private readonly Client $client, private readonly ProfileTemplateFactory $templateFactory, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->validateAttributes($this->config->templates);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromConfig(ClientInterface $client, ProfileTemplateFactory $templateFactory, ProfileConfig $config): self
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
        $auth = $resolver->resolve($config->client->auth);

        $builder = new Builder($client);
        $sdk = new Client($builder, enterpriseUrl: $config->client->url);
        $sdk->authenticate($auth['token'], $auth['type']);

        return new self($sdk, $templateFactory, $config->name, $config);
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

    public function apply(RepositoryPlan $plan): void
    {
    }

    private function getRepositories(): RepositoryCollection
    {
        if (! isset($this->repositories)) {
            $repositories = [];

            $pager = new ResultPager($this->client);

            /** @var list<TRepository> $userRepositories */
            $userRepositories = $pager->fetchAll($this->client->user(), 'myRepositories');
            foreach ($userRepositories as $userRepository) {
                $repositories[] = new Repository($userRepository, RepositoryType::fromFork($userRepository['fork']), $userRepository['full_name'], RepositoryVisibility::fromPrivate($userRepository['private']));
            }

            $this->repositories = RepositoryCollection::fromArray($repositories);
        }

        return $this->repositories;
    }

    /**
     * @return array<string, array{access: RepositoryAttributeAccess}>
     */
    private function getAttributes(): array
    {
        return [
            'description' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'stargazers_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
        ];
    }
}
