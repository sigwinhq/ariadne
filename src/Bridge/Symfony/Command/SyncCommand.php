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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ariadne:sync')]
final class SyncCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}
