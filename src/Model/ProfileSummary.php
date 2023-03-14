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

use Sigwin\Ariadne\Model\Collection\ProfileTemplateCollection;
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;

final class ProfileSummary
{
    public function __construct(private readonly RepositoryCollection $repositories, private readonly ProfileTemplateCollection $templates)
    {
    }

    /**
     * @return array<string, int>
     */
    public function getRepositories(): array
    {
        $summary = [];
        foreach ($this->repositories as $repository) {
            $offset = mb_strpos($repository->path, '/');
            if ($offset === false) {
                throw new \InvalidArgumentException(sprintf('Invalid repo path %1$s', $repository->path));
            }

            $namespace = mb_substr($repository->path, 0, $offset);

            $summary[$namespace] ??= 0;
            ++$summary[$namespace];
        }
        ksort($summary);

        return $summary;
    }

    /**
     * @return array<string, int>
     */
    public function getTemplates(): array
    {
        $summary = [];
        foreach ($this->templates as $template) {
            $summary[$template->getName()] = \count($template);
        }

        return $summary;
    }
}
