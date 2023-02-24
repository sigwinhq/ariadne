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

use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Model\TemplateCollection;

trait ProfileTrait
{
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \Traversable<Repository>
     */
    public function getIterator(): \Traversable
    {
        return $this->getRepositories();
    }

    public function plan(Repository $repository): RepositoryPlan
    {
        $changes = [];
        foreach ($this->getTemplates() as $template) {
            $changes[] = $repository->createChangeFromTemplate($template);
        }

        return new RepositoryPlan($repository, $changes);
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
