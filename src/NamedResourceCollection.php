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

namespace Sigwin\Ariadne;

/**
 * @template T of NamedResource
 *
 * @extends \IteratorAggregate<T>
 */
interface NamedResourceCollection extends \Countable, \IteratorAggregate
{
    /**
     * @template ST of NamedResource
     *
     * @param list<ST> $items
     *
     * @return self<ST>
     */
    public static function fromArray(array $items): self;

    /**
     * @param callable(T): bool $filter
     *
     * @return self<T>
     */
    public function filter(callable $filter): self;

    /**
     * @param self<T> $other
     *
     * @return self<T>
     */
    public function diff(self $other): self;

    /**
     * @param self<T> $other
     *
     * @return self<T>
     */
    public function intersect(self $other): self;

    /**
     * @param T $item
     */
    public function contains(NamedResource $item): bool;

    /**
     * @return T of NamedResource
     */
    public function get(string $name): NamedResource;
}
