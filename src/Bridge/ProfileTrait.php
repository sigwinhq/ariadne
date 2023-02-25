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
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryPlan;
use Sigwin\Ariadne\Model\Template;
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

    public function getSummary(): ProfileSummary
    {
        return new ProfileSummary($this->getRepositories(), $this->getTemplates());
    }

    public function plan(Repository $repository): RepositoryPlan
    {
        $changes = [];
        foreach ($this->getTemplates() as $template) {
            if ($template->contains($repository) === false) {
                continue;
            }

            $changes[] = $repository->createChangeForTemplate($template);
        }

        return new RepositoryPlan($repository, $changes);
    }

    public function getMatchingTemplates(Repository $repository): TemplateCollection
    {
        return $this->getTemplates()->filter(static fn (Template $template): bool => $template->contains($repository));
    }

    public function getTemplates(): TemplateCollection
    {
        $templates = [];
        foreach ($this->config->templates as $config) {
            $templates[] = $this->templateFactory->create($config, $this->getRepositories());
        }

        return new TemplateCollection($templates);
    }
}
