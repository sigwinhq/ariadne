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

final class ProfileSummary implements \Stringable
{
    public function __construct(public readonly RepositoryCollection $repositories, public readonly TemplateCollection $templates)
    {
    }

    public function __toString(): string
    {
        return sprintf('Repositories: %1$s, Templates: %2$s', $this->repositories->__toString(), $this->templates->__toString());
    }
}
