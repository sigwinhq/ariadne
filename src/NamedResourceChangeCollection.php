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
 * @template TChanges of NamedResourceChange
 *
 * @extends \IteratorAggregate<TChanges>
 */
interface NamedResourceChangeCollection extends \Countable, \IteratorAggregate, NamedResourceChange
{
    /**
     * @param TResource      $resource
     * @param list<TChanges> $changes
     *
     * @return self<TResource, TChanges>
     */
    public static function fromResource(NamedResource $resource, array $changes): self;

    /**
     * @param class-string<TChanges> $type
     *
     * @return self<TResource, TChanges>
     */
    public function filter(string $type): self;
}
