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
 * @implements \IteratorAggregate<Template>
 */
final class TemplateCollection implements \Countable, \IteratorAggregate
{
    /**
     * @param array<Template> $templates
     */
    public function __construct(private readonly array $templates)
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->templates);
    }

    public function count(): int
    {
        return \count($this->templates);
    }
}
