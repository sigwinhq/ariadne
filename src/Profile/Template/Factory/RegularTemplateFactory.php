<?php

namespace Sigwin\Ariadne\Profile\Template\Factory;

use Sigwin\Ariadne\Model\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\Repositories;
use Sigwin\Ariadne\Model\Template;
use Sigwin\Ariadne\ProfileTemplateFactory;

class RegularTemplateFactory implements ProfileTemplateFactory
{
    public function create(ProfileTemplateConfig $config, Repositories $repositories): Template
    {
        return new Template();
    }
}
