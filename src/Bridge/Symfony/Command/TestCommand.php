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

#[AsCommand(name: 'ariadne:test', aliases: ['test'])]
final class TestCommand extends Command
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

        foreach ($profiles as $profile) {
            $style->section($profile->getName());
            $style->horizontalTable(
                ['API Version', 'API User', 'Repositories', 'Templates'],
                [
                    [
                        $profile->getApiVersion(),
                        $profile->getApiUser(),
                        '$profile->getRepositories()',
                        '$profile->getTemplates()',
                    ],
                ]
            );

            foreach ($profile as $repository) {
                $style->writeln($repository->path);
            }
        }

        return 0;
    }
}
