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
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait CommandTrait
{
    private function createConfiguration(): void
    {
        $this
            ->addOption('config-file', 'C', InputOption::VALUE_OPTIONAL, 'Configuration file to use (YAML)')
        ;
    }

    private function getProfileCollection(InputInterface $input, AriadneStyle $style): ProfileCollection
    {
        $style->heading();

        /**
         * @var null|string $configFile
         *
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $configFile = $input->getOption('config-file');

        $config = $this->configReader->read($configFile);
        $style->note(sprintf('Using config: %1$s', $config->url));

        return $this->clientCollectionFactory->create($config);
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
}
