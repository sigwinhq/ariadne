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

use Sigwin\Ariadne\Model\Config\AriadneConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\Profile\Collection\FilteredProfileGeneratorCollection;
use Sigwin\Ariadne\ProfileCollection;
use Sigwin\Ariadne\ProfileCollectionFactory;
use Sigwin\Ariadne\ProfileFactory;

final class FilteredProfileGeneratorCollectionFactory implements ProfileCollectionFactory
{
    public function __construct(private readonly ProfileFactory $clientFactory) {}

    public function fromConfig(AriadneConfig $config, ProfileFilter $filter): ProfileCollection
    {
        return new FilteredProfileGeneratorCollection($this->clientFactory, $config, $filter);
    }
}
