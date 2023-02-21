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

namespace Sigwin\Ariadne\Profile\Template\Factory;

use Sigwin\Ariadne\Model\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Template;
use Sigwin\Ariadne\ProfileTemplateFactory;

final class RegularTemplateFactory implements ProfileTemplateFactory
{
    public function create(ProfileTemplateConfig $config, Repositories $repositories): Template
    {
        return new Template($config->name);
    }
}
