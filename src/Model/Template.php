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

/**
 * @implements \IteratorAggregate<Repository>
 */
final class Template implements \Countable, \IteratorAggregate, \Stringable
{
    public function __construct(public readonly string $name, public readonly RepositoryTarget $target, public readonly RepositoryCollection $repositories)
    {
    }

    public function __toString(): string
    {
        return $this->name;
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
}
