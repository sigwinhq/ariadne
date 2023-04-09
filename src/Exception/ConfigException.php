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

namespace Sigwin\Ariadne\Exception;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class ConfigException extends \RuntimeException
{
    private function __construct(public readonly string $url, string $message, \Throwable $previous)
    {
        parent::__construct($message, $previous->getCode(), $previous);
    }

    public static function fromConfigException(string $prefix, string $url, InvalidConfigurationException $exception): self
    {
        return new self($url, str_replace($prefix, '', $exception->getMessage()), $exception);
    }
}
