<?php

declare(strict_types=1);

/*
 * This file is part of the ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne\Bridge\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsClient
{
    public function __construct(public string $name)
    {
    }
}
