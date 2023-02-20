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

use Sigwin\Ariadne\Client\IterableClientProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ariadne:test', aliases: ['test'])]
final class TestCommand extends Command
{
    public function __construct(private readonly IterableClientProvider $clients)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Sigwin Ariadne');

        foreach ($this->clients as $client) {
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
