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

use Sigwin\Ariadne\Model\Change\AttributeUpdate;
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\Model\Change\NamedResourceUpdate;
use Sigwin\Ariadne\Model\Collection\NamedResourceCollection;
use Sigwin\Ariadne\Model\Collection\RepositoryChangeCollection;
use Sigwin\Ariadne\NamedResource;

final class Repository implements NamedResource
{
    /**
     * @param array<string, null|array<string, int|string>|array<string>|bool|int|string> $response
     * @param array<string>                                                               $topics
     * @param array<string>                                                               $languages
     * @param NamedResourceCollection<RepositoryUser>                                     $users
     */
    public function __construct(
        private readonly array $response,
        public readonly RepositoryType $type,
        public readonly RepositoryVisibility $visibility,
        public readonly NamedResourceCollection $users,
        public readonly int $id,
        public readonly string $path,
        public readonly array $topics,
        public readonly array $languages
    ) {
    }

    public function createChangeForTemplate(ProfileTemplate $template): RepositoryChangeCollection
    {
        $changes = [];
        foreach ($template->getTargetAttributes($this) as $name => $expected) {
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

            $changes[] = new AttributeUpdate($name, $actual, $expected);
        }

        $changes = array_merge($changes, $this->createChangesForNamedCollections($template, $template->getTargetUsers($this), $this->users, static function (RepositoryUser $expected, RepositoryUser $actual): array {
            if ($actual->role === $expected->role) {
                return [];
            }

            return [new AttributeUpdate('role', $actual->role, $expected->role)];
        }));

        return RepositoryChangeCollection::fromTemplate($template, $changes);
    }

    public function getName(): string
    {
        return $this->path;
    }

    /**
     * @template T of NamedResource
     *
     * @param NamedResourceCollection<T> $expected
     * @param NamedResourceCollection<T> $actual
     *
     * @return array<\Sigwin\Ariadne\RepositoryChange>
     */
    private function createChangesForNamedCollections(ProfileTemplate $template, NamedResourceCollection $expected, NamedResourceCollection $actual, \Closure $compare): array
    {
        $changes = [];
        foreach ($expected->diff($actual) as $item) {
            $changes[] = new NamedResourceCreate($item);
        }
        foreach ($actual->diff($expected) as $item) {
            $changes[] = new NamedResourceDelete($item);
        }
        foreach ($expected->intersect($actual) as $item) {
            $changes[] = new NamedResourceUpdate(
                $item,
                RepositoryChangeCollection::fromTemplate($template, $compare($item, $actual->get($item->getName())))
            );
        }

        return $changes;
    }
}
