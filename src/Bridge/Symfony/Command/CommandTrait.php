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

use Sigwin\Ariadne\ClientCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\StyleInterface;

trait CommandTrait
{
    private function createConfiguration(): void
    {
        $this
            ->addOption('config-file', 'C', InputOption::VALUE_OPTIONAL, 'Configuration file to use (YAML)')
        ;
    }

    private function getClientCollection(InputInterface $input, StyleInterface $style): ClientCollection
    {
        $style->title('Sigwin Ariadne');

        /**
         * @phpstan-var null|string $configFile
         *
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $configFile = $input->getOption('config-file');

        $config = $this->configReader->read($configFile);
        $style->note(sprintf('Using config: %1$s', $config->url));

        return $this->clientCollectionFactory->create($config);
    }
}
