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

enum RepositoryVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public static function fromPrivate(bool $private): self
    {
        return $private ? self::PRIVATE : self::PUBLIC;
    }
}
