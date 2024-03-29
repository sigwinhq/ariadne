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
use Sigwin\Ariadne\Exception\ConfigException;
use Sigwin\Ariadne\ProfileCollectionFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ariadne:diff', aliases: ['diff'])]
final class DiffCommand extends Command
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
        try {
            $profiles = $this->getProfileCollection($input, $style);
            if (\count($profiles) === 0) {
                $style->warning('No profiles found.');

                return self::INVALID;
            }
        } catch (ConfigException $exception) {
            return $style->exception($exception);
        }

        foreach ($profiles as $profile) {
            $this->renderPlans($profile, $style);
        }

        return self::SUCCESS;
    }
}
