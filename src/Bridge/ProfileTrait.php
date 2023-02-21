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

namespace Sigwin\Ariadne\Bridge;

use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\TemplateCollection;

trait ProfileTrait
{
    public function getSummary(): ProfileSummary
    {
        return new ProfileSummary($this->getRepositories(), $this->getTemplates());
    }

    public function getIterator(): \Traversable
    {
        return $this->getTemplates();
    }

    private function getTemplates(): TemplateCollection
    {
        $templates = [];
        foreach ($this->config->templates as $config) {
            $templates[] = $this->templateFactory->create($config, $this->getRepositories());
        }

        return new TemplateCollection($templates);
    }
}
