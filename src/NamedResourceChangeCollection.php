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
 * @template TResource of NamedResource
 * @template TChange of NamedResourceChange
 *
 * @extends \IteratorAggregate<TChange>
 */
interface NamedResourceChangeCollection extends \Countable, \IteratorAggregate, NamedResourceChange
{
    /**
     * @template STResource of NamedResource
     * @template STChange of NamedResourceChange
     *
     * @param STResource     $resource
     * @param list<STChange> $changes
     *
     * @return self<STResource, STChange>
     */
    public static function fromResource(NamedResource $resource, array $changes): self;

    /**
     * @param class-string<TChange> $type
     *
     * @return self<TResource, TChange>
     */
    public function filter(string $type): self;
}
