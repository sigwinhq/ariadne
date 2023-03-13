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

use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
use Sigwin\Ariadne\NamedResource;

/**
 * @psalm-import-type TProfileTemplateTargetAttribute from \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 *
 * @implements \IteratorAggregate<Repository>
 */
final class ProfileTemplate implements \Countable, \IteratorAggregate, NamedResource
{
    public function __construct(public readonly string $name, private readonly ProfileTemplateTarget $target, private readonly RepositoryCollection $repositories)
    {
    }

    public function count(): int
    {
        return \count($this->repositories);
    }

    public function getIterator(): \Traversable
    {
        return $this->repositories;
    }

    public function contains(Repository $repository): bool
    {
        return $this->repositories->contains($repository);
    }

    /**
     * @return TProfileTemplateTargetAttribute
     */
    public function getTargetAttributes(Repository $repository): array
    {
        return $this->target->getAttributes($this, $repository);
    }

    /**
     * @return list<RepositoryUser>
     */
    public function getUsers(Repository $repository): array
    {
        return $this->target->getUsers($this, $repository);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
