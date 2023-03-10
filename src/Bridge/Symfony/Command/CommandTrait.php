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

namespace Sigwin\Ariadne\Bridge\Symfony\Command;

use Sigwin\Ariadne\Bridge\Symfony\Console\Style\AriadneStyle;
use Sigwin\Ariadne\Model\AdrianeConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileCollection;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait CommandTrait
{
    private function createConfiguration(): void
    {
        $this
            ->addArgument('profile-name', InputArgument::OPTIONAL, 'Profile to use', null, function (CompletionInput $input): array {
                return $this->getConfigProfileAttributes($this->configReader->read($this->getConfigUrl($input)), 'name');
            })
            ->addOption('config-file', 'C', InputOption::VALUE_OPTIONAL, 'Configuration file to use (YAML)')
            ->addOption('profile-type', 'T', InputOption::VALUE_OPTIONAL, 'Profile type to use', null, function (CompletionInput $input): array {
                return $this->getConfigProfileAttributes($this->configReader->read($this->getConfigUrl($input)), 'type');
            })
        ;
    }

    private function getProfileCollection(InputInterface $input, AriadneStyle $style): ProfileCollection
    {
        $style->heading();

        $config = $this->configReader->read($this->getConfigUrl($input));
        $style->note(sprintf('Using config: %1$s', $config->url));

        $names = $this->getConfigProfileAttributes($config, 'name');
        $types = $this->getConfigProfileAttributes($config, 'type');

        return $this->clientCollectionFactory->create($config, ProfileFilter::create($this->getInputVariable($input->getArgument('profile-name'), 'name', $names), $this->getInputVariable($input->getOption('profile-type'), 'type', $types)));
    }

    /**
     * @return array<RepositoryPlan>
     */
    private function renderPlans(Profile $profile, AriadneStyle $style): array
    {
        $style->profile($profile);

        $plans = [];
        foreach ($profile as $repository) {
            $plan = $profile->plan($repository);

            if ($plan->isActual() === false) {
                $plans[] = $plan;
            }
        }

        if ($plans === []) {
            $style->info('Profile up to date.');
        }

        foreach ($plans as $plan) {
            $style->plan($plan);
        }

        return $plans;
    }

    private function getConfigUrl(InputInterface $input): ?string
    {
        /**
         * @var null|string $configFile
         *
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $configFile = $input->getOption('config-file');

        return $configFile;
    }

    /**
     * @param array<string> $allowed
     */
    private function getInputVariable(mixed $value, string $name, array $allowed): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! \is_string($value)) {
            throw new \InvalidArgumentException('Invalid value');
        }

        if (! \in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid profile %1$s "%2$s". Allowed values: "%3$s"', $name, $value, implode('", "', $allowed)));
        }

        return $value;
    }

    /**
     * @param "name"|"type" $name
     *
     * @return array<string>
     */
    private function getConfigProfileAttributes(AdrianeConfig $config, string $name): array
    {
        $attributes = [];
        foreach ($config as $profile) {
            /**
             * @phpstan-ignore-next-line
             *
             * @var string $attribute
             */
            $attribute = $profile->{$name};

            $attributes[] = $attribute;
        }

        return $attributes;
    }
}
