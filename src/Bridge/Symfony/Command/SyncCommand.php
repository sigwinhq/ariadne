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

#[AsCommand(name: 'ariadne:sync', aliases: ['sync'])]
final class SyncCommand extends Command
{
    public function __construct(private readonly ConfigReader $configReader, private readonly ClientCollectionFactory $clientCollectionFactory)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Sigwin Ariadne');

        $clients = $this->clientCollectionFactory->create($this->configReader->read());

        foreach ($clients as $client) {
            dump($client->getCurrentUser());
        }

        return 0;
    }
}
