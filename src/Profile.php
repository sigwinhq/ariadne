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
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;

interface Profile
{
    public static function fromConfig(ClientInterface $client, ProfileConfig $config): self;

    public function getCurrentUser(): ProfileUser;

    public function getApiVersion(): string;

    public function getName(): string;

    public function getSummary(): ProfileSummary;
}
