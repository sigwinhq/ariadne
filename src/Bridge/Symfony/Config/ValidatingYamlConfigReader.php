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
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

final class ValidatingYamlConfigReader implements ConfigReader
{
    public function __construct(private readonly array $clientsMap)
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
            ->requiresAtLeastOneElement()
            ->arrayPrototype()
                ->children()
                    ->enumNode('type')
                        ->values(array_keys($this->clientsMap))
                        ->isRequired()
                    ->end()
                    ->scalarNode('name')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
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
                    ->arrayNode('parameters')
                        ->scalarPrototype()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $processor = new Processor();

        /** @var list<array{type: string, name: string, auth: array{type: string, token: string}, parameters: array}> $config */
        $config = $processor->process($builder->buildTree(), [Yaml::parseFile($url)]);

        return Config::fromArray($url, $config);
    }
}
