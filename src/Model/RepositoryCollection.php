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
final class RepositoryCollection implements \Countable, \IteratorAggregate, \Stringable
{
    /** @var list<string> */
    private array $paths;

    /**
     * @param array<Repository> $repositories
     */
    private function __construct(private readonly array $repositories)
    {
        $this->paths = array_map(static fn (Repository $repository): string => $repository->path, $this->repositories);
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

    public function __toString(): string
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

        $summary = array_map(static fn (string $key, int $value): string => sprintf('%1$s: %2$d', $key, $value), array_keys($summary), array_values($summary));

        return implode(\PHP_EOL, $summary);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->repositories);
    }
}
