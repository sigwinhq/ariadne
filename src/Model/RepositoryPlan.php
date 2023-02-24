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

use Sigwin\Ariadne\Profile;

final class RepositoryPlan
{
    /**
     * @param array<RepositoryPlanChange> $changes
     */
    public function __construct(public readonly Profile $profile, public readonly array $changes = [])
    {
    }
}
