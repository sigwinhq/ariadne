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

namespace Sigwin\Ariadne\Bridge\Symfony;

use Sigwin\Ariadne\Bridge\Symfony\DependencyInjection\CompilerPass\RemoveCommandsCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return __DIR__.'/../../..';
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RemoveCommandsCompilerPass());
    }
}
