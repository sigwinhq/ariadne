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

namespace Sigwin\Ariadne\Profile\Collection;

use Sigwin\Ariadne\Model\Config\AriadneConfig;
use Sigwin\Ariadne\Model\ProfileFilter;
use Sigwin\Ariadne\ProfileCollection;
use Sigwin\Ariadne\ProfileFactory;

final readonly class FilteredProfileGeneratorCollection implements ProfileCollection
{
    public function __construct(private ProfileFactory $profileFactory, private AriadneConfig $config, private ProfileFilter $filter) {}

    public function getIterator(): \Traversable
    {
        foreach ($this->config as $profileConfig) {
            $profile = $this->profileFactory->fromConfig($profileConfig);

            if ($this->filter->match($profile)) {
                yield $profile;
            }
        }
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
