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

namespace Sigwin\Ariadne\Model\Collection;

use Sigwin\Ariadne\Model\Repository;

/**
 * @implements \IteratorAggregate<Repository>
 */
final class RepositoryCollection implements \Countable, \IteratorAggregate
{
    /** @var list<string> */
    private array $paths;

    /**
     * @param array<Repository> $repositories
     */
    private function __construct(private readonly array $repositories)
    {
        $this->paths = array_values(array_map(static fn (Repository $repository): string => $repository->path, $this->repositories));
    }

    /**
     * @param array<Repository> $repositories
     */
    public static function fromArray(array $repositories): self
    {
        usort($repositories, static fn (Repository $first, Repository $second): int => $first->path <=> $second->path);

        return new self($repositories);
    }

    public function filter(callable $filter): self
    {
        $repositories = [];

        foreach ($this->repositories as $repository) {
            if ($filter($repository)) {
                $repositories[] = $repository;
            }
        }

        return new self($repositories);
    }

    public function count(): int
    {
        return \count($this->repositories);
    }

    public function contains(Repository $repository): bool
    {
        return \in_array($repository->path, $this->paths, true);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->repositories);
    }
}
