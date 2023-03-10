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
use Sigwin\Ariadne\EnvironmentResolver;
use Sigwin\Ariadne\Model\Config;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @psalm-import-type TConfig from Config
 */
final class ValidatingYamlConfigReader implements ConfigReader
{
    /**
     * @param array<string, class-string<\Sigwin\Ariadne\Profile>> $profilesMap
     */
    public function __construct(private readonly array $profilesMap, private readonly EnvironmentResolver $environmentResolver)
    {
    }

    public function read(?string $url = null): Config
    {
        if ($url === null) {
            $in = [
                (string) getcwd(),
            ];
            $configDir = $this->environmentResolver->getConfigDir();
            if (is_dir($configDir)) {
                $in[] = $configDir;
            }

            $finder = new Finder();
            $finder
                ->in($in)
                ->files()
                ->depth(0)
                ->name(['/ariadne\.ya?ml(\.dist)?$/'])
                ->sortByName(true)
            ;
            $found = false;
            foreach ($finder as $item) {
                $url = $item->getRealPath();
                $found = true;
                break;
            }
            if (! $found) {
                throw new \InvalidArgumentException('Configuration file ariadne.yaml(.dist) not found in '.implode(', ', $in));
            }
        } else {
            $url = realpath($url);
        }

        if ($url === false) {
            throw new \InvalidArgumentException('Configuration file not found');
        }

        /**
         * @var array<array-key, mixed> $payload
         *
         * @psalm-suppress PossiblyNullArgument
         */
        $payload = Yaml::parseFile($url);

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
                                    ->scalarNode('url')
                                        ->cannotBeEmpty()
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
                                                ->enumNode('visibility')
                                                    ->values(array_map(static fn (RepositoryVisibility $case) => $case->value, RepositoryVisibility::cases()))
                                                ->end()
                                                ->scalarNode('path')
                                                ->end()
                                                ->arrayNode('topics')
                                                    ->beforeNormalization()->castToArray()->end()
                                                    ->scalarPrototype()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('languages')
                                                    ->beforeNormalization()->castToArray()->end()
                                                    ->scalarPrototype()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('target')
                                            ->isRequired()
                                            ->children()
                                                ->arrayNode('attribute')
                                                    ->scalarPrototype()
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
            ->end()
        ;

        $processor = new Processor();

        /** @var TConfig $config */
        $config = $processor->process($builder->buildTree(), [$payload]);

        return Config::fromArray($url, $config);
    }
}
