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

final class RepositoryChangeCollection
{
    /**
     * @param array<RepositoryChange> $changes
     */
    private function __construct(public readonly Template $template, public readonly array $changes)
    {
    }

    /**
     * @param array<RepositoryChange> $changes
     */
    public static function fromTemplate(Template $template, array $changes): self
    {
        return new self($template, $changes);
    }

    public function isActual(): bool
    {
        foreach ($this->changes as $change) {
            if ($change->isActual() === false) {
                return false;
            }
        }

        return true;
    }
}
