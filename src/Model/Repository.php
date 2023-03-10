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

use Sigwin\Ariadne\Model\Collection\RepositoryChangeCollection;

final class Repository
{
    /**
     * @param array<string, null|array<string, int|string>|array<string>|bool|int|string> $response
     * @param array<string>                                                               $topics
     * @param array<string>                                                               $languages
     */
    public function __construct(private readonly array $response, public readonly RepositoryType $type, public readonly int $id, public readonly string $path, public readonly RepositoryVisibility $visibility, public readonly array $topics, public readonly array $languages)
    {
    }

    public function createChangeForTemplate(ProfileTemplate $template): RepositoryChangeCollection
    {
        $changes = [];
        foreach ($template->target->config->attribute as $name => $expected) {
            if (\array_key_exists($name, $this->response) === false) {
                $message = sprintf('Invalid argument "%1$s"', $name);

                $alternatives = [];
                foreach (array_keys($this->response) as $key) {
                    if (levenshtein($name, $key) <= 3) {
                        $alternatives[] = $key;
                    }
                }

                if ($alternatives !== []) {
                    $message .= sprintf(', did you mean %1$s?', implode(', ', $alternatives));
                }

                throw new \InvalidArgumentException($message);
            }
            $actual = $this->response[$name];

            if (\is_array($actual)) {
                throw new \InvalidArgumentException('Unexpected change type found');
            }

            $changes[] = new RepositoryChange($name, $actual, $expected);
        }

        return RepositoryChangeCollection::fromTemplate($template, $changes);
    }
}
