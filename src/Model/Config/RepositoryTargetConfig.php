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

namespace Sigwin\Ariadne\Model\Config;

use Sigwin\Ariadne\Model\TRepositoryTarget;
use Sigwin\Ariadne\Model\TRepositoryTargetAttribute;

/**
 * @psalm-type TRepositoryTargetAttribute = array<string, bool|string|int>
 * @psalm-type TRepositoryTarget = array{attribute: TRepositoryTargetAttribute}
 */
final class RepositoryTargetConfig
{
    /**
     * @param TRepositoryTargetAttribute $attribute
     */
    private function __construct(public readonly array $attribute)
    {
    }

    /**
     * @param TRepositoryTarget $config
     */
    public static function fromArray(array $config): self
    {
        return new self($config['attribute']);
    }
}
