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

namespace Sigwin\Ariadne\Bridge\Symfony\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkApplication;
use Symfony\Component\HttpKernel\KernelInterface;

final class Application extends FrameworkApplication
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);
        $this->setDefinition($this->getDefaultInputDefinition());

        $this->setName('Ariadne');
        $this->setVersion('dev-main');
        // $this->setDefaultCommand('diff');
    }

    public function getLongVersion(): string
    {
        return sprintf('%1$s <info>%2$s</>', Logo::ASCII, $this->getVersion());
    }
}
