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

interface ClientFactory
{
    /**
     * @param array{name: string, auth: array{type: string, token: string}, parameters: array} $spec
     */
    public function create(string $type, array $spec): Client;
}
