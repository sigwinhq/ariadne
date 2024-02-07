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

use Github\Api\Repository\Pages;
use Github\Client;
use Github\HttpClient\Builder;
use Github\ResultPager;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Bridge\ProfileTrait;
use Sigwin\Ariadne\Exception\ConfigException;
use Sigwin\Ariadne\Exception\RuntimeException;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryAttributeAccess;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\NamedResourceCollection;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileTemplateFactory;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @psalm-type TRepository array{id: int, fork: bool, full_name: string, private: bool, topics: array<string>, language?: string, archived: bool}
 * @psalm-type TCollaborator array{login: string, role_name: string}
 */
final class GithubProfile implements Profile
{
    use ProfileTrait;

    private function __construct(private readonly Client $client, private readonly ProfileTemplateFactory $templateFactory, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->validateAttributes($this->config->templates);
    }

    public static function getType(): string
    {
        return 'github';
    }

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

        try {
            /** @var array{type: string, token: string} $auth */
            $auth = $resolver->resolve($config->client->auth);
        } catch (InvalidOptionsException $exception) {
            throw ConfigException::fromInvalidOptionsException('client.auth', $config, $exception);
        }

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
        $parts = explode('/', $plan->getResource()->getName());
        if (\count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid repository name');
        }
        [$username, $repository] = $parts;

        $attributes = [];
        foreach ($plan->filter(NamedResourceAttributeUpdate::class) as $change) {
            if ($change->isActual()) {
                continue;
            }
            $attributes[$change->getResource()->getName()] = $change->expected;
        }

        if (isset($attributes['has_pages'])) {
            /** @var Pages $pages */
            $pages = $this->client->repositories()->pages();
            if ($attributes['has_pages'] === true) {
                $pages->enable($username, $repository);
            } else {
                $pages->disable($username, $repository);
            }
            unset($attributes['has_pages']);
        }

        if ($attributes !== []) {
            $this->client->repositories()->update($username, $repository, $attributes);
        }
    }

    /**
     * @return NamedResourceCollection<Repository>
     */
    private function getRepositories(): NamedResourceCollection
    {
        if (! isset($this->repositories)) {
            $repositories = [];
            $needsUsers = $this->needsUsers();
            $needsExtendedRepository = $this->needsExtendedRepository();

            $pager = new ResultPager($this->client);
            try {
                /** @var list<TRepository> $response */
                $response = $pager->fetchAll($this->client->user(), 'myRepositories');
            } catch (\Github\Exception\RuntimeException $exception) {
                throw RuntimeException::fromRuntimeException($exception);
            }
            foreach ($response as $repository) {
                $parts = explode('/', $repository['full_name']);
                if (\count($parts) !== 2) {
                    throw new \InvalidArgumentException('Invalid repository name');
                }
                [$username, $name] = $parts;

                if ($needsExtendedRepository) {
                    /** @var TRepository $repository */
                    $repository = $this->client->repositories()->show($username, $name);
                }

                $users = [];
                if ($needsUsers) {
                    /** @var list<TCollaborator> $collaborators */
                    $collaborators = $pager->fetchAll($this->client->repository()->collaborators(), 'all', [$username, $name]);
                    foreach ($collaborators as $collaborator) {
                        $users[] = new RepositoryUser($collaborator['login'], $collaborator['role_name']);
                    }
                }
                $users = SortedNamedResourceCollection::fromArray($users);

                $repositories[] = new Repository(
                    $repository,
                    RepositoryType::fromFork($repository['fork']),
                    RepositoryVisibility::fromPrivate($repository['private']),
                    $users,
                    $repository['id'],
                    $repository['full_name'],
                    $repository['topics'],
                    isset($repository['language']) && $repository['language'] !== '' ? (array) $repository['language'] : [],
                    $repository['archived'],
                );
            }

            $this->repositories = SortedNamedResourceCollection::fromArray($repositories);
        }

        return $this->repositories;
    }

    /**
     * @return array<string, array{access: RepositoryAttributeAccess, extended?: bool}>
     */
    private function getAttributes(): array
    {
        return [
            'allow_squash_merge' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'allow_merge_commit' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'allow_rebase_merge' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'allow_auto_merge' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'allow_update_branch' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'delete_branch_on_merge' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
            'use_squash_pr_title_as_default' => ['access' => RepositoryAttributeAccess::READ_WRITE, 'extended' => true],
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

    private function needsExtendedRepository(): bool
    {
        $extendedAttributes = array_filter($this->getAttributes(), static fn (array $attribute) => $attribute['extended'] ?? false);
        foreach ($this->config->templates as $template) {
            if (array_intersect_key($template->target->attribute, $extendedAttributes) !== []) {
                return true;
            }
        }

        return false;
    }
}
