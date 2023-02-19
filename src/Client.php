<?php

declare(strict_types=1);

/*
 * This file is part of the ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne;

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\CurrentUser;

interface Client
{
    public static function fromSpec(ClientInterface $client, array $spec): self;

    public function getCurrentUser(): CurrentUser;

    public function getApiVersion(): string;

    public function getName(): string;
}
