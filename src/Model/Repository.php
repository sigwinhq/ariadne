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

namespace Sigwin\Ariadne\Model;

final class Repository
{
    /**
     * @param array<string, null|array<string, int|string>|bool|string> $response
     */
    public function __construct(private readonly array $response, public readonly RepositoryType $type, public readonly string $path, public readonly RepositoryVisibility $visibility)
    {
    }

    public function createChangeForTemplate(Template $template): RepositoryChangeCollection
    {
        $changes = [];
        foreach ($template->target->attribute as $name => $expected) {
            if (\array_key_exists($name, $this->response) === false) {
                // TODO: fix error message, Did you mean etc
                throw new \InvalidArgumentException('Invalid argument '.$name);
            }
            $actual = $this->response[$name];

            $changes[] = new RepositoryChange($name, $actual, $expected);
        }

        return RepositoryChangeCollection::fromTemplate($template, $changes);
    }
}
