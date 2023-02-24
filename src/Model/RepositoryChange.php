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

final class RepositoryChange
{
    public function __construct(public readonly string $name, public readonly mixed $actual, public readonly mixed $expected)
    {
    }

    public function isActual(): bool
    {
        return $this->actual === $this->expected;
    }
}
