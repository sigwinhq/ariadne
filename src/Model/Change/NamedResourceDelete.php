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

use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;

final class NamedResourceDelete implements NamedResourceChange
{
    public function __construct(public readonly NamedResource $resource)
    {
    }

    public function getResource(): NamedResource
    {
        return $this->resource;
    }

    public function isActual(): bool
    {
        return false;
    }
}
