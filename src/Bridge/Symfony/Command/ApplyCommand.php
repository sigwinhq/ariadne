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
use Sigwin\Ariadne\ConfigReader;
use Sigwin\Ariadne\ProfileCollectionFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ariadne:apply', aliases: ['apply'])]
final class ApplyCommand extends Command
{
    use CommandTrait;

    public function __construct(private readonly ConfigReader $configReader, private readonly ProfileCollectionFactory $clientCollectionFactory)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->createConfiguration();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new AriadneStyle($input, $output);
        $profiles = $this->getProfileCollection($input, $style);
        if (\count($profiles) === 0) {
            $style->warning('No profiles found.');

            return self::FAILURE;
        }

        $skipped = 0;
        foreach ($profiles as $profile) {
            $plans = $this->renderPlans($profile, $style);

            $count = \count($plans);
            if ($count === 0) {
                continue;
            }
            if ($style->confirm(sprintf('Update these %1$s repos?', $count)) === false) {
                $skipped += $count;
                $style->warning(sprintf('Skipping updating %1$s repos', $count));

                continue;
            }

            $style->info(sprintf('Updating %1$s repos', $count));
            foreach ($plans as $plan) {
                $profile->apply($plan);

                if ($style->isVerbose()) {
                    $style->writeln(sprintf('Updated %1$s', $plan->getResource()->getName()));
                }
            }
        }

        if ($skipped > 0) {
            $style->warning(sprintf('Completed, but %1$d repos skipped.', $skipped));

            return self::INVALID;
        }

        $style->success('Completed.');

        return self::SUCCESS;
    }
}
