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

namespace Sigwin\Ariadne\Bridge\Symfony\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveCommandsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $debug = (bool) $container->getParameter('kernel.debug');

        $commands = $container->findTaggedServiceIds('console.command');
        foreach (array_keys($commands) as $id) {
            $definition = $container->getDefinition($id);

            $className = $definition->getClass();
            if ($className === null) {
                continue;
            }

            if (! str_starts_with($className, 'Sigwin')
                && (! $debug || ! \in_array($id, [
                    'console.command.debug_autowiring',
                    'console.command.config_debug',
                    'console.command.container_debug',
                    'twig.command.debug',
                ], true))) {
                $container->removeDefinition($id);
            }
        }
    }
}
