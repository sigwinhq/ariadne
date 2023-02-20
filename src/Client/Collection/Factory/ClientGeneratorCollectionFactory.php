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

namespace Sigwin\Ariadne\Client\Collection\Factory;

use Sigwin\Ariadne\Client\Collection\ClientGeneratorCollection;
use Sigwin\Ariadne\ClientCollection;
use Sigwin\Ariadne\ClientFactory;
use Sigwin\Ariadne\Model\Config;

final class ClientGeneratorCollectionFactory implements \Sigwin\Ariadne\ClientCollectionFactory
{
    public function __construct(private readonly ClientFactory $clientFactory)
    {
    }

    public function create(Config $config): ClientCollection
    {
        return new ClientGeneratorCollection($config, $this->clientFactory);
    }
}
