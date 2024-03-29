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

use Sigwin\Ariadne\Evaluator;
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig;
use Sigwin\Ariadne\NamedResourceCollection;

/**
 * @psalm-import-type TProfileTemplateTargetAttribute from ProfileTemplateTargetConfig
 */
final readonly class ProfileTemplateTarget
{
    private function __construct(private ProfileTemplateTargetConfig $config, private Evaluator $evaluator)
    {
    }

    /**
     * @return TProfileTemplateTargetAttribute
     */
    public function getAttributes(ProfileTemplate $template, Repository $repository): array
    {
        $attributes = [];
        foreach ($this->config->attribute as $name => $value) {
            $attributes[$name] = $this->evaluator->evaluate($value, [
                'template' => $template,
                'repository' => $repository,
            ]);
        }

        return $attributes;
    }

    public static function fromConfig(ProfileTemplateTargetConfig $config, Evaluator $evaluator): self
    {
        return new self($config, $evaluator);
    }

    /**
     * @return NamedResourceCollection<RepositoryUser>
     */
    public function getUsers(ProfileTemplate $param, Repository $repository): NamedResourceCollection
    {
        $users = [];
        foreach ($this->config->users as $user) {
            $users[] = new RepositoryUser($user->username, $user->role);
        }

        return SortedNamedResourceCollection::fromArray($users);
    }
}
