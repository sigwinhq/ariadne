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

namespace Sigwin\Ariadne\Model\Change;

use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;

/**
 * @template TResource of NamedResource
 * @template TChange of NamedResourceChange
 *
 * @implements NamedResourceChangeCollection<TResource, TChange>
 */
final class NamedResourceArrayChangeCollection implements NamedResourceChangeCollection
{
    /**
     * @use NamedResourceChangeTrait<TResource, TChange>
     */
    use NamedResourceChangeTrait;
}
