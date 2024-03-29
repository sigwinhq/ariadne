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

/**
 * @implements \IteratorAggregate<\Sigwin\Ariadne\Model\Config\ProfileConfig>
 *
 * @psalm-import-type TProfile from \Sigwin\Ariadne\Model\Config\ProfileConfig
 *
 * @psalm-type TConfig = array{profiles: array<string, TProfile>}
 */
final readonly class AriadneConfig implements \IteratorAggregate
{
    /**
     * @param array<ProfileConfig> $clientConfig
     */
    private function __construct(public string $url, private array $clientConfig)
    {
    }

    /**
     * @param TConfig $config
     */
    public static function fromArray(string $url, array $config): self
    {
        $profiles = [];
        foreach ($config['profiles'] as $name => $profile) {
            $profile['name'] = $name;

            $profiles[] = ProfileConfig::fromArray($profile);
        }

        return new self($url, $profiles);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->clientConfig);
    }
}
