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

use Sigwin\Ariadne\ClientCollectionFactory;
use Sigwin\Ariadne\ConfigReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ariadne:test', aliases: ['test'])]
final class TestCommand extends Command
{
    use CommandTrait;

    public function __construct(private readonly ConfigReader $configReader, private readonly ClientCollectionFactory $clientCollectionFactory)
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
        $clients = $this->getClientCollection($input, $style);

        foreach ($clients as $client) {
            $style->section($client->getName());
            $style->horizontalTable(
                ['API Version', 'User', 'Repos'],
                [
                    [
                        $client->getApiVersion(),
                        $client->getCurrentUser()->username,
                        $client->getRepositories()->getSummary(),
                    ],
                ]
            );
        }

        return 0;
    }
}
