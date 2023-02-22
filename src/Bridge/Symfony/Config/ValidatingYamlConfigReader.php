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

namespace Sigwin\Ariadne\Bridge\Symfony\Config;

use Sigwin\Ariadne\ConfigReader;
use Sigwin\Ariadne\Model\Config;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

final class ValidatingYamlConfigReader implements ConfigReader
{
    /**
     * @param array<string, class-string<\Sigwin\Ariadne\Profile>> $profilesMap
     */
    public function __construct(private readonly array $profilesMap)
    {
    }

    public function read(?string $url = null): Config
    {
        // TODO: support .yml, .yaml.dist, .yml.dist
        $url ??= 'ariadne.yaml';
        $url = realpath($url);
        if ($url === false) {
            throw new \InvalidArgumentException('File not found');
        }

        $builder = new TreeBuilder('ariadne');
        $builder
            ->getRootNode()
            ->children()
                ->arrayNode('profiles')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('type')
                                ->values(array_keys($this->profilesMap))
                                ->isRequired()
                            ->end()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('client')
                                ->isRequired()
                                ->children()
                                    ->arrayNode('auth')
                                        ->isRequired()
                                        ->children()
                                            ->scalarNode('type')
                                            ->end()
                                            ->scalarNode('token')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('options')
                                        ->scalarPrototype()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('templates')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->arrayNode('filter')
                                            ->isRequired()
                                            ->children()
                                                ->enumNode('type')
                                                    ->values(array_map(static fn (RepositoryType $case) => $case->value, RepositoryType::cases()))
                                                ->end()
                                                ->scalarNode('path')
                                                ->end()
                                                ->enumNode('visibility')
                                                    ->values(array_map(static fn (RepositoryVisibility $case) => $case->value, RepositoryVisibility::cases()))
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $processor = new Processor();

        /** @var array{
         *     profiles: list<array{
         *          type: string,
         *          name: string,
         *          client: array{auth: array{type: string, token: string}, options: array<string, bool|string>},
         *          templates: list<array{name: string, filter: array{type?: value-of<RepositoryType>, path?: string, visibility?: value-of<RepositoryVisibility>}}>
         *     }>} $config */
        $config = $processor->process($builder->buildTree(), [Yaml::parseFile($url)]);

        return Config::fromArray($url, $config);
    }
}
