<?php

declare(strict_types=1);

/*
 * This file is part of the ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne\Bridge\Symfony\Command;

use Sigwin\Ariadne\ClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'ariadne:test', aliases: ['test'])]
final class TestCommand extends Command
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Sigwin Ariadne');

        $config = Yaml::parseFile('ariadne.yaml');

        foreach ($config as $spec) {
            $client = $this->clientFactory->create($spec);

            $style->section($client->getName());
            $style->horizontalTable(
                ['API Version', 'User'],
                [
                    [
                        $client->getApiVersion(),
                        $client->getCurrentUser()->username,
                    ],
                ]
            );
        }

        return 0;
    }
}
