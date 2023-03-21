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

use Sigwin\Ariadne\Model\Collection\NamedResourceChangeFlattenedCollection;
use Sigwin\Ariadne\Model\Collection\NamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileTemplateConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryAttributeAccess;
use Sigwin\Ariadne\NamedResourceChangeCollection;

trait ProfileTrait
{
    /**
     * @var \Sigwin\Ariadne\NamedResourceCollection<Repository>
     */
    private \Sigwin\Ariadne\NamedResourceCollection $repositories;

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
        $changes = [];
        foreach ($this->getTemplates() as $template) {
            if ($template->contains($repository) === false) {
                continue;
            }

            $changes[] = $repository->createChangeForTemplate($template);
        }

        return NamedResourceChangeFlattenedCollection::fromResource($repository, $changes);
    }

    /**
     * @return \Sigwin\Ariadne\NamedResourceCollection<ProfileTemplate>
     */
    public function getMatchingTemplates(Repository $repository): \Sigwin\Ariadne\NamedResourceCollection
    {
        return $this->getTemplates()->filter(static fn (ProfileTemplate $template): bool => $template->contains($repository));
    }

    /**
     * @return \Sigwin\Ariadne\NamedResourceCollection<ProfileTemplate>
     */
    public function getTemplates(): \Sigwin\Ariadne\NamedResourceCollection
    {
        $templates = [];
        foreach ($this->config->templates as $config) {
            $templates[] = $this->templateFactory->create($config, $this->getRepositories());
        }

        return NamedResourceCollection::fromArray($templates);
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
}
