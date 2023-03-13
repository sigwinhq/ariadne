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

namespace Sigwin\Ariadne\Model\Collection;

use Sigwin\Ariadne\Model\Change\AttributeUpdate;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\RepositoryChange;

final class RepositoryChangeCollection
{
    /**
     * @param array<RepositoryChange> $changes
     */
    private function __construct(public readonly ProfileTemplate $template, public readonly array $changes)
    {
    }

    /**
     * @param array<RepositoryChange> $changes
     */
    public static function fromTemplate(ProfileTemplate $template, array $changes): self
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

    /**
     * @return array<string, AttributeUpdate>
     */
    public function getAttributeChanges(): array
    {
        $diff = [];
        foreach ($this->changes as $change) {
            if ($change instanceof AttributeUpdate === false) {
                continue;
            }
            $diff[$change->name] = $change;
        }

        return $diff;
    }
}
