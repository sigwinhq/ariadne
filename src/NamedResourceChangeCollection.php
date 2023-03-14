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

use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;

/**
 * @extends \IteratorAggregate<NamedResourceChange>
 */
interface NamedResourceChangeCollection extends \Countable, \IteratorAggregate, NamedResourceChange
{
    /**
     * @param array<NamedResourceChange> $changes
     */
    public static function fromResource(NamedResource $resource, array $changes): self;

    /**
     * @return array<string, NamedResourceAttributeUpdate>
     */
    public function getAttributeChanges(): array;
}
