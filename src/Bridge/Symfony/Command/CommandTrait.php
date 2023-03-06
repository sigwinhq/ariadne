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
                $config = $this->configReader->read($this->getConfigUrl($input));

                $names = [];
                foreach ($config as $profile) {
                    $names[] = $profile->name;
                }

                return $names;
            })
            ->addOption('config-file', 'C', InputOption::VALUE_OPTIONAL, 'Configuration file to use (YAML)')
            ->addOption('profile-type', 'T', InputOption::VALUE_OPTIONAL, 'Profile type to use', null, function (CompletionInput $input): array {
                $config = $this->configReader->read($this->getConfigUrl($input));

                $types = [];
                foreach ($config as $profile) {
                    $types[] = $profile->type;
                }

                return $types;
            })
        ;
    }

    private function getProfileCollection(InputInterface $input, AriadneStyle $style): ProfileCollection
    {
        $style->heading();

        $config = $this->configReader->read($this->getConfigUrl($input));
        $style->note(sprintf('Using config: %1$s', $config->url));

        return $this->clientCollectionFactory->create($config, ProfileFilter::create($this->getProfileName($input), $this->getProfileType($input)));
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

    private function getProfileName(InputInterface $input): ?string
    {
        /**
         * @var null|string $profileName
         *
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $profileName = $input->getArgument('profile-name');

        return $profileName;
    }

    private function getProfileType(InputInterface $input): ?string
    {
        /**
         * @var null|string $profileType
         *
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $profileType = $input->getOption('profile-type');

        return $profileType;
    }
}
