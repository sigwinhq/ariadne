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

use Sigwin\Ariadne\ClientFactory;
use Sigwin\Ariadne\Model\Config;
use Sigwin\Ariadne\Profile\Collection\ProfileGeneratorCollection;
use Sigwin\Ariadne\ProfileCollection;

final class ProfileGeneratorCollectionFactory implements \Sigwin\Ariadne\ProfileCollectionFactory
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
    }

    public function create(Config $config): ProfileCollection
    {
        return new ProfileGeneratorCollection($config, $this->clientFactory);
    }
}
