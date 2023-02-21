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

final class ProfileUser implements \Stringable
{
    public function __construct(public readonly string $username)
    {
    }

    public function __toString(): string
    {
        return $this->username;
    }
}
