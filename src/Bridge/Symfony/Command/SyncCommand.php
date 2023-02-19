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

use Gitlab\Client;
use Gitlab\ResultPager;
use Sigwin\Ariadne\ClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'ariadne:sync', aliases: ['sync'])]
final class SyncCommand extends Command
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Sigwin Ariadne');

        // $version = $this->client->version()->show();
        // $style->info(sprintf('This is Gitlab %1$s', $version['version']));

        // $me = $this->client->users()->me();
        // $style->info(sprintf('You are %1$s, %2$s', $me['username'], $me['web_url']));

        $config = Yaml::parseFile('ariadne.yaml');

        foreach ($config as $spec) {
            $client = $this->clientFactory->create($spec);

            dump($client->getCurrentUser());
        }

        return 0;

        $pager = new ResultPager($this->client);
        $projects = $pager->fetchAllLazy($this->client->projects(), 'all', ['parameters' => [
            'simple' => true,
            'membership' => true,
        ]]);
        foreach ($projects as $project) {
            $output->writeln($project['path_with_namespace']);
        }

        return 0;
    }
}
