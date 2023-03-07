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

final class RepositoryPlan
{
    /**
     * @param array<RepositoryChangeCollection> $changes
     */
    public function __construct(public readonly Repository $repository, private readonly array $changes)
    {
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

    /**
     * @return list<RepositoryChange>
     */
    public function generateDiff(): array
    {
        $diff = [];
        foreach ($this->changes as $change) {
            $diff = array_replace($diff, $change->generateDiff());
        }

        return array_values($diff);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateAttributeChanges(): array
    {
        $changes = [];
        foreach ($this->generateDiff() as $change) {
            if ($change->isActual() === false) {
                $changes[$change->name] = $change->expected;
            }
        }

        return $changes;
    }
}
