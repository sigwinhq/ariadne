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

use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

final class ConfigException extends \RuntimeException
{
    private function __construct(string $message, \Throwable $previous, public readonly ?string $url = null)
    {
        parent::__construct($message, $previous->getCode(), $previous);
    }

    public static function fromConfigException(string $prefix, string $url, InvalidConfigurationException $exception): self
    {
        return new self(str_replace($prefix, '', $exception->getMessage()), $exception, $url);
    }

    public static function fromInvalidOptionsException(string $path, ProfileConfig $config, InvalidOptionsException $exception): self
    {
        $message = $exception->getMessage();
        if (preg_match('/^The option "(?<name>[a-zA-Z0-9-_]+)" with value "(?<value>[a-zA-Z0-9-_]+)" is invalid. Accepted values are: (?<permissible>[^\.]+).$/', $message, $matches) === 1) {
            $message = sprintf('The value "%1$s" is not allowed for path "%2$s". Permissible values: %3$s', $matches['value'], 'profiles.'.$config->name.'.'.$path.'.'.$matches['name'], $matches['permissible']);
        }

        return new self($message, $exception);
    }

    public static function fromUndefinedOptionsException(string $path, ProfileConfig $config, UndefinedOptionsException $exception): self
    {
        $message = $exception->getMessage();
        if (preg_match('/^The option "(?<name>[a-zA-Z0-9-_]+)" does not exist. Defined options are: (?<permissible>[^\.]+).$/', $message, $matches) === 1) {
            $message = sprintf('Unrecognized option "%1$s" under "%2$s". Permissible values: %3$s', $matches['name'], 'profiles.'.$config->name.','.$path, $matches['permissible']);
        }

        return new self($message, $exception);
    }
}
