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

namespace Sigwin\Ariadne;

use Sigwin\Ariadne\Model\AdrianeConfig;
use Sigwin\Ariadne\Model\ProfileFilter;

interface ProfileCollectionFactory
{
    public function create(AdrianeConfig $config, ProfileFilter $filter): ProfileCollection;
}
