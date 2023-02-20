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

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\ClientConfig;
use Sigwin\Ariadne\Model\CurrentUser;
use Sigwin\Ariadne\Model\Repositories;

interface Client
{
    public static function fromConfig(ClientInterface $client, ClientConfig $config): self;

    public function getCurrentUser(): CurrentUser;

    public function getApiVersion(): string;

    public function getName(): string;

    public function getRepositories(): Repositories;
}
