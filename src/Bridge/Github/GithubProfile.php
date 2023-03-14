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
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\ProfileTrait;
use Sigwin\Ariadne\Model\Collection\NamedResourceCollection;
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryAttributeAccess;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @psalm-type TRepository array{id: int, fork: bool, full_name: string, private: bool, topics: array<string>, language?: string}
 * @psalm-type TCollaborator array{login: string, role_name: string}
 */
final class GithubProfile implements Profile
{
    use ProfileTrait;

    private RepositoryCollection $repositories;

    private function __construct(private readonly Client $client, private readonly ProfileTemplateFactory $templateFactory, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->validateAttributes($this->config->templates);
    }

    public static function getType(): string
    {
        return 'github';
    }

    /**
     * {@inheritDoc}
     */
    public static function fromConfig(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $templateFactory, CacheItemPoolInterface $cachePool): self
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
        $sdk->addCache($cachePool);

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

    public function apply(NamedResourceChangeCollection $plan): void
    {
        [$username, $repository] = explode('/', $plan->getResource()->getName(), 2);

        $this->client->repositories()->update($username, $repository, $plan->getAttributeChanges());
    }

    private function getRepositories(): RepositoryCollection
    {
        if (! isset($this->repositories)) {
            $repositories = [];

            $pager = new ResultPager($this->client);

            $needsUsers = false;
            foreach ($this->config->templates as $template) {
                if ($template->target->users !== []) {
                    $needsUsers = true;
                    break;
                }
            }

            /** @var list<TRepository> $response */
            $response = $pager->fetchAll($this->client->user(), 'myRepositories');
            foreach ($response as $repository) {
                $users = [];
                if ($needsUsers) {
                    [$username, $name] = explode('/', $repository['full_name'], 2);

                    /** @var list<TCollaborator> $collaborators */
                    $collaborators = $pager->fetchAll($this->client->repository()->collaborators(), 'all', [$username, $name]);
                    foreach ($collaborators as $collaborator) {
                        $users[] = new RepositoryUser($collaborator['login'], $collaborator['role_name']);
                    }
                }
                $users = NamedResourceCollection::fromArray($users);

                $repositories[] = new Repository(
                    $repository,
                    RepositoryType::fromFork($repository['fork']),
                    RepositoryVisibility::fromPrivate($repository['private']),
                    $users,
                    $repository['id'],
                    $repository['full_name'],
                    $repository['topics'],
                    isset($repository['language']) && $repository['language'] !== '' ? (array) $repository['language'] : [],
                );
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
            'has_discussions' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'has_downloads' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'has_issues' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'has_pages' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'has_projects' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'has_wiki' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'open_issues_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
            'stargazers_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
            'watchers_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
        ];
    }
}
