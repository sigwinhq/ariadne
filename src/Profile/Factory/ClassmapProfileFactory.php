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

namespace Sigwin\Ariadne\Profile\Factory;

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\ProfileFactory;

final class ClassmapProfileFactory implements ProfileFactory
{
    /**
     * @param array<string, class-string<Profile>> $profilesMap
     */
    public function __construct(private readonly array $profilesMap, private readonly ClientInterface $httpClient)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(ProfileConfig $config): Profile
    {
        if (! \array_key_exists($config->type, $this->profilesMap)) {
            throw new \LogicException(sprintf('Unknown client type "%1$s"', $config->type));
        }
        $className = $this->profilesMap[$config->type];

        return $className::fromConfig($this->httpClient, $config);
    }
}
