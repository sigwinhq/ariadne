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

enum RepositoryType: string
{
    case SOURCE = 'source';
    case FORK = 'fork';

    public static function fromFork(bool $fork): self
    {
        return $fork ? self::FORK : self::SOURCE;
    }
}
