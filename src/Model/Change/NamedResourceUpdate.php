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

namespace Sigwin\Ariadne\Model\Change;

use Sigwin\Ariadne\Model\Collection\RepositoryChangeCollection;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\RepositoryChange;

final class NamedResourceUpdate implements RepositoryChange
{
    public function __construct(public readonly NamedResource $resource, public readonly RepositoryChangeCollection $changes)
    {
    }

    public function isActual(): bool
    {
        return $this->changes->isActual();
    }
}
