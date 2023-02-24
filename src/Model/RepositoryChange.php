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

final class RepositoryChange
{
    /**
     * @param array<string, array{0: array<string, bool|int|string>|bool|int|string, 1: array<string, bool|int|string>|bool|int|string}> $changes
     */
    private function __construct(private readonly Template $template, private readonly array $changes)
    {
    }

    /**
     * @param array<string, array{0:  array<string, bool|int|string>|bool|int|string, 1: array<string, bool|int|string>|bool|int|string}> $changes
     */
    public static function fromTemplate(Template $template, array $changes): self
    {
        return new self($template, $changes);
    }
}
