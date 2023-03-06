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

enum RepositoryAttributeAccess: string
{
    case READ_ONLY = 'read-only';

    case READ_WRITE = 'read-write';
}
