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

namespace Sigwin\Ariadne\Profile\Collection\Factory;

use Sigwin\Ariadne\Model\Config\AdrianeConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Profile\Collection\ProfileGeneratorCollection;
use Sigwin\Ariadne\ProfileCollection;
use Sigwin\Ariadne\ProfileCollectionFactory;
use Sigwin\Ariadne\ProfileFactory;

final class ProfileGeneratorCollectionFactory implements ProfileCollectionFactory
{
    public function __construct(private readonly ProfileFactory $clientFactory)
    {
    }

    public function create(AdrianeConfig $config, ProfileFilter $filter): ProfileCollection
    {
        return new ProfileGeneratorCollection($this->clientFactory, $config, $filter);
    }
}
