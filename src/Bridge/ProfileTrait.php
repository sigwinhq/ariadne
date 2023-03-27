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

use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryAttributeAccess;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\NamedResourceCollection;

trait ProfileTrait
{
    /**
     * @var NamedResourceCollection<Repository>
     */
    private NamedResourceCollection $repositories;

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

    public function plan(Repository $repository): NamedResourceChangeCollection
    {
        $changesets = [];
        foreach ($this->getTemplates() as $template) {
            if ($template->contains($repository) === false) {
                continue;
            }

            $changesets[] = $repository->createChangeForTemplate($template);
        }

        // flatten changes into a linear list
        $changes = [];
        foreach ($changesets as $changeset) {
            foreach ($changeset as $change) {
                $changes[$change->getResource()->getName()] = $change;
            }
        }

        return \Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection::fromResource($repository, array_values($changes));
    }

    /**
     * @return NamedResourceCollection<ProfileTemplate>
     */
    public function getMatchingTemplates(Repository $repository): NamedResourceCollection
    {
        return $this->getTemplates()->filter(static fn (ProfileTemplate $template): bool => $template->contains($repository));
    }

    /**
     * @return NamedResourceCollection<ProfileTemplate>
     */
    public function getTemplates(): NamedResourceCollection
    {
        $templates = [];
        foreach ($this->config->templates as $config) {
            $templates[] = $this->templateFactory->fromConfig($config, $this->getRepositories());
        }

        return SortedNamedResourceCollection::fromArray($templates);
    }

    /**
     * @param array<ProfileTemplateConfig> $templates
     */
    private function validateAttributes(array $templates): void
    {
        $attributes = $this->getAttributes();
        foreach ($templates as $template) {
            foreach (array_keys($template->target->attribute) as $attribute) {
                if (! \array_key_exists($attribute, $attributes)) {
                    $alternatives = $this->findAlternatives($attribute, array_keys($attributes));
                    $message = sprintf('Attribute "%1$s" does not exist.', $attribute);
                    if (\count($alternatives) > 0) {
                        $message .= sprintf(' Did you mean "%1$s"?', implode('", "', $alternatives));
                    }

                    throw new \InvalidArgumentException($message);
                } elseif ($attributes[$attribute]['access'] !== RepositoryAttributeAccess::READ_WRITE) {
                    throw new \InvalidArgumentException(sprintf('Attribute "%1$s" is read-only.', $attribute));
                }
            }
        }
    }

    /**
     * @param array<string> $haystack
     *
     * @return array<string>
     */
    private function findAlternatives(string $needle, array $haystack): array
    {
        $alternatives = [];
        foreach ($haystack as $alternative) {
            if (levenshtein($needle, $alternative) <= 3) {
                $alternatives[] = $alternative;
            }
        }

        return $alternatives;
    }

    private function needsLanguages(): bool
    {
        foreach ($this->config->templates as $template) {
            if (($template->filter['languages'] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    private function needsUsers(): bool
    {
        foreach ($this->config->templates as $template) {
            if ($template->target->users !== []) {
                return true;
            }
        }

        return false;
    }
}
