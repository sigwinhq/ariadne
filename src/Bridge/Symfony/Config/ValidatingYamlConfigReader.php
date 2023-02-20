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

namespace Sigwin\Ariadne\Bridge\Symfony\Config;

use Sigwin\Ariadne\ConfigReader;
use Sigwin\Ariadne\Model\Config;
use Symfony\Component\Yaml\Yaml;

final class ValidatingYamlConfigReader implements ConfigReader
{
    public function read(?string $url = null): Config
    {
        /** @var list<array{type: string, name: string, auth: array{type: string, token: string}, parameters: array}> $config */
        $config = Yaml::parseFile($url ?? 'ariadne.yaml');

        return Config::fromArray($config);
    }
}
