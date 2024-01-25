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

namespace Sigwin\Ariadne\Model;

use Sigwin\Ariadne\Profile;

final readonly class ProfileFilter
{
    private function __construct(private ?string $name, private ?string $type)
    {
    }

    public function match(Profile $profile): bool
    {
        if ($this->name !== null && $this->name !== $profile->getName()) {
            return false;
        }

        if ($this->type !== null && $this->type !== $profile::getType()) {
            return false;
        }

        return true;
    }

    public static function create(?string $name, ?string $type): self
    {
        return new self($name, $type);
    }
}
