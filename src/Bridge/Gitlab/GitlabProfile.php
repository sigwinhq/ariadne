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
use Gitlab\HttpClient\Builder;
use Gitlab\ResultPager;
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
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @psalm-type TRepository array{id: int, path_with_namespace: string, visibility: string, forked_from_project: ?array<string, int|string>, topics: array<string>, archived: bool}
 * @psalm-type TCollaborator array{username: string, access_level: int}
 */
final class GitlabProfile implements Profile
{
    use ProfileTrait;

    private const USER_ROLE = [
        10 => 'guest',
        20 => 'reporter',
        30 => 'developer',
        40 => 'maintainer',
        50 => 'owner',
    ];

    /** @var array{membership: bool, owned: bool} */
    private readonly array $options;

    private function __construct(private readonly Client $client, private readonly ProfileTemplateFactory $templateFactory, private readonly string $name, private readonly ProfileConfig $config)
    {
        $this->options = $this->validateOptions($this->config->client->options);

        $this->validateAttributes($this->config->templates);
    }

    public static function getType(): string
    {
        return 'gitlab';
    }

    public static function fromConfig(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $templateFactory, CacheItemPoolInterface $cachePool): self
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
        try {
            /** @var array{type: string, token: string} $auth */
            $auth = $resolver->resolve($config->client->auth);
        } catch (InvalidOptionsException $exception) {
            throw ConfigException::fromInvalidOptionsException('client.auth', $config, $exception);
        }

        $builder = new Builder($client);
        $builder->addCache($cachePool);
        $sdk = new Client($builder);
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

    /**
     * @return NamedResourceCollection<Repository>
     */
    private function getRepositories(): NamedResourceCollection
    {
        if (! isset($this->repositories)) {
            $repositories = [];

            $needsUsers = $this->needsUsers();
            $needsLanguages = $this->needsLanguages();

            $pager = new ResultPager($this->client);
            try {
                /** @var list<TRepository> $response */
                $response = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => $this->options]);
                foreach ($response as $repository) {
                    $languages = [];
                    if ($needsLanguages) {
                        /** @var array<string, int> $languages */
                        $languages = $this->client->projects()->languages($repository['id']);
                        $languages = array_keys($languages);
                    }

                    $users = [];
                    if ($needsUsers) {
                        /** @var list<TCollaborator> $collaborators */
                        $collaborators = $pager->fetchAll($this->client->projects(), 'allMembers', [$repository['id']]);
                        foreach ($collaborators as $collaborator) {
                            $users[] = new RepositoryUser($collaborator['username'], self::USER_ROLE[$collaborator['access_level']]);
                        }
                    }
                    $users = SortedNamedResourceCollection::fromArray($users);

                    $repositories[] = new Repository(
                        $repository,
                        RepositoryType::fromFork(isset($repository['forked_from_project'])),
                        RepositoryVisibility::from($repository['visibility']),
                        $users,
                        $repository['id'],
                        $repository['path_with_namespace'],
                        $repository['topics'],
                        $languages,
                        $repository['archived'],
                    );
                }
            } catch (\Gitlab\Exception\RuntimeException $exception) {
                throw RuntimeException::fromRuntimeException($exception);
            }
            $this->repositories = SortedNamedResourceCollection::fromArray($repositories);
        }

        return $this->repositories;
    }

    public function apply(NamedResourceChangeCollection $plan): void
    {
        /** @var Repository $repository */
        $repository = $plan->getResource();

        $attributes = [];
        foreach ($plan->filter(NamedResourceAttributeUpdate::class) as $change) {
            $attributes[$change->getResource()->getName()] = $change->expected;
        }
        $this->client->projects()->update($repository->id, $attributes);
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

        try {
            /** @var array{membership: bool, owned: bool} $options */
            $options = $resolver->resolve($options);
        } catch (InvalidOptionsException $exception) {
            throw ConfigException::fromInvalidOptionsException('client.options', $this->config, $exception);
        } catch (UndefinedOptionsException $exception) {
            throw ConfigException::fromUndefinedOptionsException('client.options', $this->config, $exception);
        }

        return $options;
    }

    /**
     * @return array<string, array{access: RepositoryAttributeAccess}>
     */
    private function getAttributes(): array
    {
        return [
            'description' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'issues_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'lfs_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'merge_requests_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'container_registry_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'wiki_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'service_desk_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'snippets_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'packages_enabled' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'remove_source_branch_after_merge' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'only_allow_merge_if_pipeline_succeeds' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'only_allow_merge_if_all_discussions_are_resolved' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'allow_merge_on_skipped_pipeline' => ['access' => RepositoryAttributeAccess::READ_WRITE],

            'monitor_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'pages_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'forking_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'analytics_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'security_and_compliance_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'environments_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'feature_flags_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'infrastructure_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'releases_access_level' => ['access' => RepositoryAttributeAccess::READ_WRITE],

            'merge_method' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'squash_option' => ['access' => RepositoryAttributeAccess::READ_WRITE],
            'squash_commit_template' => ['access' => RepositoryAttributeAccess::READ_WRITE],

            'star_count' => ['access' => RepositoryAttributeAccess::READ_ONLY],
        ];
    }
}
