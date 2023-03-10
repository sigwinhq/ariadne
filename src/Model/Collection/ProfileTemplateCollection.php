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

use Sigwin\Ariadne\Model\ProfileTemplate;

/**
 * @implements \IteratorAggregate<ProfileTemplate>
 */
final class ProfileTemplateCollection implements \Countable, \IteratorAggregate
{
    /**
     * @param array<ProfileTemplate> $templates
     */
    public function __construct(private readonly array $templates)
    {
    }

    public function filter(callable $filter): self
    {
        $templates = [];

        foreach ($this->templates as $template) {
            if ($filter($template)) {
                $templates[] = $template;
            }
        }

        return new self($templates);
    }

    public function count(): int
    {
        return \count($this->templates);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->templates);
    }
}
