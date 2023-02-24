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

use Sigwin\Ariadne\ConfigReader;
use Sigwin\Ariadne\ProfileCollectionFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ariadne:sync', aliases: ['sync'])]
final class SyncCommand extends Command
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
        $style = new SymfonyStyle($input, $output);
        $profiles = $this->getProfileCollection($input, $style);

        $skipped = 0;
        foreach ($profiles as $profile) {
            $style->section($profile->getName());

            $plans = [];
            foreach ($profile as $repository) {
                $plan = $profile->plan($repository);

                if ($plan->isActual() === false) {
                    $plans[] = $plan;
                }
            }

            if ($plans === []) {
                $style->info('Profile already up to date.');

                continue;
            }

            $count = \count($plans);
            foreach ($plans as $plan) {
                $style->writeln($plan->repository->path);
            }

            // TODO: show exact diff for each repo here
            if ($style->confirm('Update these repos?') === false) {
                $skipped += $count;
                $style->warning(sprintf('Skipping updating %1$s repos', $count));

                continue;
            }

            $style->warning(sprintf('Updating %1$s repos', $count));
            foreach ($plans as $plan) {
                $profile->apply($plan);
            }
        }

        if ($skipped > 0) {
            $style->warning(sprintf('Completed with %1$d plans skipped.', $skipped));

            return 1;
        }

        $style->success('Completed.');

        return 0;
    }
}
