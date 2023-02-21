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

use Sigwin\Ariadne\Model\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Template;

interface ProfileTemplateFactory
{
    public function create(ProfileTemplateConfig $config, Repositories $repositories): Template;
}
